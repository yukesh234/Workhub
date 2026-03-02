<?php
// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use Cloudinary\Cloudinary;


require_once __DIR__ . '/../../config/cloudinary.php';

class CloudinaryService {
    private Cloudinary $cloudinary;

    public function __construct() {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => \Config::get('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => \Config::get('CLOUDINARY_API_KEY'),
                'api_secret' => \Config::get('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    public function uploadImage(string $FilePath, string $folder = 'workhub'): array {
        try {
            $result = $this->cloudinary->uploadApi()->upload($FilePath, [
                'folder' => $folder
            ]);
            return [
                'url' => $result['secure_url'],
                'public_id' => $result['public_id']
            ];
        } catch (\Exception $e) {
            throw new \Exception("Upload failed: " . $e->getMessage());
        }
    }

    public function deleteImage(string $publicid): bool {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicid);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            throw new \Exception("Deletion from the cloud failed: " . $e->getMessage());
        }
    }
}
