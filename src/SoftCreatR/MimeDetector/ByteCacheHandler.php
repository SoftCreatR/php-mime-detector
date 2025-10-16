<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

/**
 * Reads a file into an internal byte cache to optimise signature lookups.
 */
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

    /**
     * Return the cached byte data.
     *
     * @return list<int>
     */
    public function getByteCache(): array
    {
        return $this->byteCache;
    }

    /**
     * Length of the cached byte data.
     */
    public function getByteCacheLen(): int
    {
        return $this->byteCacheLen;
    }

    /**
     * Fetch a single byte by offset.
     */
    public function getByte(int $offset): ?int
    {
        return $this->byteCache[$offset] ?? null;
    }

    /**
     * @throws MimeDetectorException
     */
    public function setMaxByteCacheLen(int $maxLength): void
    {
        if ($maxLength < 4) {
            throw MimeDetectorException::invalidByteCacheLength($maxLength);
        }

        $this->maxByteCacheLen = $maxLength;
    }

    /**
     * Retrieve the configured maximum cache length.
     */
    public function getMaxByteCacheLen(): int
    {
        return $this->maxByteCacheLen;
    }

    /**
     * @throws MimeDetectorException
     */
    private function createByteCache(): void
    {
        if ($this->file === '') {
            throw MimeDetectorException::missingFilePath();
        }

        $handle = @\fopen($this->file, 'rb');

        if ($handle === false) {
            throw MimeDetectorException::fileNotReadable($this->file);
        }

        $data = @\fread($handle, $this->maxByteCacheLen);
        \fclose($handle);

        if ($data === false) {
            throw MimeDetectorException::fileNotReadable($this->file);
        }

        foreach (\str_split($data) as $i => $char) {
            $this->byteCache[$i] = \ord($char);
        }

        $this->byteCacheLen = \count($this->byteCache);
    }

    /**
     * @param list<int> $bytes
     * @param list<int> $mask
     */
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

    /**
     * @param list<int> $bytes
     * @param list<int> $mask
     */
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

    /**
     * Convert the provided string into a list of byte values.
     *
     * @return list<int>
     */
    public function toBytes(string $str): array
    {
        return \array_values(\unpack('C*', $str));
    }
}
