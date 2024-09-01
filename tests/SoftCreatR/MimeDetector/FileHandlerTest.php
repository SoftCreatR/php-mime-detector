<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\FileHandler;
use SoftCreatR\MimeDetector\MimeDetectorException;

class FileHandlerTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        // Create a temporary test file
        $this->testFile = \tempnam(\sys_get_temp_dir(), 'testFile');
        \file_put_contents($this->testFile, 'This is a test file.');
    }

    protected function tearDown(): void
    {
        // Clean up the temporary test file
        if (\file_exists($this->testFile)) {
            \unlink($this->testFile);
        }
    }

    public function testSetFileThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->expectExceptionMessage("File 'non_existent.file' does not exist.");

        $fileHandler = new FileHandler();
        $fileHandler->setFile('non_existent.file');
    }

    /**
     * @throws MimeDetectorException
     */
    public function testSetFileSetsFileHash(): void
    {
        $fileHandler = new FileHandler();
        $fileHandler->setFile($this->testFile);

        $expectedHash = \hash_file('crc32b', $this->testFile);
        $this->assertSame($expectedHash, $fileHandler->getFileHash());
    }

    public function testGetHashForExistingFile(): void
    {
        $fileHandler = new FileHandler();
        $hash = $fileHandler->getHash($this->testFile);

        $expectedHash = \hash_file('crc32b', $this->testFile);
        $this->assertSame($expectedHash, $hash);
    }

    public function testGetHashForString(): void
    {
        $fileHandler = new FileHandler();
        $hash = $fileHandler->getHash('This is a test string.');

        $expectedHash = \hash('crc32b', 'This is a test string.');
        $this->assertSame($expectedHash, $hash);
    }

    public function testGetFileHashBeforeSettingFile(): void
    {
        $fileHandler = new FileHandler();

        // Initially, fileHash should be an empty string
        $this->assertSame('', $fileHandler->getFileHash());
    }
}
