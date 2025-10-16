<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeDetectorException;

class MimeDetectorExceptionTest extends TestCase
{
    public function testFileDoesNotExistMessage(): void
    {
        $exception = MimeDetectorException::fileDoesNotExist('missing.txt');

        $this->assertSame("File 'missing.txt' does not exist.", $exception->getMessage());
    }

    public function testFileNotReadableMessage(): void
    {
        $exception = MimeDetectorException::fileNotReadable('locked.txt');

        $this->assertSame("File 'locked.txt' is not readable.", $exception->getMessage());
    }

    public function testInvalidByteCacheLengthMessage(): void
    {
        $maxLength = 1;
        $exception = MimeDetectorException::invalidByteCacheLength(1);

        $this->assertSame(
            'Maximum byte cache length "' . $maxLength . '" must not be smaller than 4.',
            $exception->getMessage()
        );
    }

    public function testMissingFilePathMessage(): void
    {
        $exception = MimeDetectorException::missingFilePath();

        $this->assertSame('No file provided.', $exception->getMessage());
    }

    public function testUnableToHashFileMessage(): void
    {
        $exception = MimeDetectorException::unableToHashFile('unhashable.txt');

        $this->assertSame("Unable to calculate the hash for 'unhashable.txt'.", $exception->getMessage());
    }
}
