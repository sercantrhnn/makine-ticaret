<?php

namespace App\Command;

use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auto-translate-new',
    description: 'Automatically translate new content that has no translations'
)]
class AutoTranslateNewContentCommand extends Command
{
    private const SUPPORTED_LOCALES = ['en', 'de', 'ar'];
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslationService $translationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entity', 'e', InputOption::VALUE_OPTIONAL, 'Entity to translate (categories, products, or all)', 'all')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Target locale (en, de, ar, or all)', 'all')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of items', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $entity = $input->getOption('entity');
        $locale = $input->getOption('locale');
        $limit = (int) $input->getOption('limit');

        $io->title('Auto-Translate New Content');
        
        $entities = $entity === 'all' ? ['categories', 'products'] : [$entity];
        $locales = $locale === 'all' ? self::SUPPORTED_LOCALES : [$locale];
        
        foreach ($entities as $entityType) {
            foreach ($locales as $targetLocale) {
                $io->section("Processing {$entityType} for locale: {$targetLocale}");
                
                $newItems = $this->findUntranslatedItems($entityType, $targetLocale, $limit);
                
                if (empty($newItems)) {
                    $io->success("No new {$entityType} to translate for {$targetLocale}");
                    continue;
                }
                
                $io->progressStart(count($newItems));
                
                foreach ($newItems as $item) {
                    try {
                        $this->translationService->translateEntity($item, $targetLocale, 'tr');
                        $io->progressAdvance();
                    } catch (\Exception $e) {
                        $io->error("Failed to translate {$entityType} ID {$item->getId()}: " . $e->getMessage());
                    }
                }
                
                $io->progressFinish();
                $io->success("Translated " . count($newItems) . " {$entityType} to {$targetLocale}");
            }
        }

        return Command::SUCCESS;
    }

    private function findUntranslatedItems(string $entityType, string $locale, int $limit): array
    {
        $className = match($entityType) {
            'categories' => \App\Entity\Categories::class,
            'products' => \App\Entity\Products::class,
            default => throw new \InvalidArgumentException("Unknown entity: {$entityType}")
        };

        // Ana entity'leri al
        $qb = $this->entityManager->createQueryBuilder();
        $entities = $qb->select('e')
            ->from($className, 'e')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Çevirileri olmayan entity'leri filtrele
        $untranslated = [];
        foreach ($entities as $entity) {
            if (!$this->hasTranslation($entity, $locale)) {
                $untranslated[] = $entity;
            }
        }

        return $untranslated;
    }

    private function hasTranslation($entity, string $locale): bool
    {
        $conn = $this->entityManager->getConnection();
        
        $sql = "SELECT COUNT(*) FROM ext_translations 
                WHERE locale = :locale 
                AND object_class = :class 
                AND foreign_key = :id 
                AND field = 'name'";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'locale' => $locale,
            'class' => get_class($entity),
            'id' => (string)$entity->getId()
        ]);
        
        return $result->fetchOne() > 0;
    }
}
