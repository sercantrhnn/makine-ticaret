<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class TranslationService
{
    private const DEEPL_BASE_URL = 'https://api-free.deepl.com/v2';
    
    // DeepL dil kodu eşleştirmesi
    private const LOCALE_TO_DEEPL = [
        'tr' => 'TR',
        'en' => 'EN',
        'de' => 'DE',
        'ar' => 'AR'
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * DeepL API kullanarak metni çevir
     */
    public function translateText(string $text, string $targetLocale, string $sourceLocale = 'tr'): ?string
    {
        // Aynı dil ise çeviriye gerek yok
        if ($sourceLocale === $targetLocale) {
            return $text;
        }

        // Boş metin kontrolü
        if (empty(trim($text))) {
            return $text;
        }

        try {
            $deeplApiKey = $this->params->get('deepl_api_key');
            if (!$deeplApiKey) {
                $this->logger->warning('DeepL API key not configured');
                return null;
            }

            $sourceCode = self::LOCALE_TO_DEEPL[$sourceLocale] ?? 'TR';
            $targetCode = self::LOCALE_TO_DEEPL[$targetLocale] ?? 'EN';

            $response = $this->httpClient->request('POST', self::DEEPL_BASE_URL . '/translate', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $deeplApiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $text,
                    'source_lang' => $sourceCode,
                    'target_lang' => $targetCode,
                    'preserve_formatting' => '1',
                    'formality' => 'default'
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                if (isset($data['translations'][0]['text'])) {
                    $translatedText = $data['translations'][0]['text'];
                    
                    $this->logger->info('Text translated successfully', [
                        'source_locale' => $sourceLocale,
                        'target_locale' => $targetLocale,
                        'original_length' => strlen($text),
                        'translated_length' => strlen($translatedText)
                    ]);
                    
                    return $translatedText;
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Translation failed', [
                'error' => $e->getMessage(),
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'text_length' => strlen($text)
            ]);
        }

        return null;
    }

    /**
     * Çoklu metin çevirisi (batch)
     */
    public function translateTexts(array $texts, string $targetLocale, string $sourceLocale = 'tr'): array
    {
        if ($sourceLocale === $targetLocale) {
            return $texts;
        }

        $results = [];
        
        try {
            $deeplApiKey = $this->params->get('deepl_api_key');
            if (!$deeplApiKey) {
                return array_fill_keys(array_keys($texts), null);
            }

            $sourceCode = self::LOCALE_TO_DEEPL[$sourceLocale] ?? 'TR';
            $targetCode = self::LOCALE_TO_DEEPL[$targetLocale] ?? 'EN';

            // DeepL API batch çeviri için metinleri hazırla
            $textValues = array_values($texts);
            $textKeys = array_keys($texts);

            $response = $this->httpClient->request('POST', self::DEEPL_BASE_URL . '/translate', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $deeplApiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $textValues,
                    'source_lang' => $sourceCode,
                    'target_lang' => $targetCode,
                    'preserve_formatting' => '1',
                    'formality' => 'default'
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                if (isset($data['translations'])) {
                    foreach ($data['translations'] as $index => $translation) {
                        $key = $textKeys[$index] ?? $index;
                        $results[$key] = $translation['text'] ?? null;
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Batch translation failed', [
                'error' => $e->getMessage(),
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'text_count' => count($texts)
            ]);
            
            return array_fill_keys(array_keys($texts), null);
        }

        return $results;
    }

    /**
     * Entity için otomatik çeviri
     */
    public function translateEntity($entity, array $fields, string $targetLocale): void
    {
        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);
            
            if (method_exists($entity, $getter) && method_exists($entity, $setter)) {
                $originalText = $entity->$getter();
                
                if ($originalText && is_string($originalText)) {
                    $translatedText = $this->translateText($originalText, $targetLocale);
                    
                    if ($translatedText) {
                        // Gedmo Translatable extension kullanarak çeviriyi kaydet
                        $this->saveTranslation($entity, $field, $targetLocale, $translatedText);
                    }
                }
            }
        }
    }

    /**
     * Gedmo Translatable extension ile çeviriyi kaydet
     */
    public function saveTranslationForEntity($entity, string $field, string $locale, string $translation): void
    {
        $this->saveTranslation($entity, $field, $locale, $translation);
    }

    private function saveTranslation($entity, string $field, string $locale, string $translation): void
    {
        try {
            // Direct SQL insert approach for Gedmo Translatable
            $entityClass = get_class($entity);
            $entityId = $entity->getId();
            
            if (!$entityId) {
                throw new \Exception('Entity must have an ID to save translations');
            }
            
            // Check if translation already exists
            $existingQuery = $this->entityManager->getConnection()->prepare('
                SELECT id FROM ext_translations 
                WHERE locale = :locale 
                AND object_class = :object_class 
                AND field = :field 
                AND foreign_key = :foreign_key
            ');
            
            $existingResult = $existingQuery->executeQuery([
                'locale' => $locale,
                'object_class' => $entityClass,
                'field' => $field,
                'foreign_key' => (string)$entityId
            ]);
            
            if ($existingResult->fetchOne()) {
                // Update existing translation
                $updateQuery = $this->entityManager->getConnection()->prepare('
                    UPDATE ext_translations 
                    SET content = :content 
                    WHERE locale = :locale 
                    AND object_class = :object_class 
                    AND field = :field 
                    AND foreign_key = :foreign_key
                ');
                
                $updateQuery->executeStatement([
                    'content' => $translation,
                    'locale' => $locale,
                    'object_class' => $entityClass,
                    'field' => $field,
                    'foreign_key' => (string)$entityId
                ]);
            } else {
                // Insert new translation
                $insertQuery = $this->entityManager->getConnection()->prepare('
                    INSERT INTO ext_translations (locale, object_class, field, foreign_key, content) 
                    VALUES (:locale, :object_class, :field, :foreign_key, :content)
                ');
                
                $insertQuery->executeStatement([
                    'locale' => $locale,
                    'object_class' => $entityClass,
                    'field' => $field,
                    'foreign_key' => (string)$entityId,
                    'content' => $translation
                ]);
            }
            
            $this->logger->info('Translation saved', [
                'entity' => $entityClass,
                'field' => $field,
                'locale' => $locale,
                'entity_id' => $entityId
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to save translation', [
                'error' => $e->getMessage(),
                'entity' => get_class($entity),
                'field' => $field,
                'locale' => $locale
            ]);
        }
    }

    /**
     * API kullanım bilgilerini al
     */
    public function getUsageInfo(): ?array
    {
        try {
            $deeplApiKey = $this->params->get('deepl_api_key');
            if (!$deeplApiKey) {
                return null;
            }

            $response = $this->httpClient->request('GET', self::DEEPL_BASE_URL . '/usage', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $deeplApiKey,
                ],
                'timeout' => 5
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get DeepL usage info', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Desteklenen dilleri kontrol et
     */
    public function getSupportedLocales(): array
    {
        return array_keys(self::LOCALE_TO_DEEPL);
    }
}
