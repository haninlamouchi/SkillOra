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
        // Préparer la liste des clubs pour Gemini
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

        $response = $this->httpClient->request('POST',
           "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=". $this->apiKey,
            [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]
            ]
        );

        $data = $response->toArray();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Parser les IDs retournés
        $ids = array_map('trim', explode(',', $text));
        $ids = array_filter($ids, 'is_numeric');

        // Retourner les clubs recommandés dans l'ordre
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