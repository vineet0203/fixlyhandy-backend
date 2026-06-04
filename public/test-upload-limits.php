<?php
// D:\Hustoro-Improved\new-backend\test-upload-limits.php

header('Content-Type: application/json');

// Set limits for this test
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '21M');

$currentLimits = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_sapi' => php_sapi_name(),
];

// Convert to bytes function
function toBytes($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size)-1]);
    $value = intval($size);
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    return $value;
}

// Test values
$testFileSize = 10490463; // 10MB file you're trying to upload
$uploadMaxBytes = toBytes($currentLimits['upload_max_filesize']);
$postMaxBytes = toBytes($currentLimits['post_max_size']);

$result = [
    'current_limits' => $currentLimits,
    'test' => [
        'file_size_bytes' => $testFileSize,
        'file_size_human' => round($testFileSize / 1024 / 1024, 2) . 'MB',
        'upload_max_bytes' => $uploadMaxBytes,
        'post_max_bytes' => $postMaxBytes,
        'can_upload' => $testFileSize <= $uploadMaxBytes && $testFileSize <= $postMaxBytes,
        'upload_limit_ok' => $testFileSize <= $uploadMaxBytes,
        'post_limit_ok' => $testFileSize <= $postMaxBytes,
    ],
    'required_changes' => [
        'need_upload_max_filesize' => $uploadMaxBytes < $testFileSize ? 'YES - increase to at least 11M' : 'NO',
        'need_post_max_size' => $postMaxBytes < $testFileSize ? 'YES - increase to at least 11M' : 'NO',
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);