<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detection;

/**
 * Provides state shared across detectors while analysing a file.
 */
final class DetectionContext
{
    private ?MimeTypeMatch $result = null;

    public function __construct(
        private readonly string $file,
        private readonly FileBuffer $buffer,
    ) {
        // ...
    }

    /**
     * Path to the file that is being analysed.
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * Buffered access to the file contents.
     */
    public function buffer(): FileBuffer
    {
        return $this->buffer;
    }

    /**
     * Persist the detected result for subsequent lookups.
     */
    public function remember(MimeTypeMatch $result): void
    {
        $this->result = $result;
    }

    /**
     * Retrieve the previously remembered detection result.
     */
    public function remembered(): ?MimeTypeMatch
    {
        return $this->result;
    }
}
