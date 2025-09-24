<?php

namespace App\EventSubscriber;

use App\Service\LocaleDetectionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocaleDetectionService $localeDetectionService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ana request olup olmadığını kontrol et
        if (!$event->isMainRequest()) {
            return;
        }

        // Locale'i tespit et ve request'e set et
        $locale = $this->localeDetectionService->detectLocaleFromRequest($request);
        $request->setLocale($locale);
        
        // Gedmo listener'ına da locale'i set et
        $request->attributes->set('_locale', $locale);
        
        // Session'da locale'i güncelle (önemli!)
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }
    }
}
