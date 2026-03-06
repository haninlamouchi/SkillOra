<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GroqService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $httpClient, 
        LoggerInterface $logger,
        string $groqApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $groqApiKey;
    }

    /**
     * Génère un challenge complet avec Groq AI
     */
    public function generateChallenge(array $params): ?array
    {
        $prompt = $this->buildPrompt($params);
        
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile', // Modèle gratuit et puissant
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en pédagogie et création de challenges techniques. Tu génères des challenges détaillés et structurés en français.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.8, // Créativité
                    'max_tokens' => 2000,
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return $this->parseResponse($data['choices'][0]['message']['content']);
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Groq API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(array $params): string
    {
        $categorie = $params['categorie'] ?? 'Développement';
        $niveau = $params['niveau'] ?? 'Intermédiaire';
        $technologies = $params['technologies'] ?? 'Symfony';
        $duree = $params['duree'] ?? '2 semaines';
        $theme = $params['theme'] ?? '';

        $prompt = "Génère un challenge de programmation avec les caractéristiques suivantes :

Catégorie : {$categorie}
Niveau : {$niveau}
Technologies : {$technologies}
Durée : {$duree}";

        if ($theme) {
            $prompt .= "\nThème : {$theme}";
        }

        $prompt .= "\n\nGénère le challenge au format JSON strictement suivant :
{
    \"titre\": \"Titre créatif et accrocheur du challenge\",
    \"description\": \"Description détaillée du projet (200-300 mots)\",
    \"objectifs\": [
        \"Objectif pédagogique 1\",
        \"Objectif pédagogique 2\",
        \"Objectif pédagogique 3\"
    ],
    \"fonctionnalites\": [
        \"Fonctionnalité requise 1\",
        \"Fonctionnalité requise 2\",
        \"Fonctionnalité requise 3\"
    ],
    \"livrables\": [
        \"Livrable 1\",
        \"Livrable 2\"
    ],
    \"criteres_evaluation\": {
        \"Qualité du code\": \"30%\",
        \"Fonctionnalités\": \"40%\",
        \"Design\": \"20%\",
        \"Documentation\": \"10%\"
    }
}

IMPORTANT : Réponds UNIQUEMENT avec le JSON, sans texte avant ou après, sans balises markdown.";

        return $prompt;
    }

    /**
     * Parse la réponse JSON de l'IA
     */
    private function parseResponse(string $content): ?array
    {
        // Nettoyer le contenu (enlever markdown si présent)
        $content = trim($content);
        $content = preg_replace('/```json\s*/i', '', $content) ?? '';
        $content = preg_replace('/```\s*$/i', '', $content) ?? '';
        $content = trim($content);

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (\JsonException $e) {
            $this->logger->error('JSON Parse Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formate le challenge pour l'affichage
     */
    public function formatChallengeForDisplay(array $data): string
    {
        $output = "# {$data['titre']}\n\n";
        $output .= "## Description\n{$data['description']}\n\n";
        
        $output .= "## Objectifs Pédagogiques\n";
        foreach ($data['objectifs'] as $obj) {
            $output .= "• {$obj}\n";
        }
        
        $output .= "\n## Fonctionnalités Requises\n";
        foreach ($data['fonctionnalites'] as $fonc) {
            $output .= "• {$fonc}\n";
        }
        
        $output .= "\n## Livrables Attendus\n";
        foreach ($data['livrables'] as $liv) {
            $output .= "• {$liv}\n";
        }
        
        $output .= "\n## Critères d'Évaluation\n";
        foreach ($data['criteres_evaluation'] as $critere => $poids) {
            $output .= "• {$critere} : {$poids}\n";
        }
        
        return $output;
    }
}