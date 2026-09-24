<?php

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeTypeAliases;

final class MimeTypeAliasesTest extends TestCase
{
    public function testKnownNamesCompareAsTheSameFormat(): void
    {
        $pairs = [
            ['audio/vnd.wave', 'audio/x-wav'],
            ['audio/flac', 'audio/x-flac'],
            ['audio/aiff', 'audio/x-aiff'],
            ['audio/mp4', 'audio/x-m4a'],
            ['video/vnd.avi', 'video/x-msvideo'],
            ['application/vnd.rar', 'application/x-rar'],
            ['application/vnd.rar', 'application/x-rar-compressed'],
            ['application/vnd.ms-asf', 'video/x-ms-asf'],
            ['application/rtf', 'text/rtf'],
            ['application/xml', 'text/xml'],
            ['image/vnd.microsoft.icon', 'image/x-icon'],
            ['application/vnd.debian.binary-package', 'application/x-deb'],
        ];

        foreach ($pairs as [$preferred, $alias]) {
            $this->assertSame($preferred, MimeTypeAliases::preferred($alias));
            $this->assertTrue(MimeTypeAliases::equivalent($preferred, $alias));
            $this->assertTrue(MimeTypeAliases::equivalent($alias, $preferred));
        }

        $this->assertTrue(MimeTypeAliases::equivalent(' TEXT/XML; charset=UTF-8 ', 'application/xml'));
    }

    public function testDifferentFormatsRemainDifferent(): void
    {
        $this->assertFalse(MimeTypeAliases::equivalent('audio/opus', 'audio/ogg'));
        $this->assertFalse(MimeTypeAliases::equivalent('audio/ogg; codecs=opus', 'audio/ogg; codecs=vorbis'));
        $this->assertFalse(MimeTypeAliases::equivalent('audio/ogg; codecs=opus', 'audio/ogg'));
        $this->assertFalse(MimeTypeAliases::equivalent('font/ttf', 'font/sfnt'));
        $this->assertFalse(MimeTypeAliases::equivalent('image/flif', 'text/html'));
        $this->assertFalse(MimeTypeAliases::equivalent('', ''));
    }
}
