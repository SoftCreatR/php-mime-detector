<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\MediaSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\MediaSignatureDetector
 */
final class MediaSignatureDetectorTest extends TestCase
{
    public function testReturnsNullWhenNoSignatureMatches(): void
    {
        $detector = new MediaSignatureDetector();
        $data = 'plain-text-without-media-markers';

        $match = $this->detect($detector, $data);

        $this->assertNull($match);
    }

    public function testRejectsInvalidFrameAndTagHeaders(): void
    {
        foreach (
            [
                "\xFF\xFEW\0i\0n\0d\0o\0w\0s\0",
                "\xFF\xFE<\0?\0x\0m\0l\0",
                "\xFF\xF1\x50\x80\0\0\0",
                "\xFF\xF1\x7C\x80\x01\x3F\xFC",
                "\xFF\xEB\x90\0",
                "\xFF\xFB\xFC\0",
                "\xFF\xFB\x9C\0",
                "ID3\x04\0\0\x80\0\0\0\xFF\xFB\x90\0",
                "ID3\x04\0\0\x7F\x7F\x7F\x7F\xFF\xFB\x90\0",
                "ID3\x04\0\0\0\0\0\0",
            ] as $data
        ) {
            $this->assertNull($this->detect(new MediaSignatureDetector(), $data));
        }
    }

    public function testDetectsAudioAfterId3Footer(): void
    {
        $data = "ID3\x04\0\x10\0\0\0\0" . "3DI\x04\0\x10\0\0\0\0" . "\xFF\xFB\x90\0";

        $this->assertSame('mp3', $this->detect(new MediaSignatureDetector(), $data)?->extension());
    }

    public function testDefaultsToOgxForUnknownOggStreams(): void
    {
        $detector = new MediaSignatureDetector();
        $data = "\x4F\x67\x67\x53" . \str_repeat("\x00", 40);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('ogx', $match->extension());
        $this->assertSame('application/ogg', $match->mimeType());
    }

    #[DataProvider('provideContainerSamples')]
    public function testParsesContainerBoundaries(string $data, ?string $extension): void
    {
        $this->assertSame($extension, $this->detect(new MediaSignatureDetector(), $data)?->extension());
    }

