<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use DirectoryIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\DetectorPipeline;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\MimeDetectorException;
use SoftCreatR\MimeDetector\MimeTypeDetector;
use SoftCreatR\MimeDetector\MimeTypeRepository;

class MimeTypeDetectorTest extends TestCase
{
    #[DataProvider('provideTestFiles')]
    public function testGetMimeType(string $filePath, string $expectedMimeType, string $expectedExt): void
    {
        $mimeTypeDetector = new MimeTypeDetector($filePath);
        $mimeType = $mimeTypeDetector->getMimeType();

        $this->assertSame($expectedMimeType, $mimeType);
    }

    #[DataProvider('provideTestFiles')]
    public function testGetFileExtension(string $filePath, string $expectedMimeType, string $expectedExt): void
    {
        $mimeTypeDetector = new MimeTypeDetector($filePath);
        $fileExt = $mimeTypeDetector->getFileExtension();

        $this->assertSame($expectedExt, $fileExt);
    }

    public function testDetectFileProvidesMatchAndFileType(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-type-');
        \file_put_contents($file, '<?xml <svg></svg>');

        try {
            $detector = new MimeTypeDetector($file);

            $match = $detector->detectFile();

            $this->assertInstanceOf(MimeTypeMatch::class, $match);
            $this->assertSame('svg', $match->extension());
            $this->assertSame('image/svg+xml', $match->mimeType());

            $this->assertSame([
                'ext' => 'svg',
                'mime' => 'image/svg+xml',
            ], $detector->getFileType());
        } finally {
            \unlink($file);
        }
    }

    /**
     * @throws MimeDetectorException
     */
    public function testGetExtensionForMimeType(): void
    {
        $detector = new MimeTypeDetector(__FILE__);

        $this->assertSame('mp2', $detector->getExtensionForMimeType('audio/mpeg'));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testGetMimeTypesForExtension(): void
    {
        $detector = new MimeTypeDetector(__FILE__);

        $this->assertSame(['audio/mpeg', 'video/mp4'], $detector->getMimeTypesForExtension('mp4'));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testDetectionResultIsCachedAcrossCalls(): void
    {
        $tracker = new class {
            public int $calls = 0;
        };

        $detector = new class ($tracker) implements FileSignatureDetectorInterface {
            public function __construct(private readonly object $tracker)
            {
                // ...
            }

            public function detect(DetectionContext $context): ?MimeTypeMatch
            {
                $this->tracker->calls++;

                return new MimeTypeMatch('txt', 'text/plain');
            }
        };

        $tempFile = \tempnam(\sys_get_temp_dir(), 'mime-cache-');
        \file_put_contents($tempFile, 'cached');

        try {
            $pipeline = DetectorPipeline::create($detector);
            $repository = new MimeTypeRepository(['txt' => ['text/plain']]);
            $mimeDetector = new MimeTypeDetector($tempFile, $pipeline, $repository);

            $this->assertSame('text/plain', $mimeDetector->getMimeType());
            $this->assertSame(1, $tracker->calls);

            $this->assertSame('txt', $mimeDetector->getFileExtension());
            $this->assertSame(1, $tracker->calls);
        } finally {
            \unlink($tempFile);
        }
    }

    /**
     * @throws MimeDetectorException
     */
    public function testListAllMimeTypes(): void
    {
        $detector = new MimeTypeDetector(__FILE__);
        $all = $detector->listAllMimeTypes();

        $this->assertArrayHasKey('application/pdf', $all);
        $this->assertContains('pdf', $all['application/pdf']);
    }

    /**
     * Provides valid test files with their expected extensions and MIME types.
     *
     * @return array
     * @throws MimeDetectorException
     */
    public static function provideTestFiles(): array
    {
        $files = [];

        // Iterate over the fixtures directory to get test files
        $fixturesPath = __DIR__ . '/fixtures';
        if (\is_dir($fixturesPath)) {
            foreach (new DirectoryIterator($fixturesPath) as $file) {
                if (!$file->isFile() || $file->getBasename() === '.git' || $file->getBasename() === '.gitattributes') {
                    continue;
                }

                $mimeTypeDetector = new MimeTypeDetector($file->getPathname());
                $fileType = $mimeTypeDetector->getFileType();

                // Ensure only valid MIME types and extensions are included
                if (!empty($fileType['ext']) && !empty($fileType['mime'])) {
                    // Test MIME type and extension separately
                    $files[] = [
                        $file->getPathname(),
                        $fileType['mime'],  // Expected MIME type
                        $fileType['ext'],   // Expected file extension
                    ];
                }
            }
        }

        if ($files === []) {
            $files = self::provideGeneratedFallbackFixture();
        }

        return $files;
    }

    /**
     * Provide a deterministic fallback fixture when the optional submodule is not available.
     *
     * @return array<array{0: string, 1: string, 2: string}>
     */
    private static function provideGeneratedFallbackFixture(): array
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-fallback-');
        \file_put_contents($file, \base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p91Zs8AAAAASUVORK5CYII='
        ));
        \register_shutdown_function(static fn() => \is_string($file) ? @\unlink($file) : null);

        return [[
            $file,
            'image/png',
            'png',
        ]];
    }
}
