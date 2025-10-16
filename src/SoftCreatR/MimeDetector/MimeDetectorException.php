<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

use RuntimeException;

/**
 * Base exception for all mime detector runtime failures.
 *
 * Named constructors are provided for the most common error scenarios so the
 * core can communicate intent without repeating string formatting logic.
 */
final class MimeDetectorException extends RuntimeException
{
    /**
     * Raised when a file path does not point to an existing regular file.
     */
    public static function fileDoesNotExist(string $filePath): self
    {
        return new self("File '" . $filePath . "' does not exist.");
    }

    /**
     * Raised when a file exists but cannot be read by the current process.
     */
    public static function fileNotReadable(string $filePath): self
    {
        return new self("File '" . $filePath . "' is not readable.");
    }

    /**
     * Raised when the byte cache limit would drop below a viable threshold.
     */
    public static function invalidByteCacheLength(int $maxLength): self
    {
        return new self('Maximum byte cache length "' . $maxLength . '" must not be smaller than 4.');
    }

    /**
     * Raised when no file path was supplied to a component that requires one.
     */
    public static function missingFilePath(): self
    {
        return new self('No file provided.');
    }

    /**
     * Raised when a file hash cannot be calculated for the given path.
     */
    public static function unableToHashFile(string $filePath): self
    {
        return new self("Unable to calculate the hash for '" . $filePath . "'.");
    }
}
