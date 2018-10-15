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
        $mimeDetector = MimeDetector::getInstance();
        
        return $mimeDetector;
    }
    
    /**
     * @return  void
     */
    public function testGetInstance(): void
    {
        $mimeDetector = $this->getInstance();
        
        self::assertInstanceOf(MimeDetector::class, $mimeDetector);
        self::assertAttributeInternalType('array', 'byteCache', $mimeDetector);
        self::assertAttributeInternalType('int', 'byteCacheLen', $mimeDetector);
        self::assertAttributeInternalType('string', 'file', $mimeDetector);
        self::assertAttributeInternalType('string', 'fileHash', $mimeDetector);
    }
    
    /**
     * Test, if `setFile` throws an exception, if the provided file does not exist.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    public function testSetFileThrowsException(): void
    {
        self::expectException(MimeDetectorException::class);
        
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
            self::assertAttributeEquals($testFile['file'], 'file', $mimeDetector);
            self::assertAttributeEquals($testFile['hash'], 'fileHash', $mimeDetector);
        }
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
            
            self::assertInternalType('array', $fileData);
            self::assertEquals($testFile['ext'], $fileData['ext']);
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
        $detectedExtension = $mimeDetector->getFileExtension();
        
        self::assertInternalType('string', $detectedExtension);
        self::assertEmpty($detectedExtension);
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
            $detectedExtension = $mimeDetector->getFileExtension();
            
            self::assertInternalType('string', $detectedExtension);
            self::assertEquals($testFile['ext'], $detectedExtension);
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
        $detectedMimeType = $mimeDetector->getMimeType();
        
        self::assertInternalType('string', $detectedMimeType);
        self::assertEmpty($detectedMimeType);
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
            $detectedMimeType = $mimeDetector->getMimeType();
            
            // we don't know the mime type of our test file, so we'll just check, if any mimetype has been detected
            self::assertInternalType('string', $detectedMimeType);
            self::assertNotEmpty($detectedMimeType);
        }
    }
    
    /**
     * @return void
     */
    public function testToBytes(): void
    {
        $mimeDetector = $this->getInstance();
        $result = $mimeDetector->toBytes('php');
        
        self::assertInternalType('array', $result);
        self::assertEquals([112, 104, 112], $result);
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
        $result = $method->invoke($mimeDetector, 'php', 2);
        
        self::assertInternalType('bool', $result);
        self::assertTrue($result);
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
        $result = $method->invoke($mimeDetector, [0x66, 0x6F, 0x6F]); // foo
        
        self::assertInternalType('int', $result);
        self::assertEquals(-1, $result);
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
        $result = $method->invoke($mimeDetector, [0x70, 0x68, 0x70]); // php
        
        self::assertInternalType('int', $result);
        self::assertEquals(2, $result);
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
        $result = $method->invoke($mimeDetector, []);
        
        self::assertInternalType('bool', $result);
        self::assertFalse($result);
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
        $result = $method->invoke($mimeDetector, [0x70, 0x68, 0x70], 2); // php
        
        self::assertInternalType('bool', $result);
        self::assertTrue($result);
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
        $result = $method->invoke($mimeDetector);
        
        self::assertNull($result);
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
        self::expectException(MimeDetectorException::class);
        
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
}
