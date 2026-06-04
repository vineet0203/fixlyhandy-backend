<?php

use Illuminate\Support\Str;

if (!function_exists('generateFileUrl')) {
    function generateFileUrl(?string $path): ?string
    {
        if (!$path) return null;

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));
        return url("api/v.1/files/{$encodedPath}");
    }
}
