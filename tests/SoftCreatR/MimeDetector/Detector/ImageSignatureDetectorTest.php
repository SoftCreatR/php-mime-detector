<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\ImageSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\ImageSignatureDetector
 */
final class ImageSignatureDetectorTest extends TestCase
{
    #[DataProvider('provideImageSamples')]
    public function testDetectsAdditionalImageFormats(string $data, string $extension, string $mimeType): void
    {
        $detector = new ImageSignatureDetector();

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame($extension, $match->extension());
        $this->assertSame($mimeType, $match->mimeType());
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function provideImageSamples(): iterable
    {
        return [
            'JPEG-LS' => ["\xFF\xD8\xFF\xF7", 'jls', 'image/jls'],
            'avif-sequence' => [
                "\x00\x00\x00\x18ftypavis" . \str_repeat("\0", 4),
                'avif',
                'image/avif',
            ],
            'icns' => ['icns' . \str_repeat("\0", 4), 'icns', 'image/icns'],
            'j2c' => ["\xFF\x4F\xFF\x51", 'j2c', 'image/j2c'],
            'orf' => ["\x49\x49\x52\x4F\x08\x00\x00\x00\x18", 'orf', 'image/x-olympus-orf'],
            'raf' => ['FUJIFILMCCD-RAW', 'raf', 'image/x-fujifilm-raf'],
            'rw2' => ["\x49\x49\x55\x00\x18\x00\x00\x00\x88\xE7\x74\xD8", 'rw2', 'image/x-panasonic-rw2'],
            'TIFF with NEF-like tag count' => [
                "II\x2A\x00" . \pack('V', 8) . \pack('v', 28) . \pack('v', 254)
                    . \pack('v', 4) . \pack('V', 1) . \pack('V', 0) . \str_repeat("\0", 27 * 12 + 4),
                'tif',
                'image/tiff',
            ],
            'Canon TIFF with PrintIM tag' => [
                "II\x2A\x00" . \pack('V', 8) . \pack('v', 2)
                    . \pack('v', 271) . \pack('v', 2) . \pack('V', 6) . \pack('V', 38)
                    . \pack('v', 50341) . \pack('v', 7) . \pack('V', 8) . \pack('V', 44)
                    . \pack('V', 0) . "Canon\0" . 'PrintIM0',
                'tif',
                'image/tiff',
            ],
            'xcf' => ['gimp xcf ' . \str_repeat("\0", 2), 'xcf', 'image/x-xcf'],
        ];
    }

    #[DataProvider('provideInvalidHeaders')]
    public function testRejectsInvalidNewFormatHeaders(string $data): void
    {
        $this->assertNull($this->detect(new ImageSignatureDetector(), $data));
    }

    public static function provideInvalidHeaders(): iterable
    {
        $qoi = 'qoif' . \pack('NNCC', 1, 1, 3, 0);
        $dds = 'DDS ' . \pack('V', 124) . \str_repeat("\0", 120);

        return [
            'QOI truncated' => [\substr($qoi, 0, 13)],
            'QOI zero width' => [\substr_replace($qoi, \pack('N', 0), 4, 4)],
            'QOI zero height' => [\substr_replace($qoi, \pack('N', 0), 8, 4)],
            'QOI channels' => [\substr_replace($qoi, "\x02", 12, 1)],
            'QOI colorspace' => [\substr_replace($qoi, "\x02", 13, 1)],
            'DDS truncated' => [\substr($dds, 0, 127)],
            'DDS zero dimensions' => [$dds],
            'DDS wrong header size' => ['DDS ' . \pack('V', 125) . \str_repeat("\0", 120)],
            'EXR truncated' => ["\x76\x2F\x31\x01\x02"],
            'EXR unknown version' => ["\x76\x2F\x31\x01\x03\0\0\0"],
            'EXR reserved flags' => ["\x76\x2F\x31\x01\x02\0\0\x80"],
            'DjVu truncated' => ['AT&TFORM' . \pack('N', 4) . 'DJV'],
            'DjVu unrelated FORM' => ['AT&TFORM' . \pack('N', 4) . 'XXXX'],
            'DjVu invalid FORM length' => ['AT&TFORM' . \pack('N', 3) . 'DJVU'],
            'Netpbm prose' => ['P1 is a priority'],
            'Netpbm missing whitespace' => ["P61 1\n255\n"],
            'Netpbm zero width' => ["P6\n0 1\n255\n"],
            'Netpbm zero height' => ["P6\n1 0\n255\n"],
            'Netpbm zero maximum' => ["P6\n1 1\n0\n"],
            'Netpbm maximum too large' => ["P6\n1 1\n65536\n"],
            'Netpbm incomplete header' => ["P6\n1 1\n"],
        ];
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(ImageSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-image-');
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
}
