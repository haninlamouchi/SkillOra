<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ModerationService
{
    private const URL = 'https://api.sightengine.com/1.0/text/check.json';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $sightengineUser,
        private string $sightengineSecret
    ) {}

    public function estInapproprie(string $texte): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::URL, [
                'query' => [
                    'text'       => $texte,
                    'lang'       => 'fr,en',
                    'categories' => 'profanity,violence,self-harm,extremism',
                    'mode'       => 'rules',
                    'api_user'   => $this->sightengineUser,
                    'api_secret' => $this->sightengineSecret,
                ],
            ]);

            $data = $response->toArray();

            // Bloqué si l'une des catégories a des correspondances
            return !empty($data['profanity']['matches'])
                || !empty($data['violence']['matches'])
                || !empty($data['self-harm']['matches'])
                || !empty($data['extremism']['matches']);

        } catch (\Exception $e) {
            error_log('[Moderation] Erreur Sightengine : ' . $e->getMessage());
            return false;
        }
    }
}