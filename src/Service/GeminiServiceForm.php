<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiServiceForm
{
    private const GEMINI_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-1.5-flash-latest',
        'gemini-1.5-flash-8b-latest',
        'gemini-1.5-pro-latest',
    ];

    private const GROQ_MODELS = [
        'llama-3.3-70b-versatile',
        'llama3-8b-8192',
        'mixtral-8x7b-32768',
    ];

    private HttpClientInterface $client;
    private string $geminiKey;
    private string $groqKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client    = $client;
        $this->geminiKey = $_ENV['GEMINI_API_KEY_Form'] ?? '';
        $this->groqKey   = $_ENV['GROQ_API_KEY'] ?? '';
    }

    public function generateText(string $prompt): string
    {
        $lastError = null;

        // 1. Try all Gemini models
        foreach (self::GEMINI_MODELS as $model) {
            try {
                return $this->callGemini($model, $prompt);
            } catch (\RuntimeException $e) {
                $lastError = $e;
                if ($e->getCode() !== 429 && $e->getCode() !== 503 && $e->getCode() !== 404) {
                    throw $e;
                }
            }
        }

        // 2. Gemini fully saturated — fall back to Groq
        if ($this->groqKey !== '') {
            foreach (self::GROQ_MODELS as $model) {
                try {
                    return $this->callGroq($model, $prompt);
                } catch (\RuntimeException $e) {
                    $lastError = $e;
                    if ($e->getCode() !== 429 && $e->getCode() !== 503 && $e->getCode() !== 404) {
                        throw $e;
                    }
                }
            }
        }

        throw new \RuntimeException(
            'Tous les services IA sont actuellement saturés. Réessayez dans quelques minutes.',
            429,
            $lastError
        );
    }

    // ── Gemini ──────────────────────────────────────────────────────────────

    private function callGemini(string $model, string $prompt): string
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->geminiKey,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json'    => [
                        'contents'         => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 2048],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429 || $statusCode === 503 || $statusCode === 404) {
                throw new \RuntimeException("Gemini $model indisponible (HTTP $statusCode).", $statusCode);
            }
            if ($statusCode >= 400) {
                throw new \RuntimeException("Erreur Gemini ($model) : HTTP $statusCode.", $statusCode);
            }

            $data = $response->toArray();

            return $data['candidates'][0]['content']['parts'][0]['text']
                ?? throw new \RuntimeException("Réponse vide de Gemini $model.");

        } catch (\Symfony\Component\HttpClient\Exception\TransportException $e) {
            throw new \RuntimeException('Réseau Gemini inaccessible : ' . $e->getMessage(), 0, $e);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $code = $e->getResponse()->getStatusCode();
            throw new \RuntimeException("Erreur Gemini ($model) : HTTP $code.", $code, $e);
        }
    }

    // ── Groq (OpenAI-compatible) ─────────────────────────────────────────────

    private function callGroq(string $model, string $prompt): string
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'      => $model,
                        'max_tokens' => 2048,
                        'messages'   => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429 || $statusCode === 503 || $statusCode === 404) {
                throw new \RuntimeException("Groq $model indisponible (HTTP $statusCode).", $statusCode);
            }
            if ($statusCode >= 400) {
                throw new \RuntimeException("Erreur Groq ($model) : HTTP $statusCode.", $statusCode);
            }

            $data = $response->toArray();

            return $data['choices'][0]['message']['content']
                ?? throw new \RuntimeException("Réponse vide de Groq $model.");

        } catch (\Symfony\Component\HttpClient\Exception\TransportException $e) {
            throw new \RuntimeException('Réseau Groq inaccessible : ' . $e->getMessage(), 0, $e);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $code = $e->getResponse()->getStatusCode();
            throw new \RuntimeException("Erreur Groq ($model) : HTTP $code.", $code, $e);
        }
    }
}