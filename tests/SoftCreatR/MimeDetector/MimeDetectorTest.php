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
        return new MimeDetector();
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
        $this->getInstance()->setFile('nonexistant.file');
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
            self::assertAttributeEquals(4096, 'maxByteCacheLen', $mimeDetector);
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
        self::assertEmpty($this->getInstance()->setFile(__FILE__)->getFileType());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetFileType(array $testFiles): void
    {
        foreach ($testFiles as $testFile) {
            $fileData = $this->getInstance()->setFile($testFile['file'])->getFileType();

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
        self::assertEmpty($this->getInstance()->setFile(__FILE__)->getFileExtension());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetFileExtension(array $testFiles): void
    {
        foreach ($testFiles as $testFile) {
            self::assertSame($testFile['ext'], $this->getInstance()->setFile($testFile['file'])->getFileExtension());
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
        self::assertEmpty($this->getInstance()->setFile(__FILE__)->getMimeType());
    }

    /**
     * @dataProvider    provideTestFiles
     * @param           array $testFiles
     * @return          void
     * @throws          MimeDetectorException
     */
    public function testGetMimeType(array $testFiles): void
    {
        foreach ($testFiles as $testFile) {
            // we don't know the mime type of our test file, so we'll just check, if any mimetype has been detected
            self::assertNotEmpty($this->getInstance()->setFile($testFile['file'])->getMimeType());
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
        foreach ($fontAwesomeIcons as $mimeType => $params) {
            self::assertSame('fa ' . $params[0], $this->getInstance()->getFontAwesomeIcon($mimeType, $params[1]));
        }

        $this->getInstance()->setFile(__FILE__);

        self::assertSame('fa fa-file-o', $this->getInstance()->getFontAwesomeIcon());
        self::assertSame('fa fa-file-o fa-fw', $this->getInstance()->getFontAwesomeIcon('', true));
    }

    /**
     * Test, if `getHash` returns the crc32b hash for this test class.
     *
     * @return void
     */
    public function testGetHashFile(): void
    {
        self::assertNotFalse($this->getInstance()->getHash(__FILE__));
    }

    /**
     * @return void
     */
    public function testGetHash(): void
    {
        self::assertSame('569121d1', $this->getInstance()->getHash('php'));
    }

    /**
     * @return void
     */
    public function testToBytes(): void
    {
        self::assertEquals([112, 104, 112], $this->getInstance()->toBytes('php'));
    }

    /**
     * Test, if `setByteCacheMaxLength` throws an exception, when being called too late.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testSetByteCacheMaxLengthThrowsExceptionWrongOrder(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->getInstance()->setFile(__FILE__)->setByteCacheMaxLength(123);
    }

    /**
     * Test, if `setByteCacheMaxLength` throws an exception, if the given max length is too small.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testSetByteCacheMaxLengthThrowsExceptionTooSmall(): void
    {
        $this->expectException(MimeDetectorException::class);
        $this->getInstance()->setByteCacheMaxLength(3);
    }

    /**
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testSetByteCacheMaxLength(): void
    {
        $mimeDetector = $this->getInstance();

        $mimeDetector->setByteCacheMaxLength(5);
        $mimeDetector->setFile(__FILE__);

        self::assertAttributeEquals(5, 'maxByteCacheLen', $mimeDetector);
        self::assertAttributeEquals(5, 'byteCacheLen', $mimeDetector);
        self::assertAttributeSame($mimeDetector->toBytes('<?php'), 'byteCache', $mimeDetector);
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

        self::assertEquals(-1, $method->invoke($mimeDetector, $mimeDetector->toBytes('foo')));
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

        self::assertEquals(2, $method->invoke($mimeDetector, $mimeDetector->toBytes('php')));
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

        self::assertTrue($method->invoke($mimeDetector, $mimeDetector->toBytes('php'), 2));
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
                    'hash' => $this->getInstance()->getHash($file->getPathname()),
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
