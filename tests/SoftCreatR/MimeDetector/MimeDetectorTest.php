<?php
declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use DirectoryIterator;
use PHPUnit\Framework\TestCase as TestCaseImplementation;
use ReflectionException;
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * Tests for MimeDetector
 */
class MimeDetectorTest extends TestCaseImplementation
{
    /**
     * @return  MimeDetector
     */
    public function getInstance(): MimeDetector
    {
        return MimeDetector::getInstance();
    }

    /**
     * @return  void
     */
    public function testGetInstance(): void
    {
        self::assertInstanceOf(MimeDetector::class, $this->getInstance());
    }

    /**
     * Test, if `setFile` throws an exception, if the provided file does not exist.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testSetFileThrowsException(): void
    {
        $this->expectException(MimeDetectorException::class);

        $mimeDetector = MimeDetector::getInstance();
        $mimeDetector->setFile('nonexistant.file');
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testSetFile($testFiles): void
    {
        $mimeDetector = $this->getInstance();

        foreach ($testFiles as $testFile) {
            $mimeDetector->setFile($testFile['file']);

            self::assertAttributeNotEmpty('byteCache', $mimeDetector);
            self::assertAttributeGreaterThanOrEqual(1, 'byteCacheLen', $mimeDetector);
            self::assertAttributeSame($testFile['file'], 'file', $mimeDetector);
            self::assertAttributeSame($testFile['hash'], 'fileHash', $mimeDetector);
        }
    }

    /**
     * Test, if `getFileType` returns an empty array, if the bytecache is empty (i.e. empty file provided).
     *
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testGetFileTypeReturnEmptyArrayWithoutByteCache(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);

        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'byteCache', []);
        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'file', '');
        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'fileHash', '');

        self::assertEmpty($mimeDetector->getFileType());
    }

    /**
     * Test, if `getFileType` returns an empty array, if the file type is unknown.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testGetFileTypeReturnEmptyArrayWithUnknownFileType(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);

        self::assertEmpty($mimeDetector->getFileType());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetFileType(array $testFiles): void
    {
        $mimeDetector = $this->getInstance();

        foreach ($testFiles as $testFile) {
            $mimeDetector->setFile($testFile['file']);
            $fileData = $mimeDetector->getFileType();

            self::assertSame($testFile['ext'], $fileData['ext']);
        }
    }

    /**
     * Test, if `getFileExtension` returns an empty string, if the file type of the provided file cannot be determined.
     *
     * @dataProvider    provideTestFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetFileExtensionEmpty(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);

        self::assertEmpty($mimeDetector->getFileExtension());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetFileExtension(array $testFiles): void
    {
        $mimeDetector = $this->getInstance();

        foreach ($testFiles as $testFile) {
            $mimeDetector->setFile($testFile['file']);

            self::assertSame($testFile['ext'], $mimeDetector->getFileExtension());
        }
    }

    /**
     * Test, if `getMimeType` returns an empty string, if the file type of the provided file cannot be determined.
     *
     * @dataProvider    provideTestFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetMimeTypeEmpty(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);

        self::assertEmpty($mimeDetector->getMimeType());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetMimeType(array $testFiles): void
    {
        $mimeDetector = $this->getInstance();

        foreach ($testFiles as $testFile) {
            $mimeDetector->setFile($testFile['file']);

            // we don't know the mime type of our test file, so we'll just check, if any mimetype has been detected
            self::assertNotEmpty($mimeDetector->getMimeType());
        }
    }

    /**
     * @dataProvider    provideFontAwesomeIcons
     * @param   array   $fontAwesomeIcons
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testGetFontAwesomeIcon(array $fontAwesomeIcons): void
    {
        $mimeDetector = $this->getInstance();

        foreach ($fontAwesomeIcons as $mimeType => $params) {
            self::assertSame('fa ' . $params[0], $mimeDetector->getFontAwesomeIcon($mimeType, $params[1]));
        }

        $mimeDetector->setFile(__FILE__);

        self::assertSame('fa fa-file-o', $mimeDetector->getFontAwesomeIcon());
        self::assertSame('fa fa-file-o fa-fw', $mimeDetector->getFontAwesomeIcon('', true));
    }

    /**
     * @return void
     */
    public function testToBytes(): void
    {
        $mimeDetector = $this->getInstance();

        self::assertEquals([112, 104, 112], $mimeDetector->toBytes('php'));
    }