    public static function provideContainerSamples(): iterable
    {
        $ebml = "\x1A\x45\xDF\xA3";
        $docType = "\x42\x82\x88matroska";

        yield 'two-byte root and child size' => [$ebml . "\x40\x0C\x42\x82\x40\x08matroska", 'mkv'];
        yield 'eight-byte size' => [$ebml . "\x01\0\0\0\0\0\0\x0B" . $docType, 'mkv'];
        yield 'padded document type' => [$ebml . "\x88\x42\x82\x85webm\0", 'webm'];
        yield 'marker in a void element' => [$ebml . "\x8D\xEC\x8B" . $docType, null];
        yield 'marker after header' => [$ebml . "\x80" . $docType, null];
        yield 'truncated header' => [$ebml . "\x8C" . $docType, null];
        yield 'child exceeds header' => [$ebml . "\x8B\x42\x82\x89matroska", null];
        yield 'invalid child ID' => [$ebml . "\x8C\0" . $docType, null];
        yield 'truncated integer' => [$ebml . "\x81\x40", null];
        yield 'unknown root size' => [$ebml . "\xFF" . $docType, null];
        yield 'unknown child size' => [$ebml . "\x8B\x42\x82\xFFmatroska", null];
        yield 'integer overflow' => [$ebml . "\x01\x7F\xFF\xFF\xFF\xFF\xFF\xFF" . $docType, null];
        yield 'duplicate document type' => [$ebml . "\x96" . $docType . $docType, null];
        yield 'incomplete trailing element' => [$ebml . "\x8C" . $docType . "\xEC", null];
        yield 'unrecognized document type' => [$ebml . "\x8B\x42\x82\x88otherdoc", null];
        yield 'empty document type' => [$ebml . "\x83\x42\x82\x80", null];
        yield 'oversized header' => [$ebml . "\x50\0" . $docType . \str_repeat("\0", 4096), null];
        yield 'DocType prefix alone' => [$ebml . "\x8E\x42\x82\x8Bmatroska-old", null];

        yield 'two Ogg laces' => [self::createOggPage(\str_pad('OpusHead', 300, "\0")), 'opus'];
        yield 'maximum Ogg segment table' => [self::createOggPage('OpusHead', 2, 255), 'opus'];
        yield 'empty first Ogg packet' => [self::createOggPage('OpusHead', 2, 2, true), 'ogx'];
        yield 'Ogg codec prefix outside first packet' => [self::createOggPage('OpusHead', 2, 1, false, 4), 'ogx'];
        yield 'continued Ogg packet' => [self::createOggPage('OpusHead', 1), 'ogx'];
        yield 'Ogg page without BOS flag' => [self::createOggPage('OpusHead', 0), 'ogx'];
        yield 'Opus marker outside Ogg' => [\str_repeat("\0", 28) . 'OpusHead', null];
        yield 'unsupported Ogg version' => ["OggS\x01" . \str_repeat("\0", 30), null];
        yield 'reserved Ogg flags' => [self::createOggPage('OpusHead', 8), null];
        yield 'incomplete Ogg header' => ['OggS', null];
        yield 'incomplete Ogg segment table' => ['OggS' . \str_repeat("\0", 22) . "\x02\x13", null];
        yield 'IFF bitmap is not AIFF' => ['FORM' . \pack('N', 4) . 'ILBM', null];
        yield 'truncated AIFF' => ['FORM' . "\0", null];
        yield 'invalid CAF version' => ["caff\0\x02\0\0desc" . \pack('J', 32) . \str_repeat("\0", 32), null];
        yield 'invalid CAF description size' => ["caff\0\x01\0\0desc" . \pack('J', 31) . \str_repeat("\0", 32), null];
        yield 'incomplete CAF description' => ["caff\0\x01\0\0desc" . \pack('J', 32), null];
        yield 'AMR-WB newline required' => ['#!AMR-WB', null];
        yield 'multi-channel AMR reserved bits' => ["#!AMR_MC1.0\n\xFF\xFF\xFF\xF2", 'amr'];
        yield 'multi-channel AMR-WB' => ["#!AMR-WB_MC1.0\n" . \pack('N', 2), 'awb'];
        yield 'invalid multi-channel AMR count' => ["#!AMR_MC1.0\n" . \pack('N', 0), null];
        yield 'incomplete multi-channel AMR description' => ["#!AMR_MC1.0\n\0", null];
    }

    public function testDetectsM4aBrandFromIsoContainer(): void
    {
        $detector = new MediaSignatureDetector();
        $data = self::createIsoBrandSample('M4A ');

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('m4a', $match->extension());
        $this->assertSame('audio/x-m4a', $match->mimeType());
    }

    public function testDetects3gpSignatureWhenBrandParsingFails(): void
    {
        $detector = new MediaSignatureDetector();
        $data = "\x00\x00\x00\x10ftyp3g";

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('3gp', $match->extension());
        $this->assertSame('video/3gpp', $match->mimeType());
    }

    public function testIsoBrandWithoutIdentifierReturnsNull(): void
    {
        $detector = new MediaSignatureDetector();
        $data = "\x00\x00\x00\x18ftyp" . \str_repeat(' ', 4) . \str_repeat("\0", 8);

        $match = $this->detect($detector, $data);

        $this->assertNull($match);
    }

    public function testDetectsIsoBrandSpecificMatches(): void
    {
        $detector = new MediaSignatureDetector();

        foreach (self::provideIsoBrandMatches() as $label => [$brand, $extension, $mimeType]) {
            $data = self::createIsoBrandSample($brand);
            $match = $this->detect($detector, $data);

            $this->assertInstanceOf(MimeTypeMatch::class, $match, $label);
            $this->assertSame($extension, $match->extension(), $label);
            $this->assertSame($mimeType, $match->mimeType(), $label);
        }
    }

