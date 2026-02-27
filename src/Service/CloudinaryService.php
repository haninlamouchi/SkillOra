<?php

namespace App\Service;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    private $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(
            $_ENV['CLOUDINARY_URL']
        );
    }

    public function uploadImage($file)
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath()
        );

        return $result['secure_url'];
    }
    public function uploadVideo($file)
    {
        // Remove PHP time limit for this request — video uploads can take minutes
        set_time_limit(0);

        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'resource_type' => 'video',
                'chunk_size'    => 6000000, // 6 MB chunks — faster & avoids timeouts
                'timeout'       => 600,     // 10 min curl timeout
            ]
        );

        return $result['secure_url'];
    }
}