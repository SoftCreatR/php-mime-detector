<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\FileBuffer;

class FileBufferTest extends TestCase
{
    public function testDelegatesByteChecksToHandler(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->expects($this->once())
            ->method('checkForBytes')
            ->with([1, 2], 4, [255, 255])
            ->willReturn(true);

        $buffer = new FileBuffer($handler);

        $this->assertTrue($buffer->checkForBytes([1, 2], 4, [255, 255]));
    }

    public function testDelegatesSearchToHandler(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->expects($this->once())
            ->method('searchForBytes')
            ->with([3, 4], 2, [])
            ->willReturn(42);

        $buffer = new FileBuffer($handler);

        $this->assertSame(42, $buffer->searchForBytes([3, 4], 2));
    }

    public function testDelegatesStringChecksAndHelpers(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->expects($this->once())
            ->method('checkString')
            ->with('GIF', 1)
            ->willReturn(true);
        $handler->expects($this->once())
            ->method('toBytes')
            ->with('GIF')
            ->willReturn([71, 73, 70]);

        $buffer = new FileBuffer($handler);

        $this->assertTrue($buffer->checkString('GIF', 1));
        $this->assertSame([71, 73, 70], $buffer->toBytes('GIF'));
    }

    public function testExposesLengthAndSingleByte(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->expects($this->once())
            ->method('getByteCacheLen')
            ->willReturn(512);
        $handler->expects($this->once())
            ->method('getByte')
            ->with(10)
            ->willReturn(255);

        $buffer = new FileBuffer($handler);

        $this->assertSame(512, $buffer->length());
        $this->assertSame(255, $buffer->get(10));
    }

    public function testProvidesStringSlicesOfTheCachedBytes(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->method('getByteCache')->willReturn([65, 66, 67, 68]);

        $buffer = new FileBuffer($handler);

        $this->assertSame('ABCD', $buffer->sliceAsString());
        $this->assertSame('BC', $buffer->sliceAsString(1, 2));
    }

    public function testReturnsEmptyStringWhenByteCacheIsEmpty(): void
    {
        $handler = $this->createMock(ByteCacheHandler::class);
        $handler->expects($this->once())
            ->method('getByteCache')
            ->willReturn([]);

        $buffer = new FileBuffer($handler);

        $this->assertSame('', $buffer->sliceAsString());
    }
}
