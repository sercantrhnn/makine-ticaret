<?php

namespace App\Command;

use App\Entity\Products;
use App\Entity\Categories;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:translate-content',
    description: 'Translate dynamic content using DeepL API'
)]
class TranslateContentCommand extends Command
{
    public function __construct(
        private TranslationService $translationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity to translate (products, categories, all)')
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED, 'Target locale for translation', 'en')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of entities to process', 10)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force retranslation of existing content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $entity = $input->getArgument('entity');
        $targetLocale = $input->getOption('locale');
        $limit = (int) $input->getOption('limit');
        $force = $input->getOption('force');

        $io->title('Dynamic Content Translation');
        $io->text("Entity: {$entity}");
        $io->text("Target Locale: {$targetLocale}");
        $io->text("Limit: {$limit}");
        $io->text("Force: " . ($force ? 'Yes' : 'No'));

        // Desteklenen locale kontrolü
        $supportedLocales = $this->translationService->getSupportedLocales();
        if (!in_array($targetLocale, $supportedLocales)) {
            $io->error("Unsupported locale: {$targetLocale}. Supported: " . implode(', ', $supportedLocales));
            return Command::FAILURE;
        }

        try {
            switch ($entity) {
                case 'products':
                    $this->translateProducts($io, $targetLocale, $limit, $force);
                    break;
                case 'categories':
                    $this->translateCategories($io, $targetLocale, $limit, $force);
                    break;
                case 'all':
                    $this->translateProducts($io, $targetLocale, $limit, $force);
                    $this->translateCategories($io, $targetLocale, $limit, $force);
                    break;
                default:
                    $io->error("Unknown entity: {$entity}. Use: products, categories, or all");
                    return Command::FAILURE;
            }

            $io->success('Translation completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Translation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function translateProducts(SymfonyStyle $io, string $targetLocale, int $limit, bool $force): void
    {
        $io->section('Translating Products');

        $repository = $this->entityManager->getRepository(Products::class);
        $products = $repository->createQueryBuilder('p')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $io->progressStart(count($products));

        foreach ($products as $product) {
            try {
                // Set locale for entity
                $product->setTranslatableLocale($targetLocale);

                // Çevrilecek alanlar
                $fieldsToTranslate = ['name', 'description', 'detail'];
                
                $this->translationService->translateEntity($product, $fieldsToTranslate, $targetLocale);
                
                $io->progressAdvance();
                
            } catch (\Exception $e) {
                $io->writeln("\nError translating product {$product->getId()}: " . $e->getMessage());
            }
        }

        $io->progressFinish();
        $io->text(sprintf('Processed %d products', count($products)));
    }

    private function translateCategories(SymfonyStyle $io, string $targetLocale, int $limit, bool $force): void
    {
        $io->section('Translating Categories');

        $repository = $this->entityManager->getRepository(Categories::class);
        $categories = $repository->createQueryBuilder('c')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $io->progressStart(count($categories));

        foreach ($categories as $category) {
            try {
                // Set locale for entity
                $category->setTranslatableLocale($targetLocale);

                // Çevrilecek alanlar
                $fieldsToTranslate = ['name', 'description'];
                
                $this->translationService->translateEntity($category, $fieldsToTranslate, $targetLocale);
                
                $io->progressAdvance();
                
            } catch (\Exception $e) {
                $io->writeln("\nError translating category {$category->getId()}: " . $e->getMessage());
            }
        }

        $io->progressFinish();
        $io->text(sprintf('Processed %d categories', count($categories)));
    }
}
