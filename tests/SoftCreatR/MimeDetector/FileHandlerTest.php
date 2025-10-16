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

use const E_WARNING;

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

    public function testSetFileThrowsExceptionForUnreadableFile(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->expectExceptionMessage("is not readable");

        \stream_wrapper_register('filehandler-unreadable', UnreadableFileStream::class);

        try {
            $fileHandler = new FileHandler();
            $fileHandler->setFile('filehandler-unreadable://file.txt');
        } finally {
            \stream_wrapper_unregister('filehandler-unreadable');
        }
    }

    public function testSetFileThrowsExceptionWhenHashingFails(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->expectExceptionMessage("Unable to calculate the hash");

        \stream_wrapper_register('filehandler-hashfail', HashFailureStream::class);

        \set_error_handler(static function (): bool {
            return true;
        }, E_WARNING);

        try {
            $fileHandler = new FileHandler();
            $fileHandler->setFile('filehandler-hashfail://file.txt');
        } finally {
            \restore_error_handler();
            \stream_wrapper_unregister('filehandler-hashfail');
        }
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

    /**
     * @throws MimeDetectorException
     */
    public function testGetFilePathReturnsRegisteredPath(): void
    {
        $fileHandler = new FileHandler();
        $fileHandler->setFile($this->testFile);

        $this->assertSame($this->testFile, $fileHandler->getFilePath());
    }

    /**
     * @throws MimeDetectorException
     */
    public function testGetHashForExistingFile(): void
    {
        $fileHandler = new FileHandler();
        $hash = $fileHandler->getHash($this->testFile);

        $expectedHash = \hash_file('crc32b', $this->testFile);
        $this->assertSame($expectedHash, $hash);
    }

    /**
     * @throws MimeDetectorException
     */
    public function testGetHashForString(): void
    {
        $fileHandler = new FileHandler();
        $hash = $fileHandler->getHash('This is a test string.');

        $expectedHash = \hash('crc32b', 'This is a test string.');
        $this->assertSame($expectedHash, $hash);
    }

    public function testGetHashThrowsExceptionWhenHashFileFails(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->expectExceptionMessage("Unable to calculate the hash");

        \stream_wrapper_register('filehandler-hashfail', HashFailureStream::class);

        \set_error_handler(static function (): bool {
            return true;
        }, E_WARNING);

        try {
            $fileHandler = new FileHandler();
            $fileHandler->getHash('filehandler-hashfail://file.txt');
        } finally {
            \restore_error_handler();
            \stream_wrapper_unregister('filehandler-hashfail');
        }
    }

    public function testGetFileHashBeforeSettingFile(): void
    {
        $fileHandler = new FileHandler();

        // Initially, fileHash should be an empty string
        $this->assertSame('', $fileHandler->getFileHash());
    }

    public function testGetFilePathThrowsExceptionBeforeSettingFile(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->expectExceptionMessage('No file provided.');

        $fileHandler = new FileHandler();
        $fileHandler->getFilePath();
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses, PSR1.Methods.CamelCapsMethodName.NotCamelCaps

final class UnreadableFileStream
{
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return false;
    }

    public function stream_stat(): array
    {
        return [
            'mode' => 0100000,
            'size' => 0,
            'mtime' => \time(),
            'atime' => \time(),
            'ctime' => \time(),
        ];
    }

    public function url_stat(string $path, int $flags): array
    {
        return $this->stream_stat();
    }
}

final class HashFailureStream
{
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return false;
    }

    public function stream_stat(): array
    {
        return [
            'mode' => 0100644,
            'size' => 0,
            'mtime' => \time(),
            'atime' => \time(),
            'ctime' => \time(),
        ];
    }

    public function url_stat(string $path, int $flags): array
    {
        return $this->stream_stat();
    }
}

// phpcs:enable