    public function testDetectsAdditionalMediaFormats(): void
    {
        $detector = new MediaSignatureDetector();

        foreach (self::provideAdditionalMediaSamples() as $label => [$data, $extension, $mimeType]) {
            $match = $this->detect($detector, $data);

            $this->assertInstanceOf(MimeTypeMatch::class, $match, $label);
            $this->assertSame($extension, $match->extension(), $label);
            $this->assertSame($mimeType, $match->mimeType(), $label);
        }
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function provideAdditionalMediaSamples(): iterable
    {
        $asfHeader = "\x30\x26\xB2\x75\x8E\x66\xCF\x11\xA6\xD9\x00\xAA\x00\x62\xCE\x6C";
        $videoStream = "\xC0\xEF\x19\xBC\x4D\x5B\xCF\x11\xA8\xFD\x00\x80\x5F\x5C\x44\x2B";
        $audioStream = "\x40\x9E\x69\xF8\x4D\x5B\xCF\x11\xA8\xFD\x00\x80\x5F\x5C\x44\x2B";

        return [
            'mp-plus' => ['MP+' . \str_repeat("\0", 2), 'mpc', 'audio/x-musepack'],
            'ac3' => ["\x0B\x77" . \str_repeat("\0", 10), 'ac3', 'audio/vnd.dolby.dd-raw'],
            'mpc' => ['MPCK' . \str_repeat("\0", 4), 'mpc', 'audio/x-musepack'],
            'dsf' => ['DSD ' . \str_repeat("\0", 4), 'dsf', 'audio/x-dsf'],
            'mp4-box' => ["\x33\x67\x70\x35" . \str_repeat("\0", 4), 'mp4', 'video/mp4'],
            'mid' => ['MThd' . \str_repeat("\0", 4), 'mid', 'audio/midi'],
            'mkv' => ["\x1A\x45\xDF\xA3\x8B\x42\x82\x88matroska", 'mkv', 'video/x-matroska'],
            'webm' => ["\x1A\x45\xDF\xA3\x87\x42\x82\x84webm", 'webm', 'video/webm'],
            'mov-free' => [\str_repeat("\0", 4) . 'free' . \str_repeat("\0", 4), 'mov', 'video/quicktime'],
            'rm' => ['.RMF' . \str_repeat("\0", 4), 'rm', 'application/vnd.rn-realmedia'],
            'avi' => ['RIFF' . \str_repeat("\0", 4) . 'AVI ', 'avi', 'video/vnd.avi'],
            'wav' => ['RIFF' . \str_repeat("\0", 4) . 'WAVE', 'wav', 'audio/vnd.wave'],
            'qcp' => ['RIFF' . \str_repeat("\0", 4) . 'QLCM', 'qcp', 'audio/qcelp'],
            'ani' => ['RIFF' . \str_repeat("\0", 4) . 'ACON', 'ani', 'application/x-navi-animation'],
            'wmv' => [$asfHeader . $videoStream, 'wmv', 'video/x-ms-wmv'],
            'wma' => [$asfHeader . $audioStream, 'wma', 'audio/x-ms-wma'],
            'asf' => [$asfHeader . \str_repeat("\0", 16), 'asf', 'video/x-ms-asf'],
            'mpg-pack' => ["\x00\x00\x01\xBA" . \str_repeat("\0", 4), 'mpg', 'video/mpeg'],
            'mpg-sequence' => ["\x00\x00\x01\xB3" . \str_repeat("\0", 4), 'mpg', 'video/mpeg'],
            '3gp-signature' => ["\x00\x00\x00\x10ftyp3g" . \str_repeat("\0", 2), '3gp', 'video/3gpp'],
            'mts' => [self::createMtsSample(), 'mts', 'video/mp2t'],
            'it' => ['IMPM' . \str_repeat("\0", 4), 'it', 'audio/x-it'],
            's3m' => [self::createS3mSample(), 's3m', 'audio/x-s3m'],
            'xm' => ['Extended Module:' . \str_repeat("\0", 4), 'xm', 'audio/x-xm'],
            'voc' => ['Creative Voice File' . \str_repeat("\0", 2), 'voc', 'audio/x-voc'],
            'mp3-id3' => ["ID3\x04\0\0\0\0\0\0\xFF\xFB\x90\0", 'mp3', 'audio/mpeg'],
            'mp3-ffe2' => ["\xFF\xE2" . \str_repeat("\0", 18), 'mp3', 'audio/mpeg'],
            'mp2-ffe4' => ["\xFF\xE4" . \str_repeat("\0", 18), 'mp2', 'audio/mpeg'],
            'mp2-fff4' => ["\xFF\xF4" . \str_repeat("\0", 18), 'mp2', 'audio/mpeg'],
            'AAC ADTS' => ["\xFF\xF1\x50\x80\x01\x3F\xFC\0\0", 'aac', 'audio/aac'],
            'm4a-audio-marker' => ["\x00\x00\x00\x0BftypM4A", 'm4a', 'audio/mp4'],
            'opus' => [self::createOggPage('OpusHead'), 'opus', 'audio/opus'],
            'ogv' => [self::createOggPage("\x80theora"), 'ogv', 'video/ogg'],
            'ogm' => [self::createOggPage("\x01video\x00"), 'ogm', 'video/ogg'],
            'oga' => [self::createOggPage("\x7FFLAC"), 'oga', 'audio/ogg'],
            'spx' => [self::createOggPage('Speex  '), 'spx', 'audio/ogg'],
            'ogg' => [self::createOggPage("\x01vorbis"), 'ogg', 'audio/ogg'],
            'flac' => ['fLaC' . \str_repeat("\0", 4), 'flac', 'audio/x-flac'],
            'ape' => ['MAC ' . \str_repeat("\0", 4), 'ape', 'audio/ape'],
            'wavpack' => ['wvpk' . \str_repeat("\0", 4), 'wv', 'audio/wavpack'],
            'amr' => ["#!AMR\n" . \str_repeat("\0", 4), 'amr', 'audio/amr'],
            'amr-wb' => ["#!AMR-WB\n\x7C", 'awb', 'audio/amr-wb'],
            'aif' => ['FORM' . \pack('N', 4) . 'AIFF', 'aif', 'audio/aiff'],
            'aifc' => ['FORM' . \pack('N', 4) . 'AIFC', 'aif', 'audio/aiff'],
            'caf' => ["caff\0\x01\xFF\xFFdesc" . \pack('J', 32) . \str_repeat("\0", 32), 'caf', 'audio/x-caf'],
            'mxf' => [
                \pack('C*', 0x06, 0x0E, 0x2B, 0x34, 0x02, 0x05, 0x01, 0x01, 0x0D, 0x01, 0x02, 0x01, 0x01, 0x02),
                'mxf',
                'application/mxf',
            ],
            'flv' => ['FLV' . "\x01" . \str_repeat("\0", 4), 'flv', 'video/x-flv'],
            'au' => ['.snd' . \str_repeat("\0", 4), 'au', 'audio/basic'],
        ];
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function provideIsoBrandMatches(): iterable
    {
        return [
            'quicktime' => ['qt  ', 'mov', 'video/quicktime'],
            '3gp' => ['3gp ', '3gp', 'video/3gpp'],
            '3g2' => ['3g2a', '3g2', 'video/3gpp2'],
            'm4v' => ['M4V ', 'm4v', 'video/x-m4v'],
            'f4b' => ['F4B ', 'f4b', 'audio/mp4'],
        ];
    }

    private static function createOggPage(
        string $packet,
        int $flags = 2,
        ?int $segmentCount = null,
        bool $emptyFirstPacket = false,
        ?int $declaredLength = null,
    ): string {
        $length = $declaredLength ?? \strlen($packet);
        $laces = \str_repeat("\xFF", \intdiv($length, 255)) . \chr($length % 255);

        if ($emptyFirstPacket) {
            $laces = "\0" . $laces;
        }

        if ($segmentCount !== null) {
            $laces = \str_pad($laces, $segmentCount, "\0");
        }

        return "OggS\0" . \chr($flags) . \str_repeat("\0", 20) . \chr(\strlen($laces)) . $laces . $packet;
    }

    private static function createIsoBrandSample(string $brand): string
    {
        $brand = \substr($brand, 0, 4);
        $brand = \str_pad($brand, 4);

        return "\x00\x00\x00\x18ftyp" . $brand . \str_repeat("\x00", 8);
    }

    private static function createMtsSample(): string
    {
        $data = \str_repeat("\0", 200);
        $data[0] = "\x47";
        $data[188] = "\x47";

        return $data;
    }

    private static function createS3mSample(): string
    {
        $data = \str_repeat("\0", 48);

        return \substr_replace($data, 'SCRM', 44, 4);
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(MediaSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-media-');
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
