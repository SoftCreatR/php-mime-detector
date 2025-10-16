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
            'xcf' => ['gimp xcf ' . \str_repeat("\0", 2), 'xcf', 'image/x-xcf'],
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
