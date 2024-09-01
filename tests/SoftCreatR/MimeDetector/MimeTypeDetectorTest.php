<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeDetectorException;
use SoftCreatR\MimeDetector\MimeTypeDetector;

class MimeTypeDetectorTest extends TestCase
{
    /**
     * @dataProvider provideTestFiles
     * @throws MimeDetectorException
     */
    public function testGetMimeType(string $filePath, string $expectedMimeType, string $expectedExt): void
    {
        $mimeTypeDetector = new MimeTypeDetector($filePath);
        $mimeType = $mimeTypeDetector->getMimeType();

        $this->assertSame($expectedMimeType, $mimeType);
    }

    /**
     * @dataProvider provideTestFiles
     * @throws MimeDetectorException
     */
    public function testGetFileExtension(string $filePath, string $expectedMimeType, string $expectedExt): void
    {
        $mimeTypeDetector = new MimeTypeDetector($filePath);
        $fileExt = $mimeTypeDetector->getFileExtension();

        $this->assertSame($expectedExt, $fileExt);
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
        foreach (new DirectoryIterator(__DIR__ . '/fixtures') as $file) {
            if ($file->isFile() && $file->getBasename() !== '.git') {
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

        return $files;
    }
}
