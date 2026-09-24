<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

/**
 * Provides lightweight file metadata helpers used by the façade.
 */
class FileHandler
{
    private string $fileHash = '';

    private ?string $filePath = null;

    /**
     * Register a file with the handler. The checksum is calculated on demand.
     *
     * @throws MimeDetectorException When the file is missing or unreadable.
     */
    public function setFile(string $filePath): self
    {
        if (!\is_file($filePath)) {
            throw MimeDetectorException::fileDoesNotExist($filePath);
        }

        if (!\is_readable($filePath)) {
            throw MimeDetectorException::fileNotReadable($filePath);
        }

        $this->fileHash = '';
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * Derive a CRC32 checksum for the provided value.
     *
     * @throws MimeDetectorException When the referenced file cannot be hashed.
     */
    public function getHash(string $value): string
    {
        if (\is_file($value)) {
            $hash = \hash_file('crc32b', $value);

            if ($hash === false) {
                throw MimeDetectorException::unableToHashFile($value);
            }

            return $hash;
        }

        return \hash('crc32b', $value);
    }

    /**
     * Retrieve or calculate the checksum of the registered file.
     *
     * @throws MimeDetectorException When the file cannot be hashed.
     */
    public function getFileHash(): string
    {
        if ($this->fileHash === '' && $this->filePath !== null) {
            $hash = @\hash_file('crc32b', $this->filePath);

            if ($hash === false) {
                throw MimeDetectorException::unableToHashFile($this->filePath);
            }

            $this->fileHash = $hash;
        }

        return $this->fileHash;
    }

    /**
     * Return the absolute path of the registered file.
     *
     * @throws MimeDetectorException When no file has been registered yet.
     */
    public function getFilePath(): string
    {
        if ($this->filePath === null) {
            throw MimeDetectorException::missingFilePath();
        }

        return $this->filePath;
    }
}
