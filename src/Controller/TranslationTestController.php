<?php

namespace App\Controller;

use App\Service\TranslationService;
use App\Service\LocaleDetectionService;
use App\Repository\CategoriesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/translation-test')]
class TranslationTestController extends AbstractController
{
    #[Route('/api-status', name: 'translation_test_api_status', methods: ['GET'])]
    public function apiStatus(TranslationService $translationService): JsonResponse
    {
        $usage = $translationService->getUsageInfo();
        $supportedLocales = $translationService->getSupportedLocales();
        
        return $this->json([
            'deepl_usage' => $usage,
            'supported_locales' => $supportedLocales,
            'status' => $usage ? 'connected' : 'disconnected'
        ]);
    }

    #[Route('/test-translate', name: 'translation_test_translate', methods: ['POST'])]
    public function testTranslate(Request $request, TranslationService $translationService): JsonResponse
    {
        $text = $request->request->get('text', 'Merhaba Dünya!');
        $targetLocale = $request->request->get('target_locale', 'en');
        $sourceLocale = $request->request->get('source_locale', 'tr');

        $translatedText = $translationService->translateText($text, $targetLocale, $sourceLocale);

        return $this->json([
            'original' => $text,
            'translated' => $translatedText,
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'success' => $translatedText !== null
        ]);
    }

    #[Route('/locale-detection', name: 'translation_test_locale', methods: ['GET'])]
    public function localeDetection(Request $request, LocaleDetectionService $localeService): JsonResponse
    {
        $detectedLocale = $localeService->detectLocaleFromRequest($request);
        $supportedLocales = $localeService->getSupportedLocales();
        
        return $this->json([
            'detected_locale' => $detectedLocale,
            'supported_locales' => $supportedLocales,
            'client_ip' => $request->getClientIp(),
            'accept_language' => $request->headers->get('Accept-Language'),
            'session_locale' => $request->getSession()->get('_locale')
        ]);
    }

    #[Route('/', name: 'translation_test_page', methods: ['GET'])]
    public function testPage(CategoriesRepository $categoriesRepository): Response
    {
        $categories = $categoriesRepository->findBy([], ['id' => 'ASC'], 10);
        
        return $this->render('debug_translation.html.twig', [
            'categories' => $categories
        ]);
    }
}
