<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\MiscSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\MiscSignatureDetector
 */
final class MiscSignatureDetectorTest extends TestCase
{
    public function testDetectsMiscellaneousFormats(): void
    {
        $detector = new MiscSignatureDetector();

        foreach (self::provideMiscSignatures() as $label => [$data, $extension, $mimeType]) {
            $match = $this->detect($detector, $data);

            $this->assertInstanceOf(MimeTypeMatch::class, $match, $label);
            $this->assertSame($extension, $match->extension(), $label);
            $this->assertSame($mimeType, $match->mimeType(), $label);
        }
    }

    public function testDwgDetectionRequiresVersionBytes(): void
    {
        $detector = new MiscSignatureDetector();

        $match = $this->detect($detector, 'AC');

        $this->assertNull($match);
    }

    public function testDwgDetectionRequiresNumericVersion(): void
    {
        $detector = new MiscSignatureDetector();

        $match = $this->detect($detector, 'ACABCD');

        $this->assertNull($match);
    }

    public function testAsarDetectionReturnsNullForTruncatedHeaders(): void
    {
        $detector = new MiscSignatureDetector();
        $data = "\x04\x00\x00\x00" . \str_repeat("\0", 8);

        $match = $this->detect($detector, $data);

        $this->assertNull($match);
    }

    public function testAsarDetectionReturnsNullWhenDeclaredLengthExceedsCache(): void
    {
        $detector = new MiscSignatureDetector();
        $header = "\x04\x00\x00\x00" . \str_repeat("\0", 8);
        $data = $header . \pack('V', 64);

        $match = $this->detect($detector, $data);

        $this->assertNull($match);
    }

    public function testAsarDetectionReturnsNullWhenJsonSliceIsEmpty(): void
    {
        $detector = new MiscSignatureDetector();
        $json = '{"files": {"entry": {}}}';
        $length = \strlen($json);
        $header = "\x04\x00\x00\x00" . \str_repeat("\0", 8) . \pack('V', $length);
        $data = $header . $json;

        $file = \tempnam(\sys_get_temp_dir(), 'mime-asar-empty-');
        \file_put_contents($file, $data);

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);

            $bytesProperty = new ReflectionProperty(ByteCacheHandler::class, 'byteCache');
            $headerBytes = \array_slice($handler->getByteCache(), 0, 16);
            $bytesProperty->setValue($handler, $headerBytes);

            $lengthProperty = new ReflectionProperty(ByteCacheHandler::class, 'byteCacheLen');
            $lengthProperty->setValue($handler, 16 + $length);

            $context = new DetectionContext($file, $buffer);

            $match = $detector->detect($context);

