<?php

namespace App\Twig;

use App\Service\LocaleDetectionService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LocaleExtension extends AbstractExtension
{
    public function __construct(
        private LocaleDetectionService $localeService,
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('supported_locales', [$this, 'getSupportedLocales']),
            new TwigFunction('locale_display_name', [$this, 'getLocaleDisplayName']),
            new TwigFunction('locale_switch_url', [$this, 'getLocaleSwitchUrl']),
        ];
    }

    public function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getLocale() : 'tr';
    }

    public function getSupportedLocales(): array
    {
        return $this->localeService->getSupportedLocales();
    }

    public function getLocaleDisplayName(string $locale): string
    {
        return $this->localeService->getLocaleDisplayName($locale);
    }

    public function getLocaleSwitchUrl(string $locale): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return "/?_locale={$locale}";
        }

        // Mevcut URL'i al
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getPathInfo();
        $queryParams = $request->query->all();
        
        // Locale parametresini ekle/güncelle
        $queryParams['_locale'] = $locale;
        
        // Query string oluştur
        $queryString = http_build_query($queryParams);
        
        return $baseUrl . ($queryString ? '?' . $queryString : '');
    }
}
