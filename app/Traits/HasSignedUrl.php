<?php
// app/HasSignedUrl.php

namespace App\Traits;

use App\Services\File\SignedUrlService;

trait HasSignedUrl
{
    /**
     * Get signed URL for a file path
     *
     * @param string|null $path
     * @param int $expirationMinutes
     * @return string|null
     */
    protected function getSignedUrl(?string $path, int $expirationMinutes = 60): ?string
    {
        if (!$path) {
            return null;
        }

        $signedUrlData = app(SignedUrlService::class)
            ->generateTemporarySignedUrl($path, $expirationMinutes);

        return $signedUrlData['url'] ?? null;
    }

    /**
     * Get signed URL with full data
     *
     * @param string|null $path
     * @param int $expirationMinutes
     * @return array|null
     */
    protected function getSignedUrlData(?string $path, int $expirationMinutes = 60): ?array
    {
        if (!$path) {
            return null;
        }

        return app(SignedUrlService::class)
            ->generateTemporarySignedUrl($path, $expirationMinutes);
    }
}
