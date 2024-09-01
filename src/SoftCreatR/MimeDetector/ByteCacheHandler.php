<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

class ByteCacheHandler
{
    private array $byteCache = [];

    private int $byteCacheLen = 0;

    private int $maxByteCacheLen = 4096;

    /**
     * @throws MimeDetectorException
     */
    public function __construct(private readonly string $file)
    {
        $this->createByteCache();
    }

    public function getByteCache(): array
    {
        return $this->byteCache;
    }

    public function getByteCacheLen(): int
    {
        return $this->byteCacheLen;
    }

    /**
     * @throws MimeDetectorException
     */
    public function setMaxByteCacheLen(int $maxLength): void
    {
        if ($maxLength < 4) {
            throw new MimeDetectorException('Maximum byte cache length must not be smaller than 4.');
        }
        $this->maxByteCacheLen = $maxLength;
    }

    public function getMaxByteCacheLen(): int
    {
        return $this->maxByteCacheLen;
    }

    /**
     * @throws MimeDetectorException
     */
    private function createByteCache(): void
    {
        if (empty($this->file)) {
            throw new MimeDetectorException('No file provided.');
        }

        $handle = \fopen($this->file, 'rb');
        $data = \fread($handle, $this->maxByteCacheLen);
        \fclose($handle);

        foreach (\str_split($data) as $i => $char) {
            $this->byteCache[$i] = \ord($char);
        }

        $this->byteCacheLen = \count($this->byteCache);
    }

    public function checkForBytes(array $bytes, int $offset = 0, array $mask = []): bool
    {
        if (empty($bytes) || empty($this->byteCache)) {
            return false;
        }

        foreach (\array_values($bytes) as $i => $byte) {
            if (!empty($mask)) {
                if (
                    !isset($this->byteCache[$offset + $i], $mask[$i])
                    || $byte !== ($mask[$i] & $this->byteCache[$offset + $i])
                ) {
                    return false;
                }
            } elseif (!isset($this->byteCache[$offset + $i]) || $this->byteCache[$offset + $i] !== $byte) {
                return false;
            }
        }

        return true;
    }

    public function searchForBytes(array $bytes, int $offset = 0, array $mask = []): int
    {
        $limit = $this->byteCacheLen - \count($bytes);

        for ($i = $offset; $i < $limit; $i++) {
            if ($this->checkForBytes($bytes, $i, $mask)) {
                return $i;
            }
        }

        return -1;
    }

    public function checkString(string $str, int $offset = 0): bool
    {
        return $this->checkForBytes($this->toBytes($str), $offset);
    }

    public function toBytes(string $str): array
    {
        return \array_values(\unpack('C*', $str));
    }
}
