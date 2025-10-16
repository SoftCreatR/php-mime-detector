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
        $iconClass = 'fa-file-o';

        $fileMimeType = $fileMimeType ?: $this->getMimeType();

        $iconClass = match (true) {
            \str_contains($fileMimeType, 'image') => 'fa-file-image-o',
            \str_contains($fileMimeType, 'audio') => 'fa-file-audio-o',
            \str_contains($fileMimeType, 'video') => 'fa-file-video-o',
            default => $iconClass,
        };

        return 'fa ' . $iconClass . ($fixedWidth ? ' fa-fw' : '');
    }
}
