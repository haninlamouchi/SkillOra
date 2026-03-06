<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FaceRecognitionService
{
    private string $pythonApiUrl = 'http://127.0.0.1:5001';

    public function __construct(private HttpClientInterface $httpClient) {}

    public function compareFaces(string $capturedImageBase64, string $referencePhotoPath): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->pythonApiUrl . '/compare', [
                'json' => [
                    'captured_image' => $capturedImageBase64,
                    'reference_path' => $referencePhotoPath,
                ],
                'timeout' => 15,
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            return [
                'success' => false,
                'match'   => false,
                'message' => 'Python API unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function isApiAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->pythonApiUrl . '/health', [
                'timeout' => 3,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}