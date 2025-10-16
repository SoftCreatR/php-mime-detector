<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detection;

/**
 * Immutable value object that stores the outcome of a detection run.
 */
final class MimeTypeMatch
{
    public function __construct(
        private readonly string $extension,
        private readonly string $mimeType,
    ) {
        // ...
    }

    /**
     * Return the detected file extension.
     */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * Return the detected MIME type.
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Represent the match as an associative array.
     *
     * @return array{ext: string, mime: string}
     */
    public function toArray(): array
    {
        return [
            'ext' => $this->extension,
            'mime' => $this->mimeType,
        ];
    }
}