            $this->assertNull($match);
        } finally {
            \unlink($file);
        }
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function provideMiscSignatures(): iterable
    {
        $dwg = 'AC1024' . \str_repeat("\0", 4);

        $asarHeader = "\x04\x00\x00\x00" . \str_repeat("\0", 8);
        $asarJson = '{"files":{"foo":{}}}';
        $asar = $asarHeader . \pack('V', \strlen($asarJson)) . $asarJson;

        $icc = \str_repeat("\0", 128);
        $icc = \substr_replace($icc, 'acsp', 36, 4);

        $shp = \str_repeat("\0", 14);
        $shp = \substr_replace($shp, "\x27\x0A\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 2, 12);

        return [
            'blend' => ['BLENDER', 'blend', 'application/x-blender'],
            'sqlite' => ['SQLi' . \str_repeat("\0", 2), 'sqlite', 'application/x-sqlite3'],
            'g3drem' => ['g3drem' . \str_repeat("\0", 2), 'g3drem', 'application/octet-stream'],
            'studio3' => ['silhouette05' . \str_repeat("\0", 2), 'studio3', 'application/octet-stream'],
            'draco' => ['DRACO' . \str_repeat("\0", 4), 'drc', 'application/vnd.google.draco'],
            'mie' => [\pack('C*', 0x7E, 0x10, 0x04, 0x00, 0x30, 0x4D, 0x49, 0x45), 'mie', 'application/x-mie'],
            'mie-alt' => [\pack('C*', 0x7E, 0x18, 0x04, 0x00, 0x30, 0x4D, 0x49, 0x45), 'mie', 'application/x-mie'],
            'dwg' => [$dwg, 'dwg', 'image/vnd.dwg'],
            'arrow' => ["ARROW1\x00\x00", 'arrow', 'application/vnd.apache.arrow.file'],
            'avro' => ['Obj' . "\x01", 'avro', 'application/avro'],
            'asar' => [$asar, 'asar', 'application/x-asar'],
            'glb' => ["\x67\x6C\x54\x46\x02\x00\x00\x00", 'glb', 'model/gltf-binary'],
            'fbx' => ['Kaydara FBX Binary  ' . "\x00", 'fbx', 'application/x.autodesk.fbx'],
            'icc' => [$icc, 'icc', 'application/vnd.iccprofile'],
            'pcap' => ["\xD4\xC3\xB2\xA1", 'pcap', 'application/vnd.tcpdump.pcap'],
            'pcap-be' => ["\xA1\xB2\xC3\xD4", 'pcap', 'application/vnd.tcpdump.pcap'],
            'dat' => ['regf' . \str_repeat("\0", 4), 'dat', 'application/x-ft-windows-registry-hive'],
            'alias' => [
                "\x62\x6F\x6F\x6B\x00\x00\x00\x00\x6D\x61\x72\x6B\x00\x00\x00\x00",
                'alias',
                'application/x.apple.alias',
            ],
            'lnk' => [
                "\x4C\x00\x00\x00\x01\x14\x02\x00\x00\x00\x00\x00\xC0\x00\x00\x00\x00\x00\x00\x46",
                'lnk',
                'application/x.ms.shortcut',
            ],
            'parquet' => ['PAR1' . \str_repeat("\0", 4), 'parquet', 'application/vnd.apache.parquet'],
            'parquet-alt' => ['PARE' . \str_repeat("\0", 4), 'parquet', 'application/vnd.apache.parquet'],
            'pgp' => ['-----BEGIN PGP MESSAGE-----', 'pgp', 'application/pgp-encrypted'],
            'shp' => [$shp, 'shp', 'application/x-esri-shape'],
            'stl' => ['solid model', 'stl', 'model/stl'],
            'unicorn' => ['UNICORN', 'unicorn', 'application/unicorn'],
            'ics' => ['BEGIN:VCALENDAR', 'ics', 'text/calendar'],
            'vcf' => ['BEGIN:VCARD', 'vcf', 'text/vcard'],
            'vtt' => ["WEBVTT\n", 'vtt', 'text/vtt'],
            'vtt-eof' => ['WEBVTT', 'vtt', 'text/vtt'],
            'reg' => ['REGEDIT4' . "\r\n", 'reg', 'application/x-ms-regedit'],
            'reg-win5' => ['Windows Registry Editor Version 5.00', 'reg', 'application/x-ms-regedit'],
            'reg-utf16' => [
                "\xFF\xFE" . self::toUtf16Le('Windows Registry Editor Version 5.00'),
                'reg',
                'application/x-ms-regedit',
            ],
        ];
    }

    private static function toUtf16Le(string $value): string
    {
        $encoded = '';

        for ($i = 0, $length = \strlen($value); $i < $length; $i++) {
            $encoded .= $value[$i] . "\x00";
        }

        return $encoded;
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(MiscSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-misc-');
        \file_put_contents($file, $data);

        try {
            $handler = new ByteCacheHandler($file);
            $buffer = new FileBuffer($handler);
            $context = new DetectionContext($file, $buffer);

            return $detector->detect($context);
        } finally {
            \unlink($file);
        }
    }
}
