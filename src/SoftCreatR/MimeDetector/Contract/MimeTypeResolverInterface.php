<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Contract;

use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Exposes the capabilities of the MIME type detector to consumers.
 */
interface MimeTypeResolverInterface
{
    /**
     * Run the signature pipeline and return the resulting match instance.
     */
    public function detectFile(): ?MimeTypeMatch;

    /**
     * Retrieve both the MIME type and extension for the current file.
     *
     * @return array{ext: string, mime: string}
     */
    public function getFileType(): array;

    /**
     * Resolve the most appropriate extension for the current file.
     */
    public function getFileExtension(): string;

    /**
     * Resolve the MIME type for the current file.
     */
    public function getMimeType(): string;

    /**
     * Find the preferred extension for the supplied MIME type.
     */
    public function getExtensionForMimeType(string $mimeType): string;

    /**
     * Return every MIME type that maps to the provided extension.
     *
     * @return list<string>
     */
    public function getMimeTypesForExtension(string $extension): array;

    /**
     * List all known MIME types and their extensions.
     *
     * @return array<string, list<string>>
     */
    public function listAllMimeTypes(): array;
}
