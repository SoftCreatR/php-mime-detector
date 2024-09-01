<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

class FileHandler
{
    private string $fileHash = '';

    /**
     * @throws MimeDetectorException
     */
    public function setFile(string $filePath): self
    {
        if (!\file_exists($filePath)) {
            throw new MimeDetectorException("File '" . $filePath . "' does not exist.");
        }

        $fileHash = $this->getHash($filePath);
        $this->fileHash = $fileHash;

        return $this;
    }

    public function getHash(string $str): string
    {
        if (\file_exists($str)) {
            return \hash_file('crc32b', $str);
        }

        return \hash('crc32b', $str);
    }

    public function getFileHash(): string
    {
        return $this->fileHash;
    }
}
