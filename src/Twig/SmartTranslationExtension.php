<?php

namespace App\Twig;

use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SmartTranslationExtension extends AbstractExtension
{
    public function __construct(
        private TranslationService $translationService,
        private EntityManagerInterface $entityManager
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('smart_translate', [$this, 'smartTranslate']),
        ];
    }

    /**
     * Akıllı çeviri: Önce database'e bak, yoksa API'den çevir ve kaydet
     */
    public function smartTranslate($entity, string $field, string $locale, string $sourceLocale = 'tr'): string
    {
        // Türkçe ise orijinal döndür
        if ($locale === $sourceLocale) {
            return $this->getFieldValue($entity, $field);
        }

        // Database'de çeviri var mı kontrol et
        $translation = $this->getExistingTranslation($entity, $field, $locale);
        if ($translation) {
            return $translation;
        }

        // Çeviri yoksa API'den çevir ve kaydet
        $originalText = $this->getFieldValue($entity, $field);
        if (empty($originalText)) {
            return $originalText;
        }

        try {
            $translatedText = $this->translationService->translateText($originalText, $locale, $sourceLocale);
            
            if ($translatedText) {
                // Database'e kaydet (gelecekte kullanım için)
                $this->saveTranslation($entity, $field, $locale, $translatedText);
                return $translatedText;
            }
        } catch (\Exception $e) {
            // API hatası durumunda orijinal metni döndür
            error_log("Translation failed: " . $e->getMessage());
        }

        return $originalText;
    }

    private function getFieldValue($entity, string $field): string
    {
        $getter = 'get' . ucfirst($field);
        if (method_exists($entity, $getter)) {
            return $entity->$getter() ?? '';
        }
        return '';
    }

    private function getExistingTranslation($entity, string $field, string $locale): ?string
    {
        $conn = $this->entityManager->getConnection();
        
        $sql = "SELECT content FROM ext_translations 
                WHERE locale = :locale 
                AND object_class = :class 
                AND field = :field 
                AND foreign_key = :id";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'locale' => $locale,
            'class' => get_class($entity),
            'field' => $field,
            'id' => (string)$entity->getId()
        ]);
        
        return $result->fetchOne() ?: null;
    }

    private function saveTranslation($entity, string $field, string $locale, string $translation): void
    {
        try {
            $this->translationService->saveTranslationForEntity(
                $entity,
                $field,
                $locale,
                $translation
            );
        } catch (\Exception $e) {
            error_log("Failed to save translation: " . $e->getMessage());
        }
    }
}
