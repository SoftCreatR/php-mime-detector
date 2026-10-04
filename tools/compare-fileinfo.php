<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

use SoftCreatR\MimeDetector\MimeTypeAliases;
use SoftCreatR\MimeDetector\MimeTypeDetector;

require \dirname(__DIR__) . '/vendor/autoload.php';

$directory = $argv[1] ?? \dirname(__DIR__) . '/tests/SoftCreatR/MimeDetector/fixtures';

if (!\extension_loaded('fileinfo') || !\is_dir($directory)) {
    \fwrite(STDERR, "Usage: php tools/compare-fileinfo.php [fixture-directory] (requires ext-fileinfo)\n");
    exit(1);
}

$fileinfo = new finfo(FILEINFO_MIME_TYPE);
$summary = ['equivalent' => 0, 'different' => 0, 'detector-only' => 0, 'fileinfo-only' => 0, 'unidentified' => 0];
$differences = [];
$detectedTypes = [];
$files = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

foreach ($files as $file) {
    if (!$file->isFile() || !\str_starts_with($file->getFilename(), 'fixture')) {
        continue;
    }

    $match = (new MimeTypeDetector($file->getPathname()))->detectFile();
    $detected = $match?->mimeType();
    $detected = $detected === 'application/octet-stream' ? null : $detected;
    $system = $fileinfo->file($file->getPathname());
    $system = $system === false || $system === 'application/octet-stream' ? null : $system;

    if ($detected !== null) {
        $detectedTypes[$detected] = true;
    }

    $status = match (true) {
        $detected === null && $system === null => 'unidentified',
        $detected === null => 'fileinfo-only',
        $system === null => 'detector-only',
        MimeTypeAliases::equivalent($detected, $system) => 'equivalent',
        default => 'different',
    };
    $summary[$status]++;

    if ($status !== 'equivalent') {
        $differences[$file->getFilename()] = [
            'status' => $status,
            'detector' => $detected,
            'fileinfo' => $system,
        ];
    }
}

\ksort($differences);

echo \json_encode([
    'php' => PHP_VERSION,
    'detectedMimeTypes' => \count($detectedTypes),
    'summary' => $summary,
    'differences' => $differences,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
