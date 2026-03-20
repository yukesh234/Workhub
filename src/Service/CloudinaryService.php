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

    /**
     * Images (jpeg, png, gif, webp) → resource_type: image
     * PDFs also work under image (Cloudinary renders them as pages)
     * Everything else (docx, xlsx, zip, csv, txt…) → resource_type: raw
     */
    public function uploadImage(string $FilePath, string $folder = 'workhub'): array {
        try {
            $resourceType = $this->getResourceType($FilePath);

            $result = $this->cloudinary->uploadApi()->upload($FilePath, [
                'folder'        => $folder,
                'resource_type' => $resourceType,
            ]);

            return [
                'url'           => $result['secure_url'],
                'public_id'     => $result['public_id'],
                'resource_type' => $resourceType,   // return it so delete can use it later
            ];
        } catch (\Exception $e) {
            throw new \Exception("Upload failed: " . $e->getMessage());
        }
    }

    /**
     * Pass the resource_type that was used during upload.
     * Defaults to 'image' for backward compat (profile pics, org logos, etc.)
     */
    public function deleteImage(string $publicid, string $resourceType = 'image'): bool {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicid, [
                'resource_type' => $resourceType,
            ]);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            throw new \Exception("Deletion from the cloud failed: " . $e->getMessage());
        }
    }

    // ── Private helper ────────────────────────────────────────────────
    private function getResourceType(string $filePath): string {
        $mime = mime_content_type($filePath);

        // Cloudinary handles these natively under resource_type: image
        $imageCompatible = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',   // PDF works under image — Cloudinary renders pages
        ];

        return in_array($mime, $imageCompatible, true) ? 'image' : 'raw';
    }
}