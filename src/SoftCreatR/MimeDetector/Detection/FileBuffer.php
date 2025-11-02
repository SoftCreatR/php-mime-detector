<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detection;

use SoftCreatR\MimeDetector\ByteCacheHandler;

/**
 * Lightweight wrapper around the byte cache for convenience and clarity.
 */
final class FileBuffer
{
    public function __construct(private readonly ByteCacheHandler $handler)
    {
        // ...
    }

    /**
     * Determine whether the provided byte sequence exists at the given offset.
     *
     * @param list<int> $bytes
     * @param list<int> $mask
     */
    public function checkForBytes(array $bytes, int $offset = 0, array $mask = []): bool
    {
        return $this->handler->checkForBytes($bytes, $offset, $mask);
    }

    /**
     * Find the offset for the supplied byte sequence if present.
     *
     * @param list<int> $bytes
     * @param list<int> $mask
     */
    public function searchForBytes(array $bytes, int $offset = 0, array $mask = []): int
    {
        return $this->handler->searchForBytes($bytes, $offset, $mask);
    }

    /**
     * Convenience wrapper to check whether the supplied string occurs at the
     * given offset.
     */
    public function checkString(string $string, int $offset = 0): bool
    {
        return $this->handler->checkString($string, $offset);
    }

    /**
     * Convert a string into an array of byte values.
     *
     * @return list<int>
     */
    public function toBytes(string $string): array
    {
        return $this->handler->toBytes($string);
    }

    /**
     * Expose the cached byte length of the current file.
     */
    public function length(): int
    {
        return $this->handler->getByteCacheLen();
    }

    /**
     * Fetch a single byte at the given offset.
     */
    public function get(int $offset): ?int
    {
        return $this->handler->getByte($offset);
    }

    /**
     * Return a slice of the cached bytes as a string for textual inspections.
     */
    public function sliceAsString(int $offset = 0, ?int $length = null): string
    {
        $bytes = $this->handler->getByteCache();

        if ($offset !== 0 || $length !== null) {
            $bytes = \array_slice($bytes, $offset, $length ?? null);
        }

        if ($bytes === []) {
            return '';
        }

        return \implode('', \array_map('chr', $bytes));
    }
}
