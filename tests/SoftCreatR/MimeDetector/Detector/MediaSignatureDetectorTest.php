<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Detector;

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

    public function testDefaultsToOgxForUnknownOggStreams(): void
    {
        $detector = new MediaSignatureDetector();
        $data = "\x4F\x67\x67\x53" . \str_repeat("\x00", 40);

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame('ogx', $match->extension());
        $this->assertSame('application/ogg', $match->mimeType());
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
        return [
            'mp-plus' => ['MP+' . \str_repeat("\0", 2), 'mpc', 'audio/x-musepack'],
            'ac3' => ["\x0B\x77" . \str_repeat("\0", 10), 'ac3', 'audio/vnd.dolby.dd-raw'],
            'mpc' => ['MPCK' . \str_repeat("\0", 4), 'mpc', 'audio/x-musepack'],
            'dsf' => ['DSD ' . \str_repeat("\0", 4), 'dsf', 'audio/x-dsf'],
            'mp4-box' => ["\x33\x67\x70\x35" . \str_repeat("\0", 4), 'mp4', 'video/mp4'],
            'mid' => ['MThd' . \str_repeat("\0", 4), 'mid', 'audio/midi'],
            'mkv' => ["\x1A\x45\xDF\xA3\x00\x00\x42\x82\x08matroska", 'mkv', 'video/x-matroska'],
            'webm' => ["\x1A\x45\xDF\xA3\x00\x00\x42\x82\x08webm", 'webm', 'video/webm'],
            'mov-free' => [\str_repeat("\0", 4) . 'free' . \str_repeat("\0", 4), 'mov', 'video/quicktime'],
            'rm' => ['.RMF' . \str_repeat("\0", 4), 'rm', 'application/vnd.rn-realmedia'],
            'avi' => ['RIFF' . \str_repeat("\0", 4) . 'AVI ', 'avi', 'video/vnd.avi'],
            'wav' => ['RIFF' . \str_repeat("\0", 4) . 'WAVE', 'wav', 'audio/vnd.wave'],
            'qcp' => ['RIFF' . \str_repeat("\0", 4) . 'QLCM', 'qcp', 'audio/qcelp'],
            'ani' => ['RIFF' . \str_repeat("\0", 4) . 'ACON', 'ani', 'application/x-navi-animation'],
            'wmv' => ["\x30\x26\xB2\x75\x8E\x66\xCF\x11\xA6\xD9", 'wmv', 'video/x-ms-wmv'],
            'mpg-pack' => ["\x00\x00\x01\xBA" . \str_repeat("\0", 4), 'mpg', 'video/mpeg'],
            'mpg-sequence' => ["\x00\x00\x01\xB3" . \str_repeat("\0", 4), 'mpg', 'video/mpeg'],
            '3gp-signature' => ["\x00\x00\x00\x10ftyp3g" . \str_repeat("\0", 2), '3gp', 'video/3gpp'],
            'mts' => [self::createMtsSample(), 'mts', 'video/mp2t'],
            'it' => ['IMPM' . \str_repeat("\0", 4), 'it', 'audio/x-it'],
            's3m' => [self::createS3mSample(), 's3m', 'audio/x-s3m'],
            'xm' => ['Extended Module:' . \str_repeat("\0", 4), 'xm', 'audio/x-xm'],
            'voc' => ['Creative Voice File' . \str_repeat("\0", 2), 'voc', 'audio/x-voc'],
            'mp3-id3' => [\str_pad('ID3', 20, "\0"), 'mp3', 'audio/mpeg'],
            'mp3-ffe2' => ["\xFF\xE2" . \str_repeat("\0", 18), 'mp3', 'audio/mpeg'],
            'mp2-ffe4' => ["\xFF\xE4" . \str_repeat("\0", 18), 'mp2', 'audio/mpeg'],
            'mp2-fff8' => ["\xFF\xF8" . \str_repeat("\0", 18), 'mp2', 'audio/mpeg'],
            'mp4-audio' => ["\xFF\xF0" . \str_repeat("\0", 18), 'mp4', 'audio/mpeg'],
            'm4a-audio-marker' => ["\x00\x00\x00\x0BftypM4A", 'm4a', 'audio/mp4'],
            'opus' => ['OggS' . \str_repeat("\0", 24) . 'OpusHead', 'opus', 'audio/opus'],
            'ogv' => ['OggS' . \str_repeat("\0", 24) . "\x80theora", 'ogv', 'video/ogg'],
            'ogm' => ['OggS' . \str_repeat("\0", 24) . "\x01video\x00", 'ogm', 'video/ogg'],
            'oga' => ['OggS' . \str_repeat("\0", 24) . "\x7FFLAC", 'oga', 'audio/ogg'],
            'spx' => ['OggS' . \str_repeat("\0", 24) . 'Speex  ', 'spx', 'audio/ogg'],
            'ogg' => ['OggS' . \str_repeat("\0", 24) . "\x01vorbis", 'ogg', 'audio/ogg'],
            'flac' => ['fLaC' . \str_repeat("\0", 4), 'flac', 'audio/x-flac'],
            'ape' => ['MAC ' . \str_repeat("\0", 4), 'ape', 'audio/ape'],
            'wavpack' => ['wvpk' . \str_repeat("\0", 4), 'wv', 'audio/wavpack'],
            'amr' => ["#!AMR\n" . \str_repeat("\0", 4), 'amr', 'audio/amr'],
            'aif' => ['FORM' . "\x00" . \str_repeat("\0", 4), 'aif', 'audio/aiff'],
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

    private static function createIsoBrandSample(string $brand): string
    {
        $brand = \substr($brand, 0, 4);
        $brand = \str_pad($brand, 4, ' ');

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
