<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\DocumentSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\DocumentSignatureDetector
 */
final class DocumentSignatureDetectorTest extends TestCase
{
    private const OLE_HEADER = [0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1];

    #[DataProvider('provideSimpleSignatures')]
    public function testDetectsSimpleDocumentSignatures(string $expectedExt, string $expectedMime, string $data): void
    {
        $detector = new DocumentSignatureDetector();

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame($expectedExt, $match->extension());
        $this->assertSame($expectedMime, $match->mimeType());
    }

    public static function provideSimpleSignatures(): iterable
    {
        yield 'pdf' => ['pdf', 'application/pdf', self::bytes([0x25, 0x50, 0x44, 0x46])];
        yield 'rtf' => ['rtf', 'application/rtf', self::bytes([0x7B, 0x5C, 0x72, 0x74, 0x66])];
        yield 'mobi' => [
            'mobi',
            'application/x-mobipocket-ebook',
            self::bytes(\array_merge(
                \array_fill(0, 60, 0x00),
                [0x42, 0x4F, 0x4F, 0x4B, 0x4D, 0x4F, 0x42, 0x49],
            )),
        ];
        yield 'postscript' => ['ps', 'application/postscript', self::bytes([0x25, 0x21])];
        yield 'eps-ascii' => [
            'eps',
            'application/eps',
            '%!PS-Adobe-3.0 EPSF-3.0',
        ];
        yield 'eps-binary' => [
            'eps',
            'application/eps',
            self::bytes([0xC5, 0xD0, 0xD3, 0xC6]),
        ];
        yield 'chm' => [
            'chm',
            'application/vnd.ms-htmlhelp',
            'ITSF' . \str_repeat("\0", 4),
        ];
        yield 'pst' => [
            'pst',
            'application/vnd.ms-outlook',
            self::bytes([0x21, 0x42, 0x44, 0x4E]),
        ];
        yield 'indd' => [
            'indd',
            'application/x-indesign',
            self::bytes([
                0x06, 0x06, 0xED, 0xF5, 0xD8, 0x1D, 0x46, 0xE5,
                0xBD, 0x31, 0xEF, 0xE7, 0xFE, 0x74, 0xB7, 0x1D,
            ]),
        ];
    }

    public function testDetectsVisioDocuments(): void
    {
        $data = $this->bytesWithOffsets([
            0 => self::OLE_HEADER,
            1664 => [
                0x56, 0x00, 0x69, 0x00, 0x73,
                0x00, 0x69, 0x00, 0x6F, 0x00,
                0x44, 0x00, 0x6F, 0x00, 0x63,
            ],
        ]);

        $match = $this->detect(new DocumentSignatureDetector(), $data);

        $this->assertSame('vsd', $match?->extension());
        $this->assertSame('application/vnd.visio', $match?->mimeType());
    }

    public function testDetectsXlsWithBiffStream(): void
    {
        $data = $this->bytesWithOffsets([
            0 => self::OLE_HEADER,
            2048 => [0x09, 0x08, 0x10, 0x00, 0x00, 0x06, 0x05, 0x00],
        ]);

        $match = $this->detect(new DocumentSignatureDetector(), $data);

        $this->assertSame('xls', $match?->extension());
        $this->assertSame('application/vnd.ms-excel', $match?->mimeType());
    }

    public function testDetectsXlsWithWorkbookStream(): void
    {
        $data = $this->bytesWithOffsets([
            0 => self::OLE_HEADER,
            512 => [0xFD, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF],
        ]);

        $match = $this->detect(new DocumentSignatureDetector(), $data);

        $this->assertSame('xls', $match?->extension());
        $this->assertSame('application/vnd.ms-excel', $match?->mimeType());
    }

    public function testDefaultsToMsiForOleDocuments(): void
    {
        $match = $this->detect(new DocumentSignatureDetector(), self::bytes(self::OLE_HEADER));

        $this->assertSame('msi', $match?->extension());
        $this->assertSame('application/x-msi', $match?->mimeType());
    }

    public function testReturnsNullForUnknownDocuments(): void
    {
        $match = $this->detect(new DocumentSignatureDetector(), '');

        $this->assertNull($match);
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(DocumentSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-doc-');
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

    private static function bytes(array $bytes): string
    {
        if ($bytes === []) {
            return '';
        }

        return \pack('C*', ...$bytes);
    }

    private function bytesWithOffsets(array $segments): string
    {
        $length = 0;

        foreach ($segments as $offset => $bytes) {
            $length = \max($length, $offset + \count($bytes));
        }

        if ($length === 0) {
            return '';
        }

        $buffer = \array_fill(0, $length, 0x00);

        foreach ($segments as $offset => $bytes) {
            foreach ($bytes as $index => $byte) {
                $buffer[$offset + $index] = $byte;
            }
        }

        return \pack('C*', ...$buffer);
    }
}