    /**
     * @return  void
     * @throws  ReflectionException
     * @throws  MimeDetectorException
     */
    public function testCheckString(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'checkString');

        self::assertTrue($method->invoke($mimeDetector, 'php', 2));
    }

    /**
     * Test, if `searchForBytes` returns -1, if a byte array is provided, that isn't in the cached byte array.
     *
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testSearchForBytesNegative(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'searchForBytes');

        self::assertEquals(-1, $method->invoke($mimeDetector, [0x66, 0x6F, 0x6F]));
    }

    /**
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testSearchForBytes(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'searchForBytes');

        self::assertEquals(2, $method->invoke($mimeDetector, [0x70, 0x68, 0x70]));
    }

    /**
     * Test, if `checkForBytes` returns false, if an empty byte array is provided.
     *
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testCheckForBytesFalse(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'checkForBytes');

        self::assertFalse($method->invoke($mimeDetector, []));
    }

    /**
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testCheckForBytes(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'checkForBytes');

        self::assertTrue($method->invoke($mimeDetector, [0x70, 0x68, 0x70], 2));
    }

    /**
     * Test, if `createByteCache` returns early.
     *
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testCreateByteCacheNull(): void
    {
        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);
        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'createByteCache');

        self::assertNull($method->invoke($mimeDetector));
    }

    /**
     * Test, if `createByteCache` throws a MimeDetectorException.
     *
     * @return  void
     * @throws  MimeDetectorException
     * @throws  ReflectionException
     */
    public function testCreateByteCacheException(): void
    {
        $this->expectException(MimeDetectorException::class);

        $mimeDetector = $this->getInstance();
        $mimeDetector->setFile(__FILE__);

        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'byteCache', []);
        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'file', '');
        MimeDetectorTestUtil::setPrivateProperty($mimeDetector, 'fileHash', '');

        $method = MimeDetectorTestUtil::getProtectedMethod($mimeDetector, 'createByteCache');
        $method->invoke($mimeDetector);
    }

    /**
     * Returns an array of all existing test files and their corresponding CRC32b hashes.
     *
     * @return array
     */
    public function provideTestFiles(): array
    {
        $files = [];

        foreach (new DirectoryIterator(__DIR__ . '/fixtures') as $file) {
            if ($file->isFile() && $file->getBasename() !== '.git') {
                $files[$file->getBasename()] = [
                    'file' => $file->getPathname(),
                    'hash' => hash_file('crc32b', $file->getPathname()),
                    'ext' => $file->getExtension()
                ];
            }
        }

        return [[$files]];
    }

    /**
     * Returns an array of all existing test files and their corresponding CRC32b hashes.
     *
     * @return array
     */
    public function provideFontAwesomeIcons(): array
    {
        return [[[
            'application/application/vnd.oasis.opendocument.spreadsheet' => ['fa-file-excel-o', false],
            'application/gzip' => ['fa-file-archive-o', false],
            'application/json' => ['fa-file-code-o', false],
            'application/msword' => ['fa-file-word-o', false],
            'application/pdf' => ['fa-file-pdf-o', false],
            'application/vnd.ms-excel' => ['fa-file-excel-o', false],
            'application/vnd.ms-powerpoint' => ['fa-file-powerpoint-o', false],
            'application/vnd.ms-word' => ['fa-file-word-o', false],
            'application/vnd.oasis.opendocument.presentation' => ['fa-file-powerpoint-o', false],
            'application/vnd.oasis.opendocument.spreadsheet' => ['fa-file-excel-o', false],
            'application/vnd.oasis.opendocument.text' => ['fa-file-word-o', false],
            'application/vnd.openxmlformats-officedocument.presentationml' => ['fa-file-powerpoint-o', false],
            'application/vnd.openxmlformats-officedocument.spreadsheetml' => ['fa-file-excel-o', false],
            'application/vnd.openxmlformats-officedocument.wordprocessingml' => ['fa-file-word-o', false],
            'application/zip' => ['fa-file-archive-o', false],
            'audio' => ['fa-file-audio-o', false],
            'image' => ['fa-file-image-o', false],
            'video' => ['fa-file-video-o', false]
        ]]];
    }
}
