<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Throwable;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct(string $cloudinaryUrl)
    {
        $this->cloudinary = new Cloudinary($cloudinaryUrl);
    }

    public function uploadImage(\Symfony\Component\HttpFoundation\File\UploadedFile $file): string
    {
        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                ['folder' => 'skilora/formations']
            );

            return $result['secure_url'] ?? throw new \RuntimeException('Cloudinary did not return a URL.');
        } catch (Throwable $e) {
            throw new \RuntimeException('Upload image Cloudinary failed: ' . $e->getMessage(), 0, $e);
        }
    }
    public function uploadVideo(\Symfony\Component\HttpFoundation\File\UploadedFile $file): ?string
    {
        set_time_limit(0);

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'resource_type' => 'video',
                ]
            );

            return $result['secure_url'] ?? null;
        } catch (Throwable $exception) {
            throw new \RuntimeException('Upload vidéo Cloudinary échoué: ' . $exception->getMessage(), 0, $exception);
        }
    }
}