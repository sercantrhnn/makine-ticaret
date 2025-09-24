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
            'Ana Kategoriler' => 'categories.main_categories',
            'Tüm Firmalar' => 'companies.all_companies',
            'Ada göre A → Z' => 'sort.name_asc',
            'Ada göre Z → A' => 'sort.name_desc',
            'Tüm İller' => 'location.all_cities',
            'Firma adı, faaliyet alanı ara...' => 'companies.search_placeholder',
            'firma bulundu' => 'companies.found',
            'Firma bulunamadı' => 'companies.not_found',
            'için firma arama sonuçları' => 'companies.search_results_for',
            'Güvenilir firma rehberi. Sanayi sektöründe faaliyet gösteren firmalar ve iletişim bilgileri.' => 'companies.description',
            'Hakkımızda' => 'companies.about_us',
            'Firma Bilgileri' => 'companies.company_info',
            'Kuruluş Yılı' => 'companies.founded_year',
            'Ticaret Sicil No' => 'companies.trade_registry_no',
            'Website' => 'companies.website',
            'Adres' => 'companies.address',
            'Haritada Görüntüle' => 'companies.view_on_map',
            'Hemen Ara' => 'companies.call_now',
            'Katalog İndir' => 'companies.download_catalog',
            'Sertifika İndir' => 'companies.download_certificate',
            'Harita' => 'companies.map',
            'Firma Ürünleri' => 'companies.products',
            'ürün' => 'products.product',
            'Henüz Ürün Bulunmuyor' => 'companies.no_products_yet',
            'Bu firma henüz hiç ürün eklememış.' => 'companies.no_products_added',
            'Tüm ürün kategorileri. Makine, sanayi, tarım ve inşaat sektörlerine yönelik kapsamlı kategori rehberi.' => 'categories.description',
            'Ürün Kategorileri' => 'categories.title',
            'Aradığınız ürünü kolayca bulabilmeniz için düzenlenmiş kapsamlı kategori yapımız' => 'categories.subtitle',
            'alt kategori' => 'categories.subcategory',
            'Alt kategori bulunmuyor' => 'categories.no_subcategories',
            'Kategori Bulunamadı' => 'categories.not_found',
            'Henüz hiç kategori eklenmemiş.' => 'categories.no_categories_added',
            'Onaylanan ve aktif durumdaki ihaleleri keşfedin.' => 'bids.description',
            'İhale, ürün, firma adında arayın...' => 'bids.search_placeholder',
            'için' => 'search.for',
            'ihale bulundu' => 'bids.found',
            'Toplam' => 'search.total',
            'onaylı ihale' => 'bids.approved',
            'Aramayı Temizle' => 'search.clear',
            'Aktif İhale' => 'bids.active',
            'Onaylı İhale' => 'bids.approved_bid',
            'Firma' => 'companies.company',
            'Başlangıç Fiyatı' => 'bids.starting_price',
            'Bitiş Tarihi' => 'bids.end_date',
            'İhale Tarihi' => 'bids.bid_date',
            'İhale Detayı' => 'bids.details',
            'Arama Sonucu Bulunamadı' => 'search.no_results',
            'araması için hiçbir ihale bulunamadı.' => 'bids.no_search_results',
            'Tüm İhaleleri Görüntüle' => 'bids.view_all',
            'Henüz İhale Bulunmuyor' => 'bids.no_bids_yet',
            'Şu anda onaylanmış ihale bulunmamaktadır.' => 'bids.no_approved_bids',
            'Ana Sayfaya Dön' => 'nav.back_to_home',
            'Teknik Detaylar' => 'products.technical_details',
            'Ürün Görselleri' => 'products.product_images',
            'İhale Açıklaması' => 'bids.description',
            'İhale Özeti' => 'bids.summary',
            'Kendi ihalenize teklif veremezsiniz.' => 'bids.cannot_bid_own',
            'Teklif Ver' => 'bids.make_offer',
            'Giriş yaparak teklif verin' => 'bids.login_to_bid',
            'Başlangıç Tarihi' => 'bids.start_date',
            'Süresi Dolmuş' => 'bids.expired',
            'Kalan Süre' => 'bids.time_left',
            'gün' => 'time.days',
            'saat' => 'time.hours',
            'Bu ihale aktif durumdadır.' => 'bids.is_active',
            'Bu ihale onaylanmıştır.' => 'bids.is_approved',
            'İhale Veren Firma' => 'bids.bidding_company',
            'E-posta' => 'contact.email',
            'Firma Detayı' => 'companies.company_details',
            'Firmanın Diğer Ürünleri' => 'companies.other_products',
            'Tüm Ürünleri Gör' => 'companies.view_all_products',
            'ürün' => 'products.product',
            'Alım Talebi İletişim' => 'purchase_request.contact',
            'Açıklama yok' => 'general.no_description',
            'Talep Tarihi' => 'purchase_request.request_date',
            'Mesaj Geçmişi' => 'messages.history',
            'Siz' => 'user.you',
            'Talep Sahibi' => 'purchase_request.requester',
            'Henüz mesaj yok.' => 'messages.no_messages',
            'Mesaj Gönder' => 'messages.send_message',
            'Talep Gönder' => 'purchase_request.send_request',
            'Panele Git' => 'nav.go_to_panel',
            'Sayfalarımız' => 'footer.our_pages',
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
