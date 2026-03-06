<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class PublicationAIService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $groqApiKey = '',
    ) {}

    /**
     * Generate BOTH title and description from keywords using Groq AI
     * 
     * @param string $keywords Space or comma-separated keywords
     * @param string $tone 'formal', 'casual', 'technical', 'friendly' (default: 'friendly')
     * @param int $maxLength Maximum length of generated description (default: 500)
     * 
     * @return array ['success' => bool, 'title' => string, 'description' => string, 'error' => string|null]
     */
    public function generateTitleAndDescription(
        string $keywords,
        string $tone = 'friendly',
        int $maxLength = 500
    ): array {
        try {
            if (empty($this->groqApiKey)) {
                $this->logger->warning('[PublicationAI] Groq API key not configured');
                return [
                    'success' => false,
                    'title' => '',
                    'description' => '',
                    'error' => 'Service IA non configuré',
                ];
            }

            $keywords = trim($keywords);
            if (empty($keywords)) {
                return [
                    'success' => false,
                    'title' => '',
                    'description' => '',
                    'error' => 'Veuillez entrer au moins un mot-clé',
                ];
            }

            $this->logger->info('[PublicationAI] Generating title and description', [
                'keywords' => $keywords,
                'tone' => $tone,
            ]);

            // Build prompt for title + description
            $prompt = $this->buildTitleAndDescriptionPrompt($keywords, $tone, $maxLength);

            // Call Groq API
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'max_tokens' => 800,
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en rédaction de contenu éducatif et professionnel. '
                                . 'Tu génères des titres accrocheurs et des descriptions claires pour des publications '
                                . 'sur un forum d\'apprentissage collaboratif. Tu DOIS répondre uniquement avec un objet JSON valide '
                                . 'au format: {"title": "le titre ici", "description": "la description ici"}. '
                                . 'Ne mets RIEN d\'autre dans ta réponse, seulement le JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('[PublicationAI] Groq API error', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(false),
                ]);

                return [
                    'success' => false,
                    'title' => '',
                    'description' => '',
                    'error' => 'Erreur lors de la génération IA',
                ];
            }

            $data = json_decode($response->getContent(), true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Try to parse JSON from response
            // Clean potential markdown code blocks
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim(is_string($content) ? $content : '');

            $parsed = json_decode($content, true);
            
            if ($parsed && isset($parsed['title']) && isset($parsed['description'])) {
                return [
                    'success' => true,
                    'title' => trim($parsed['title']),
                    'description' => trim($parsed['description']),
                    'error' => null,
                ];
            }

            // Fallback: couldn't parse JSON
            $this->logger->warning('[PublicationAI] Could not parse JSON response', ['content' => $content]);
            
            return [
                'success' => false,
                'title' => '',
                'description' => '',
                'error' => 'Format de réponse invalide de l\'IA',
            ];

        } catch (\Exception $e) {
            $this->logger->error('[PublicationAI] Exception in generateTitleAndDescription', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'title' => '',
                'description' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a publication description from keywords using Groq AI
     * 
     * @param string $keywords Space or comma-separated keywords
     * @param string|null $title Optional publication title
     * @param string $tone 'formal', 'casual', 'technical', 'friendly' (default: 'friendly')
     * @param int $maxLength Maximum length of generated description (default: 500)
     * 
     * @return array ['success' => bool, 'description' => string, 'error' => string|null]
     */
    public function generateDescription(
        string $keywords,
        ?string $title = null,
        string $tone = 'friendly',
        int $maxLength = 500
    ): array {
        try {
            if (empty($this->groqApiKey)) {
                $this->logger->warning('[PublicationAI] Groq API key not configured');
                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Service IA non configuré',
                ];
            }

            $keywords = trim($keywords);
            if (empty($keywords)) {
                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Veuillez entrer au moins un mot-clé',
                ];
            }

            $this->logger->info('[PublicationAI] Generating description', [
                'keywords' => $keywords,
                'title' => $title,
                'tone' => $tone,
            ]);

            // Prepare the prompt
            $prompt = $this->buildPrompt($keywords, $title, $tone, $maxLength);

            // Call Groq API
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'max_tokens' => 600,
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en rédaction de contenu éducatif et professionnel. '
                                . 'Tu génères des descriptions claires, engageantes et structurées pour des publications '
                                . 'sur un forum d\'apprentissage collaboratif. Sois concis mais informatif.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('[PublicationAI] Groq API error', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                ]);

                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Erreur lors de la génération IA',
                ];
            }

            $data = json_decode($response->getContent(), true);
            $description = $data['choices'][0]['message']['content'] ?? '';

            if (empty($description)) {
                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Réponse vide de l\'IA',
                ];
            }

            // Clean up the description
            $description = trim($description);
            
            // Remove markdown code blocks if any
            $description = preg_replace('/```[\w]*\n?/m', '', $description) ?? '';
            
            // Limit length if needed
            if (strlen($description) > $maxLength) {
                $description = substr($description, 0, $maxLength) . '...';
            }

            $this->logger->info('[PublicationAI] Description generated successfully', [
                'length' => strlen($description ?? ''),
            ]);

            return [
                'success' => true,
                'description' => $description,
                'error' => null,
            ];

        } catch (\Throwable $e) {
            $this->logger->error('[PublicationAI] Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'description' => '',
                'error' => 'Erreur serveur: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enrich/improve an existing publication description using AI
     */
    public function enhanceDescription(
        string $description,
        string $keywords,
        string $tone = 'friendly'
    ): array {
        try {
            if (empty($this->groqApiKey)) {
                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Service IA non configuré',
                ];
            }

            $prompt = "Améliore et enrichis cette description pour une publication forum sur ces mots-clés: {$keywords}\n\n"
                . "Description actuelle:\n{$description}\n\n"
                . "Instructions:\n"
                . "- Garde le style {$tone}\n"
                . "- Améliore la clarté et l'engagement\n"
                . "- Ajoute des détails pertinents basés sur les mots-clés\n"
                . "- Garde une longueur similaire ou légèrement plus longue\n"
                . "- Rends-la plus structurée et lisible";

            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'max_tokens' => 600,
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'description' => '',
                    'error' => 'Erreur lors de l\'amélioration',
                ];
            }

            $data = json_decode($response->getContent(), true);
            $enhanced = $data['choices'][0]['message']['content'] ?? '';

            return [
                'success' => true,
                'description' => trim($enhanced),
                'error' => null,
            ];

        } catch (\Throwable $e) {
            $this->logger->error('[PublicationAI] Enhancement error: ' . $e->getMessage());
            return [
                'success' => false,
                'description' => '',
                'error' => 'Erreur lors de l\'amélioration',
            ];
        }
    }

    /**
     * Generate suggestions for what to write about based on keywords
     */
    public function generateWritingSuggestions(string $keywords): array
    {
        try {
            if (empty($this->groqApiKey)) {
                return [
                    'success' => false,
                    'suggestions' => [],
                    'error' => 'Service IA non configuré',
                ];
            }

            $prompt = "Basé sur ces mots-clés: {$keywords}\n\n"
                . "Donne-moi 3-4 suggestions courtes (1-2 phrases max) de sujets d'articles ou de points clés à couvrir.\n"
                . "Format: Une suggestion par ligne, sans numérotation.";

            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'max_tokens' => 300,
                    'temperature' => 0.8,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'suggestions' => [],
                    'error' => 'Erreur lors de la génération',
                ];
            }

            $data = json_decode($response->getContent(), true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                return [
                    'success' => false,
                    'suggestions' => [],
                    'error' => 'Réponse vide',
                ];
            }

            // Parse suggestions (split by newlines)
            $suggestions = array_filter(
                array_map('trim', explode("\n", $content)),
                fn($line) => !empty($line) && strlen($line) > 5
            );

            return [
                'success' => true,
                'suggestions' => array_values($suggestions),
                'error' => null,
            ];

        } catch (\Throwable $e) {
            $this->logger->error('[PublicationAI] Suggestions error: ' . $e->getMessage());
            return [
                'success' => false,
                'suggestions' => [],
                'error' => 'Erreur serveur',
            ];
        }
    }

    /**
     * Build the prompt for title + description generation
     */
    private function buildTitleAndDescriptionPrompt(
        string $keywords,
        string $tone,
        int $maxLength
    ): string {
        $toneInstructions = match($tone) {
            'formal' => 'Formelle, professionnelle et académique',
            'casual' => 'Décontractée, friendly et accessible',
            'technical' => 'Technique, précise avec des détails professionnels',
            'friendly' => 'Amicale, engageante et facile à comprendre',
            default => 'Amicale et engageante',
        };

        $prompt = "Génère un titre ET une description pour une publication sur un forum d'apprentissage collaboratif.\n\n"
            . "Mots-clés: {$keywords}\n\n"
            . "Instructions:\n"
            . "- Ton: {$toneInstructions}\n"
            . "- Longueur de la description: maximum {$maxLength} caractères\n"
            . "- Le titre doit être accrocheur et court (max 80 caractères)\n"
            . "- La description doit être engageante, informative et structurée\n"
            . "- Assure-toi que les mots-clés sont bien couverts\n\n"
            . "Tu DOIS répondre au format JSON exact suivant (aucun autre texte):\n"
            . '{"title": "ton titre ici", "description": "ta description ici"}';

        return $prompt;
    }

    /**
     * Build the prompt for description generation
     */
    private function buildPrompt(
        string $keywords,
        ?string $title,
        string $tone,
        int $maxLength
    ): string {
        $toneInstructions = match($tone) {
            'formal' => 'Formelle, professionnelle et académique',
            'casual' => 'Décontractée, friendly et accessible',
            'technical' => 'Technique, précise avec des détails professionnels',
            'friendly' => 'Amicale, engageante et facile à comprendre',
            default => 'Amicale et engageante',
        };

        $prompt = "Génère une description de publication pour un forum d'apprentissage collaboratif.\n\n"
            . "Mots-clés: {$keywords}\n";

        if ($title) {
            $prompt .= "Titre: {$title}\n";
        }

        $prompt .= "\nInstructions:\n"
            . "- Ton: {$toneInstructions}\n"
            . "- Longueur: maximum {$maxLength} caractères\n"
            . "- Structure: Introduction claire + détails pertinents + conclusion/appel à l'action\n"
            . "- Assure-toi que la description couvre les principaux mots-clés\n"
            . "- Rends-la engageante pour une communauté d'apprentissage\n"
            . "- Sois précis et informatif\n\n"
            . "Génère seulement la description, sans titre ni explications supplémentaires.";

        return $prompt;
    }

    /**
     * Check if Groq API is properly configured
     */
    public function isAvailable(): bool
    {
        return !empty($this->groqApiKey);
    }
}
