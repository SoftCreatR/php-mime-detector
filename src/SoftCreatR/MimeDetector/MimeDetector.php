<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

use SoftCreatR\MimeDetector\Contract\MimeTypeResolverInterface;
use SoftCreatR\MimeDetector\Detection\DetectorPipeline;

/**
 * High level façade that mirrors the public API of the legacy detector.
 */
class MimeDetector
{
    private MimeTypeResolverInterface $mimeTypeDetector;

    private FileHandler $fileHandler;

    /**
     * @throws MimeDetectorException
     */
    public function __construct(
        string $filePath,
        ?MimeTypeRepository $repository = null,
        ?DetectorPipeline $pipeline = null,
        ?MimeTypeResolverInterface $mimeTypeDetector = null,
    ) {
        $this->fileHandler = new FileHandler();
        $this->fileHandler->setFile($filePath);

        $this->mimeTypeDetector = $mimeTypeDetector ?? new MimeTypeDetector(
            $filePath,
            $pipeline,
            $repository,
        );
    }

    /**
     * Resolve the MIME type for the configured file.
     */
    public function getMimeType(): string
    {
        return $this->mimeTypeDetector->getMimeType();
    }

    /**
     * Resolve the file extension for the configured file.
     */
    public function getFileExtension(): string
    {
        return $this->mimeTypeDetector->getFileExtension();
    }

    /**
     * Find the preferred extension for the supplied MIME type.
     */
    public function getExtensionForMimeType(string $mimeType): string
    {
        return $this->mimeTypeDetector->getExtensionForMimeType($mimeType);
    }

    /**
     * List all MIME types associated with the given extension.
     *
     * @return list<string>
     */
    public function getMimeTypesForExtension(string $extension): array
    {
        return $this->mimeTypeDetector->getMimeTypesForExtension($extension);
    }

    /**
     * Return the entire set of detectable MIME types.
     *
     * @return array<string, list<string>>
     */
    public function listAllMimeTypes(): array
    {
        return $this->mimeTypeDetector->listAllMimeTypes();
    }

    /**
     * Return the CRC32 hash of the configured file.
     */
    public function getFileHash(): string
    {
        return $this->fileHandler->getFileHash();
    }

    /**
     * Return a base64 encoded data URI for the configured file.
     */
    public function getBase64DataURI(): string
    {
        $fileMimeType = $this->getMimeType();

        try {
            $filePath = $this->fileHandler->getFilePath();
        } catch (MimeDetectorException) {
            return '';
        }

        $fileContents = @\file_get_contents($filePath);

        if ($fileContents === false || empty($fileMimeType)) {
            return '';
        }

        $base64String = \base64_encode($fileContents);

        if (!empty($base64String)) {
            return 'data:' . $fileMimeType . ';base64,' . $base64String;
        }

        return '';
    }

    /**
     * Suggest a Font Awesome icon class for the detected MIME type.
     */
    public function getFontAwesomeIcon(string $fileMimeType = '', bool $fixedWidth = false): string
    {
        $fileMimeType = \strtolower($fileMimeType ?: $this->getMimeType());

        $iconMap = [
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'fa-file-word',
            'application/vnd.oasis.opendocument.text' => 'fa-file-word',
            'application/rtf' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'fa-file-excel',
            'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel',
            'text/csv' => 'fa-file-csv',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.template' => 'fa-file-powerpoint',
            'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint',
            'application/vnd.apple.pages' => 'fa-file-word',
            'application/vnd.apple.numbers' => 'fa-file-excel',
            'application/vnd.apple.keynote' => 'fa-file-powerpoint',
            'text/plain' => 'fa-file-lines',
            'text/markdown' => 'fa-file-lines',
            'text/x-markdown' => 'fa-file-lines',
            'application/json' => 'fa-file-code',
            'application/ld+json' => 'fa-file-code',
            'application/graphql' => 'fa-file-code',
            'application/xml' => 'fa-file-code',
            'text/xml' => 'fa-file-code',
            'application/xhtml+xml' => 'fa-file-code',
            'text/html' => 'fa-file-code',
            'text/css' => 'fa-file-code',
            'application/javascript' => 'fa-file-code',
            'text/javascript' => 'fa-file-code',
            'application/x-sh' => 'fa-file-code',
            'application/x-php' => 'fa-file-code',
            'text/x-php' => 'fa-file-code',
            'text/x-python' => 'fa-file-code',
            'application/x-python-code' => 'fa-file-code',
            'text/x-shellscript' => 'fa-file-code',
            'text/x-c' => 'fa-file-code',
            'text/x-c++' => 'fa-file-code',
            'text/x-java-source' => 'fa-file-code',
            'font/collection' => 'fa-font',
            'font/otf' => 'fa-font',
            'font/sfnt' => 'fa-font',
            'font/ttf' => 'fa-font',
            'font/woff' => 'fa-font',
            'font/woff2' => 'fa-font',
            'application/font-woff' => 'fa-font',
            'application/font-woff2' => 'fa-font',
            'application/vnd.ms-opentype' => 'fa-font',
        ];

        if ($fileMimeType === '') {
            $iconClass = 'fa-file';
        } elseif (\array_key_exists($fileMimeType, $iconMap)) {
            $iconClass = $iconMap[$fileMimeType];
        } else {
            $iconClass = match (true) {
                $this->mimeIndicatesArchive($fileMimeType) => 'fa-file-zipper',
                \str_starts_with($fileMimeType, 'image/') => 'fa-file-image',
                \str_starts_with($fileMimeType, 'audio/') => 'fa-file-audio',
                \str_starts_with($fileMimeType, 'video/') => 'fa-file-video',
                \str_starts_with($fileMimeType, 'text/') => 'fa-file-lines',
                \str_contains($fileMimeType, 'json'),
                \str_contains($fileMimeType, 'xml'),
                \str_contains($fileMimeType, 'script') => 'fa-file-code',
                default => 'fa-file',
            };
        }

        return 'fa-solid ' . $iconClass . ($fixedWidth ? ' fa-fw' : '');
    }

    private function mimeIndicatesArchive(string $fileMimeType): bool
    {
        return \str_contains($fileMimeType, 'zip')
            || \str_contains($fileMimeType, 'rar')
            || \str_contains($fileMimeType, '7z')
            || \str_contains($fileMimeType, 'compressed')
            || \str_contains($fileMimeType, 'tar')
            || \str_contains($fileMimeType, 'gzip')
            || \str_contains($fileMimeType, 'bzip')
            || \str_contains($fileMimeType, 'xz');
    }
}
