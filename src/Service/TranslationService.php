<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private const URL = 'https://api.mymemory.translated.net/get';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function traduire(string $texte, string $cible = 'en'): string
    {
        try {
            $response = $this->httpClient->request('GET', self::URL, [
                'query' => [
                    'q'        => $texte,
                    'langpair' => 'fr|' . strtolower($cible),  // ← fr à la place de auto
                ],
            ]);

            $data = $response->toArray();
            return $data['responseData']['translatedText'] ?? $texte;

        } catch (\Exception $e) {
            error_log('[Translation] Erreur MyMemory : ' . $e->getMessage());
            return $texte;
        }
    }
}