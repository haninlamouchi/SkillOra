<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        string $geminiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
    }

    public function recommanderClubs(string $interets, array $clubs): array
    {
        $listeClubs = '';
        foreach ($clubs as $club) {
            $listeClubs .= '- ID:' . $club->getId() .
                           ' | Nom: ' . $club->getNom() .
                           ' | Description: ' . $club->getDescription() . "\n";
        }

        $prompt = "Tu es un assistant de recommandation de clubs éducatifs.
Voici les intérêts de l'utilisateur : {$interets}

Voici la liste des clubs disponibles :
{$listeClubs}

Retourne UNIQUEMENT une liste d'IDs des clubs les plus adaptés aux intérêts de l'utilisateur, 
séparés par des virgules, sans aucun texte supplémentaire.
Exemple de réponse attendue : 1,3,5";

        try {
            $response = $this->httpClient->request('POST',
                'https://openrouter.ai/api/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                            'model' => 'liquid/lfm-2.5-1.2b-instruct:free', 'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ]
                    ]
                ]
            );
           
            $data = $response->toArray(false);

            if (isset($data['error'])) {
                return [];
            }

            $text = $data['choices'][0]['message']['content'] ?? '';


        } catch (\Exception $e) {
                return [];
        }

        $ids = array_map('trim', explode(',', $text));
        $ids = array_filter($ids, 'is_numeric');

        $recommandes = [];
        foreach ($ids as $id) {
            foreach ($clubs as $club) {
                if ($club->getId() == $id) {
                    $recommandes[] = $club;
                    break;
                }
            }
        }
        


        return $recommandes;
    }
}