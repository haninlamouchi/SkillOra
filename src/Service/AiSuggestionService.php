<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiSuggestionService
{
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {}

    public function suggererReponseMembre(string $publicationContenu, string $commentaireContenu): string
    {
        $prompt = "Tu es un assistant pour un forum étudiant.
Voici le contenu d'une publication : \"$publicationContenu\"
Un membre a posté ce commentaire : \"$commentaireContenu\"
Propose une réponse courte, polie et constructive en français (2-3 phrases maximum).
Réponds uniquement avec la suggestion, sans explication.";

        return $this->appelerGroq($prompt);
    }

    public function suggererReponseResponsable(string $publicationContenu, string $commentaireContenu): string
    {
        $prompt = "Tu es un responsable de club étudiant professionnel.
Voici le contenu d'une publication : \"$publicationContenu\"
Un membre a posté ce commentaire : \"$commentaireContenu\"
Propose une réponse officielle, bienveillante et professionnelle en français (2-4 phrases).
Réponds uniquement avec la suggestion, sans explication.";

        return $this->appelerGroq($prompt);
    }

    private function appelerGroq(string $prompt): string
    {
        try {
            $response = $this->httpClient->request('POST', self::URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'max_tokens'  => 300,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Aucune suggestion disponible.';

        } catch (\Exception $e) {
            error_log('[AiSuggestion] Erreur Groq : ' . $e->getMessage());
            return 'Aucune suggestion disponible.';
        }
    }
}