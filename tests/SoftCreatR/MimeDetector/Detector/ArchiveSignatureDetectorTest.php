<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\ArchiveSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\ArchiveSignatureDetector
 */
final class ArchiveSignatureDetectorTest extends TestCase
{
    public function testDetectsZstandardArchives(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = "\x28\xB5\x2F\xFD" . \str_repeat("\x00", 16);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('zst', $match->extension());
        $this->assertSame('application/zstd', $match->mimeType());
    }

    public function testDetectsTarArchivesUsingChecksumFallback(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = \str_repeat("\0", 512);
        $checksum = \sprintf('%06o', 256) . "\0 ";

        for ($i = 0, $length = \strlen($checksum); $i < $length; $i++) {
            $data[148 + $i] = $checksum[$i];
        }

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('tar', $match->extension());
        $this->assertSame('application/x-tar', $match->mimeType());
    }

    public function testDetectsCpioBinaryArchives(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = "\xC7\x71" . \str_repeat("\x00", 10);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('cpio', $match->extension());
        $this->assertSame('application/x-cpio', $match->mimeType());
    }

    public function testDetectsCpioAsciiArchives(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = '070707' . \str_repeat("\x00", 10);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('cpio', $match->extension());
        $this->assertSame('application/x-cpio', $match->mimeType());
    }

    public function testDetectsArjArchives(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = "\x60\xEA" . \str_repeat("\x00", 10);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('arj', $match->extension());
        $this->assertSame('application/x-arj', $match->mimeType());
    }

    public function testDetectsLzhArchives(): void
    {
        $detector = new ArchiveSignatureDetector();
        $data = "\x00\x00-lh5-" . \str_repeat("\x00", 10);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('lzh', $match->extension());
        $this->assertSame('application/x-lzh-compressed', $match->mimeType());
    }

    public function testTarChecksumFailsWhenHeaderBytesAreMissing(): void
    {
        $detector = new ArchiveSignatureDetector();

        $match = $this->detectFromSyntheticBuffer($detector, [], 512);

        $this->assertNull($match);
    }

    public function testTarChecksumFailsWhenBodyBytesAreUnavailable(): void
    {
        $detector = new ArchiveSignatureDetector();
        $bytes = [];

        foreach (\str_split('000000') as $index => $char) {
            $bytes[148 + $index] = \ord($char);
        }

        $bytes[154] = 0x00;
        $bytes[155] = 0x20;

        $match = $this->detectFromSyntheticBuffer($detector, $bytes, 512);

        $this->assertNull($match);
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(ArchiveSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-archive-');
        \file_put_contents($file, $data);

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);
            $context = new DetectionContext($file, $buffer);

            return $detector->detect($context);
        } finally {
            \unlink($file);
        }
    }

    /**
     * @param array<int, int> $bytes
     *
     * @throws ReflectionException
     */
    private function detectFromSyntheticBuffer(
        ArchiveSignatureDetector $detector,
        array $bytes,
        int $length
    ): ?MimeTypeMatch {
        $reflection = new ReflectionClass(ByteCacheHandler::class);
        /** @var ByteCacheHandler $handler */
        $handler = $reflection->newInstanceWithoutConstructor();

        $this->setByteCacheProperty($reflection, $handler, 'file', __FILE__);
        $this->setByteCacheProperty($reflection, $handler, 'byteCache', $bytes);
        $this->setByteCacheProperty($reflection, $handler, 'byteCacheLen', $length);
        $this->setByteCacheProperty($reflection, $handler, 'maxByteCacheLen', \max($length, 4096));

        $buffer = new FileBuffer($handler);
        $context = new DetectionContext(__FILE__, $buffer);

        return $detector->detect($context);
    }

    private function setByteCacheProperty(
        ReflectionClass $reflection,
        ByteCacheHandler $handler,
        string $property,
        mixed $value
    ): void {
        $prop = $reflection->getProperty($property);
        $prop->setValue($handler, $value);
    }
}
