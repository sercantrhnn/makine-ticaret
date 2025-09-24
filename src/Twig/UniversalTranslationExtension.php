<?php

namespace App\Twig;

use App\Service\TranslationService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UniversalTranslationExtension extends AbstractExtension
{
    public function __construct(
        private TranslationService $translationService,
        private TranslatorInterface $translator
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('auto_translate', [$this, 'autoTranslate']),
        ];
    }

    /**
     * Akıllı çeviri: Önce YAML'den bak, yoksa API'den çevir ve kaydet
     */
    public function autoTranslate(string $text, string $locale, string $sourceLocale = 'tr'): string
    {
        // Türkçe ise orijinal döndür
        if ($locale === $sourceLocale) {
            return $text;
        }

        // 1. Önce translation key olarak dene (YAML'de var mı?)
        $translationKey = $this->generateKeyFromText($text);
        $yamlTranslation = $this->translator->trans($translationKey, [], 'messages', $locale);
        
        // YAML'de bulunduysa ve orijinal key'den farklıysa kullan
        if ($yamlTranslation !== $translationKey) {
            return $yamlTranslation;
        }

        // 2. Direct text key olarak dene
        $directTranslation = $this->translator->trans($text, [], 'messages', $locale);
        if ($directTranslation !== $text) {
            return $directTranslation;
        }

        // 3. Cache'de var mı kontrol et
        $cachedTranslation = $this->getCachedTranslation($text, $locale);
        if ($cachedTranslation) {
            return $cachedTranslation;
        }

        // 4. API'den çevir ve cache'le
        try {
            $apiTranslation = $this->translationService->translateText($text, $locale, $sourceLocale);
            if ($apiTranslation && $apiTranslation !== $text) {
                $this->cacheTranslation($text, $locale, $apiTranslation);
                return $apiTranslation;
            }
        } catch (\Exception $e) {
            error_log("Auto translation failed for '{$text}': " . $e->getMessage());
        }

        // 5. Fallback: orijinal metni döndür
        return $text;
    }

    private function generateKeyFromText(string $text): string
    {
        // Yaygın metinler için otomatik key oluştur
        $keyMap = [
            'Ürünler' => 'nav.products',
            'Firmalar' => 'nav.companies', 
            'Kategoriler' => 'nav.categories',
            'İhaleler' => 'nav.bids',
            'Giriş Yap' => 'nav.login',
            'Kayıt Ol' => 'nav.register',
            'Çıkış Yap' => 'nav.logout',
            'Ana Sayfa' => 'nav.home',
            'Mesajlarım' => 'user.messages',
            'Alım Talebi Oluştur' => 'purchase_request.create_new',
            'İhale Oluştur' => 'bid.create',
            'Detayları Görüntüle' => 'products.view_details',
            'Ürün Detayı' => 'products.product_detail',
            'Tüm Ürünler' => 'products.all_products',
            'Sırala' => 'sort.label',
            'Ara' => 'hero.search_button',
            'İletişime Geç' => 'purchase_request.contact',
            'Satılık' => 'products.for_sale',
            'Kiralık' => 'products.for_rent',
            'Sıfır' => 'products.new',
            'İkinci El' => 'products.used',
            'Ürün, marka veya firma ara...' => 'hero.search_placeholder',
            'Ürün Videoları' => 'products.videos',
            'Ürün Özellikleri' => 'products.specifications',
            'Açıklama' => 'products.description',
            'İletişim' => 'footer.contact',
            'Marka' => 'products.brand',
            'Model' => 'products.model',
            'Tip' => 'products.type',
            'Kategori' => 'nav.categories',
            'Durum' => 'products.status',
            'Menşei' => 'products.origin',
            'Telefon' => 'contact.phone',
            'E-posta Gönder' => 'contact.send_email',
            'Fiyat için iletişime geçin' => 'products.contact_for_price',
        ];

        return $keyMap[$text] ?? strtolower(str_replace([' ', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['_', 'i', 'g', 'u', 's', 'o', 'c'], $text));
    }

    private function getCachedTranslation(string $text, string $locale): ?string
    {
        // Simple file-based cache (Redis de olabilir)
        $cacheKey = md5($text . '_' . $locale);
        $cacheFile = sys_get_temp_dir() . "/translations/{$cacheKey}.txt";
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) { // 1 saat cache
            return file_get_contents($cacheFile);
        }
        
        return null;
    }

    private function cacheTranslation(string $text, string $locale, string $translation): void
    {
        $cacheKey = md5($text . '_' . $locale);
        $cacheDir = sys_get_temp_dir() . "/translations";
        $cacheFile = $cacheDir . "/{$cacheKey}.txt";
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, $translation);
    }
}
