<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoftCreatR\MimeDetector\Contract\MimeTypeResolverInterface;
use SoftCreatR\MimeDetector\FileHandler;
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;
use SoftCreatR\MimeDetector\MimeTypeDetector;

class MimeDetectorTest extends TestCase
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

    /**
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testConstructorInitializesComponents(): void
    {
        $mimeDetector = new MimeDetector($this->testFile);

        $this->assertInstanceOf(FileHandler::class, $this->getPrivateProperty($mimeDetector, 'fileHandler'));
        $this->assertInstanceOf(MimeTypeDetector::class, $this->getPrivateProperty($mimeDetector, 'mimeTypeDetector'));
    }

    public function testConstructorThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(MimeDetectorException::class);
        new MimeDetector('non_existent.file');
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetMimeType(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('text/plain');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('text/plain', $mimeDetector->getMimeType());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFileExtension(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getFileExtension')->willReturn('txt');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('txt', $mimeDetector->getFileExtension());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetExtensionForMimeType(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getExtensionForMimeType')->with('audio/mpeg')->willReturn('mp3');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('mp3', $mimeDetector->getExtensionForMimeType('audio/mpeg'));
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetMimeTypesForExtension(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock
            ->method('getMimeTypesForExtension')
            ->with('mp4')
            ->willReturn(['video/mp4', 'audio/mpeg']);

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame(['video/mp4', 'audio/mpeg'], $mimeDetector->getMimeTypesForExtension('mp4'));
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testListAllMimeTypes(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('listAllMimeTypes')->willReturn([
            'audio/mpeg' => ['mp2', 'mp3', 'mp4'],
        ]);

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame([
            'audio/mpeg' => ['mp2', 'mp3', 'mp4'],
        ], $mimeDetector->listAllMimeTypes());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFileHash(): void
    {
        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock->method('getFileHash')->willReturn('fakehash123');

        $mimeDetector = $this->createMimeDetectorWithMocks(null, $fileHandlerMock);

        $this->assertSame('fakehash123', $mimeDetector->getFileHash());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetBase64DataURI(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('text/plain');

        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock->method('getFileHash')->willReturn($this->testFile);
        $fileHandlerMock->method('getFilePath')->willReturn($this->testFile);

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock, $fileHandlerMock);

        $base64String = \base64_encode(\file_get_contents($this->testFile));
        $expectedURI = 'data:text/plain;base64,' . $base64String;

        $this->assertSame($expectedURI, $mimeDetector->getBase64DataURI());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetBase64DataURIReturnsEmptyWhenMimeTypeMissing(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('');

        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock->method('getFileHash')->willReturn($this->testFile);
        $fileHandlerMock->method('getFilePath')->willReturn($this->testFile);

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock, $fileHandlerMock);

        $this->assertSame('', $mimeDetector->getBase64DataURI());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetBase64DataURIReturnsEmptyWhenFileIsUnreadable(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('text/plain');

        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock->method('getFileHash')->willReturn('/path/does/not/exist');
        $fileHandlerMock->method('getFilePath')->willReturn('/path/does/not/exist');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock, $fileHandlerMock);

        $this->assertSame('', $mimeDetector->getBase64DataURI());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetBase64DataURIReturnsEmptyWhenFileContentIsEmpty(): void
    {
        $emptyFile = \tempnam(\sys_get_temp_dir(), 'mime-empty-');
        \file_put_contents($emptyFile, '');

        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('text/plain');

        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock->method('getFileHash')->willReturn($emptyFile);
        $fileHandlerMock->method('getFilePath')->willReturn($emptyFile);

        try {
            $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock, $fileHandlerMock);

            $this->assertSame('', $mimeDetector->getBase64DataURI());
        } finally {
            \unlink($emptyFile);
        }
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetBase64DataURIReturnsEmptyWhenFilePathUnavailable(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('text/plain');

        $fileHandlerMock = $this->createMock(FileHandler::class);
        $fileHandlerMock
            ->method('getFileHash')
            ->willReturn($this->testFile);
        $fileHandlerMock
            ->method('getFilePath')
            ->willThrowException(MimeDetectorException::missingFilePath());

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock, $fileHandlerMock);

        $this->assertSame('', $mimeDetector->getBase64DataURI());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFontAwesomeIconReturnsDefault(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('application/octet-stream');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('fa fa-file-o', $mimeDetector->getFontAwesomeIcon());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFontAwesomeIconForImage(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('image/jpeg');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('fa fa-file-image-o', $mimeDetector->getFontAwesomeIcon());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFontAwesomeIconForAudio(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('audio/mpeg');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('fa fa-file-audio-o', $mimeDetector->getFontAwesomeIcon());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFontAwesomeIconForVideo(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('video/mp4');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('fa fa-file-video-o', $mimeDetector->getFontAwesomeIcon());
    }

    /**
     * @throws Exception
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    public function testGetFontAwesomeIconWithFixedWidth(): void
    {
        $mimeTypeDetectorMock = $this->createMock(MimeTypeResolverInterface::class);
        $mimeTypeDetectorMock->method('getMimeType')->willReturn('image/png');

        $mimeDetector = $this->createMimeDetectorWithMocks($mimeTypeDetectorMock);

        $this->assertSame('fa fa-file-image-o fa-fw', $mimeDetector->getFontAwesomeIcon('', true));
    }

    /**
     * @throws MimeDetectorException
     * @throws ReflectionException
     */
    private function createMimeDetectorWithMocks(
        ?MimeTypeResolverInterface $mimeTypeDetectorMock = null,
        ?FileHandler $fileHandlerMock = null
    ): MimeDetector {
        $mimeDetector = new MimeDetector($this->testFile, null, null, $mimeTypeDetectorMock);

        if ($fileHandlerMock !== null) {
            $this->setPrivateProperty($mimeDetector, 'fileHandler', $fileHandlerMock);
        }

        return $mimeDetector;
    }

    /**
     * @throws ReflectionException
     */
    private function getPrivateProperty($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);

        return $property->getValue($object);
    }

    /**
     * @throws ReflectionException
     */
    private function setPrivateProperty($object, $property, $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setValue($object, $value);
    }
}
