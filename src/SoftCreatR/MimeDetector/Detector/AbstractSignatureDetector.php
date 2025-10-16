<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Base helper that simplifies the creation of match value objects.
 */
abstract class AbstractSignatureDetector implements FileSignatureDetectorInterface
{
    /**
     * Convenience factory for `MimeTypeMatch` instances.
     */
    protected function match(string $extension, string $mimeType): MimeTypeMatch
    {
        return new MimeTypeMatch($extension, $mimeType);
    }
}
