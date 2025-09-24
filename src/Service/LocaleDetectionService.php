<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class LocaleDetectionService
{
    private const SUPPORTED_LOCALES = ['tr', 'en', 'de', 'ar'];
    private const DEFAULT_LOCALE = 'tr';
    
    // IP'ye göre ülke kodu eşleştirmesi
    private const COUNTRY_TO_LOCALE = [
        'TR' => 'tr',
        'US' => 'en',
        'GB' => 'en', 
        'CA' => 'en',
        'AU' => 'en',
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',
        'SA' => 'ar',
        'AE' => 'ar',
        'QA' => 'ar',
        'KW' => 'ar',
        'EG' => 'ar',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function detectLocaleFromRequest(Request $request): string
    {
        // 1. Query parameter ile gelen locale (EN YÜKSEK ÖNCELİK)
        $queryLocale = $request->query->get('_locale');
        if ($queryLocale && in_array($queryLocale, self::SUPPORTED_LOCALES)) {
            $request->getSession()->set('_locale', $queryLocale);
            // URL'den gelen locale'i hemen request'e set et
            $request->setLocale($queryLocale);
            return $queryLocale;
        }

        // 2. Session'da saklı locale kontrolü
        $sessionLocale = $request->getSession()->get('_locale');
        if ($sessionLocale && in_array($sessionLocale, self::SUPPORTED_LOCALES)) {
            return $sessionLocale;
        }

        // 3. IP adresinden locale tespiti (sadece localhost değilse)
        $clientIp = $request->getClientIp();
        if ($clientIp && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
            $ipLocale = $this->detectLocaleFromIp($request);
            if ($ipLocale) {
                $request->getSession()->set('_locale', $ipLocale);
                return $ipLocale;
            }
        }

        // 4. Browser dilinden locale tespiti (sadece Türkçe değilse)
        $browserLocale = $this->detectLocaleFromAcceptLanguage($request);
        if ($browserLocale && $browserLocale !== 'tr') {
            $request->getSession()->set('_locale', $browserLocale);
            return $browserLocale;
        }

        // 5. Varsayılan locale (Türkçe)
        $request->getSession()->set('_locale', self::DEFAULT_LOCALE);
        return self::DEFAULT_LOCALE;
    }

    private function detectLocaleFromIp(Request $request): ?string
    {
        try {
            $clientIp = $request->getClientIp();
            
            // Localhost kontrolü
            if (!$clientIp || $clientIp === '127.0.0.1' || $clientIp === '::1') {
                return self::DEFAULT_LOCALE;
            }

            // ipapi.co servisi ile IP lokasyonu (ücretsiz 1000 request/gün)
            $response = $this->httpClient->request('GET', "https://ipapi.co/{$clientIp}/country/", [
                'timeout' => 3,
                'headers' => [
                    'User-Agent' => 'Makine-Ticaret/1.0'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $countryCode = trim($response->getContent());
                
                if (isset(self::COUNTRY_TO_LOCALE[$countryCode])) {
                    $this->logger->info('Locale detected from IP', [
                        'ip' => $clientIp,
                        'country' => $countryCode,
                        'locale' => self::COUNTRY_TO_LOCALE[$countryCode]
                    ]);
                    
                    return self::COUNTRY_TO_LOCALE[$countryCode];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('IP locale detection failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
        }

        return null;
    }

    private function detectLocaleFromAcceptLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->headers->get('Accept-Language');
        if (!$acceptLanguage) {
            return null;
        }

        // Accept-Language header'ından dil kodlarını çıkar
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $langCode = strtolower(substr($parts[0], 0, 2));
            $languages[] = $langCode;
        }

        // Desteklenen diller ile eşleştir
        foreach ($languages as $lang) {
            if (in_array($lang, self::SUPPORTED_LOCALES)) {
                return $lang;
            }
        }

        return null;
    }

    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public function getLocaleDisplayName(string $locale): string
    {
        $names = [
            'tr' => 'Türkçe',
            'en' => 'English',
            'de' => 'Deutsch',
            'ar' => 'العربية'
        ];

        return $names[$locale] ?? $locale;
    }
}
