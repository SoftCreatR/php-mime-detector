<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\MimeDetectorException;

class ByteCacheHandlerTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        // Create a temporary file for testing
        $this->testFile = \tempnam(\sys_get_temp_dir(), 'test');
        \file_put_contents($this->testFile, 'This is a test string for byte cache testing.');
    }

    protected function tearDown(): void
    {
        // Clean up the temporary file after testing
        if (\file_exists($this->testFile)) {
            \unlink($this->testFile);
        }
    }

    public function testConstructorThrowsExceptionForEmptyFile(): void
    {
        $this->expectException(MimeDetectorException::class);
        new ByteCacheHandler('');
    }

    /**
     * @throws MimeDetectorException
     */
    public function testConstructorInitializesByteCache(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertNotEmpty($byteCacheHandler->getByteCache());
        $this->assertEquals(4096, $byteCacheHandler->getMaxByteCacheLen());
        $this->assertEquals(
            \strlen('This is a test string for byte cache testing.'),
            $byteCacheHandler->getByteCacheLen()
        );
    }

    /**
     * @throws MimeDetectorException
     */
    public function testSetMaxByteCacheLenThrowsExceptionForSmallLength(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->expectException(MimeDetectorException::class);
        $byteCacheHandler->setMaxByteCacheLen(3);
    }

    /**
     * @throws MimeDetectorException
     */
    public function testSetMaxByteCacheLenSetsLength(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);
        $byteCacheHandler->setMaxByteCacheLen(1024);

        $this->assertEquals(1024, $byteCacheHandler->getMaxByteCacheLen());
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesReturnsTrueForMatchingBytes(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertTrue($byteCacheHandler->checkForBytes([84, 104, 105, 115])); // "This"
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesReturnsFalseForNonMatchingBytes(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertFalse($byteCacheHandler->checkForBytes([66, 67, 68])); // Non-matching bytes
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesWithInsufficientMask(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        // Setting the mask to be shorter than the bytes array.
        $this->assertFalse($byteCacheHandler->checkForBytes([115, 116, 114], 0, [255]));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesWithMismatchingMask(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        // Creating a mask that will cause the comparison to fail.
        $this->assertFalse($byteCacheHandler->checkForBytes([115, 116, 114], 0, [255, 255, 0]));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesWithInsufficientByteCache(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        // Truncate the byte cache to force the `!isset` condition to trigger.
        $reflection = new ReflectionClass($byteCacheHandler);
        $property = $reflection->getProperty('byteCache');
        $property->setAccessible(true);
        $property->setValue($byteCacheHandler, [115, 116]); // Setting a shorter byte cache.
        $property->setAccessible(false);

        $this->assertFalse($byteCacheHandler->checkForBytes([115, 116, 114])); // This should now fail.
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesWithEmptyBytesArray(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        // Pass an empty array as the "bytes" parameter.
        $this->assertFalse($byteCacheHandler->checkForBytes([]));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckForBytesWithEmptyByteCache(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        // Manually clear the byteCache property to simulate an empty byte cache.
        $reflection = new ReflectionClass($byteCacheHandler);
        $property = $reflection->getProperty('byteCache');
        $property->setAccessible(true);
        $property->setValue($byteCacheHandler, []); // Empty the byte cache.
        $property->setAccessible(false);

        $this->assertFalse($byteCacheHandler->checkForBytes([115, 116, 114])); // This should now fail.
    }

    /**
     * @throws MimeDetectorException
     */
    public function testSearchForBytesReturnsCorrectOffset(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertEquals(0, $byteCacheHandler->searchForBytes([84, 104, 105, 115])); // "This"

        // Adjusted the expected offset for "string" to 15
        $this->assertEquals(15, $byteCacheHandler->searchForBytes([115, 116, 114, 105, 110, 103])); // "string"
    }

    /**
     * @throws MimeDetectorException
     */
    public function testSearchForBytesReturnsNegativeOneForNoMatch(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertEquals(-1, $byteCacheHandler->searchForBytes([66, 67, 68])); // Non-matching bytes
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckStringReturnsTrueForMatchingString(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertTrue($byteCacheHandler->checkString('This'));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testCheckStringReturnsFalseForNonMatchingString(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertFalse($byteCacheHandler->checkString('That'));
    }

    /**
     * @throws MimeDetectorException
     */
    public function testToBytesConvertsStringToByteArray(): void
    {
        $byteCacheHandler = new ByteCacheHandler($this->testFile);

        $this->assertEquals([84, 104, 105, 115], $byteCacheHandler->toBytes('This')); // "This"
    }
}
