<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\ZipSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;
use ZipArchive;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\ZipSignatureDetector
 */
final class ZipSignatureDetectorTest extends TestCase
{
    public function testDetectsSpecificFormatsWithZipArchive(): void
    {
        if (!\class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        foreach ($this->zipArchiveCases() as $label => [$entries, $extension, $mimeType]) {
            $match = $this->detectFromEntries($entries);

            $this->assertInstanceOf(MimeTypeMatch::class, $match, $label);
            $this->assertSame($extension, $match->extension(), $label);
            $this->assertSame($mimeType, $match->mimeType(), $label);
        }
    }

    public function testFallsBackToZipWhenNoMarkersAreFound(): void
    {
        $match = $this->detectFromEntries([
            'random.txt' => 'contents',
        ]);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('zip', $match->extension());
        $this->assertSame('application/zip', $match->mimeType());
    }

    public function testDetectsRawContentMarkers(): void
    {
        foreach ($this->rawContentCases() as $label => [$payload, $extension, $mimeType]) {
            $match = $this->detectFromRaw($payload);

            $this->assertInstanceOf(MimeTypeMatch::class, $match, $label);
            $this->assertSame($extension, $match->extension(), $label);
            $this->assertSame($mimeType, $match->mimeType(), $label);
        }
    }

    public function testDetectionFallsBackWhenZipArchiveIsUnavailable(): void
    {
        $property = new ReflectionProperty(ZipSignatureDetector::class, 'zipArchiveAvailable');
        $original = $property->getValue();

        $property->setValue(null, false);

        $detector = new ZipSignatureDetector();
        $file = \tempnam(\sys_get_temp_dir(), 'mime-zip-missing-');
        \file_put_contents($file, "PK\x03\x04meta-inf/manifest.mf");

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);
            $context = new DetectionContext($file, $buffer);

            $match = $detector->detect($context);

            $this->assertInstanceOf(MimeTypeMatch::class, $match);
            $this->assertSame('jar', $match->extension());
            $this->assertSame('application/java-archive', $match->mimeType());
        } finally {
            \unlink($file);
            $property->setValue(null, $original);
        }
    }

    public function testZipArchiveOpenFailureFallsBackToRawDetection(): void
    {
        if (!\class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $detector = new ZipSignatureDetector();
        $data = "PK\x03\x04" . \str_repeat("\0", 12);
        $file = \tempnam(\sys_get_temp_dir(), 'mime-zip-open-');
        \file_put_contents($file, $data);

        $handler = new ByteCacheHandler($file);
        $buffer = new FileBuffer($handler);
        \unlink($file);

        $context = new DetectionContext($file, $buffer);

        $match = $detector->detect($context);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('zip', $match->extension());
        $this->assertSame('application/zip', $match->mimeType());
    }

    /**
     * @return iterable<string, array{0: array<string, string>, 1: string, 2: string}>
     */
    private function zipArchiveCases(): iterable
    {
        $contentType = 'application/vnd.ms-word.document.macroenabled.12';

        return [
            'content-types docm match' => [[
                '[Content_Types].xml' => '<Types><Default ContentType="' . $contentType . '"/></Types>',
                'word/document.xml' => '<xml/>',
            ], 'docm', $contentType],
            'classes.dex apk match' => [[
                'AndroidManifest.xml' => '<manifest/>',
                'classes.dex' => '',
            ], 'apk', 'application/vnd.android.package-archive'],
            'mimetype epub match' => [[
                'mimetype' => 'application/epub+zip',
            ], 'epub', 'application/epub+zip'],
            'mozilla signature xpi match' => [[
                'META-INF/mozilla.rsa' => '',
            ], 'xpi', 'application/x-xpinstall'],
            'manifest jar match' => [[
                'META-INF/MANIFEST.MF' => '',
            ], 'jar', 'application/java-archive'],
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    private function rawContentCases(): iterable
    {
        return [
            'content-type docm match' => [
                'application/vnd.ms-word.document.macroenabled.12',
                'docm',
                'application/vnd.ms-word.document.macroenabled.12',
            ],
            'mozilla signature xpi match' => [
                'META-INF/mozilla.rsa',
                'xpi',
                'application/x-xpinstall',
            ],
            'classes.dex apk match' => [
                'classes.dex',
                'apk',
                'application/vnd.android.package-archive',
            ],
            'manifest jar match' => [
                'META-INF/manifest.mf',
                'jar',
                'application/java-archive',
            ],
        ];
    }

    /**
     * @param array<string, string> $entries
     *
     * @throws MimeDetectorException
     */
    private function detectFromEntries(array $entries): ?MimeTypeMatch
    {
        if (!\class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $file = \tempnam(\sys_get_temp_dir(), 'mime-zip-');
        $archive = new ZipArchive();
        $opened = $archive->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            $this->fail('Unable to create temporary zip archive for testing.');
        }

        foreach ($entries as $name => $contents) {
            $archive->addFromString($name, $contents);
        }

        $archive->close();

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);
            $context = new DetectionContext($file, $buffer);

            return (new ZipSignatureDetector())->detect($context);
        } finally {
            \unlink($file);
        }
    }

    private function detectFromRaw(string $payload): ?MimeTypeMatch
    {
        $detector = new ZipSignatureDetector();
        $file = \tempnam(\sys_get_temp_dir(), 'mime-zip-raw-');
        \file_put_contents($file, "PK\x03\x04" . $payload);

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);
        } finally {
            \unlink($file);
        }

        $context = new DetectionContext($file, $buffer);

        return $detector->detect($context);
    }
}
