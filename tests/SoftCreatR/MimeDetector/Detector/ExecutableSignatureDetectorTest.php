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
use SoftCreatR\MimeDetector\Detector\ExecutableSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\ExecutableSignatureDetector
 */
final class ExecutableSignatureDetectorTest extends TestCase
{
    #[DataProvider('provideExecutables')]
    public function testDetectsExecutables(string $data, string $extension, string $mimeType): void
    {
        $detector = new ExecutableSignatureDetector();

        $match = $this->detect($detector, $data);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame($extension, $match->extension());
        $this->assertSame($mimeType, $match->mimeType());
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public static function provideExecutables(): iterable
    {
        return [
            'exe' => ["MZ", 'exe', 'application/x-msdownload'],
            'PE32' => [
                self::portableExecutable(64, "\x0B\x01"),
                'exe', 'application/vnd.microsoft.portable-executable',
            ],
            'PE32+ beyond cache' => [
                self::portableExecutable(8192, "\x0B\x02"), 'exe', 'application/vnd.microsoft.portable-executable',
            ],
            'NE is not PE' => [
                \substr_replace(self::portableExecutable(64, "\x0B\x01"), "NE\0\0", 64, 4),
                'exe', 'application/x-msdownload',
            ],
            'out-of-file offset' => [
                "MZ" . \str_repeat("\0", 58) . \pack('V', 0xFFFFFFFF),
                'exe', 'application/x-msdownload',
            ],
            'offset inside DOS header' => [
                "MZ" . \str_repeat("\0", 58) . \pack('V', 4),
                'exe', 'application/x-msdownload',
            ],
            'truncated PE' => [
                \substr(self::portableExecutable(64, "\x0B\x01"), 0, 89),
                'exe', 'application/x-msdownload',
            ],
            'invalid optional-header magic' => [
                self::portableExecutable(64, "\0\0"),
                'exe', 'application/x-msdownload',
            ],
            'elf' => ["\x7FELF", 'elf', 'application/x-elf'],
            'macho' => ["\xCF\xFA\xED\xFE", 'macho', 'application/x-mach-binary'],
            'macho-be' => ["\xFE\xED\xFA\xCF", 'macho', 'application/x-mach-binary'],
            'macho-32' => ["\xCE\xFA\xED\xFE", 'macho', 'application/x-mach-binary'],
            'macho-32-be' => ["\xFE\xED\xFA\xCE", 'macho', 'application/x-mach-binary'],
            'fat-macho' => [
                "\xCA\xFE\xBA\xBE" . \pack('N6', 1, 7, 3, 28, 28, 0) . "\xCE\xFA\xED\xFE",
                'macho', 'application/x-mach-binary',
            ],
            'fat-macho-swapped' => [
                "\xBE\xBA\xFE\xCA" . \pack('V6', 1, 7, 3, 28, 28, 0) . "\xCE\xFA\xED\xFE",
                'macho', 'application/x-mach-binary',
            ],
            'fat64-macho' => [
                "\xCA\xFE\xBA\xBF" . \pack('N3J2N2', 1, 7, 3, 40, 28, 0, 0),
                'macho', 'application/x-mach-binary',
            ],
            'fat64-macho-swapped' => [
                "\xBF\xBA\xFE\xCA" . \pack('V3P2V2', 1, 7, 3, 40, 28, 0, 0),
                'macho', 'application/x-mach-binary',
            ],
            'class' => ["\xCA\xFE\xBA\xBE" . \pack('n3', 0, 52, 1), 'class', 'application/java-vm'],
            'preview class' => ["\xCA\xFE\xBA\xBE" . \pack('n3', 65535, 65, 1), 'class', 'application/java-vm'],
            'swf' => ['CWS', 'swf', 'application/x-shockwave-flash'],
            'wasm' => ["\x00asm", 'wasm', 'application/wasm'],
            'luac' => ["\x1BLua", 'luac', 'application/x-lua-bytecode'],
            'nes' => ['NES' . "\x1A", 'nes', 'application/x-nintendo-nes-rom'],
            'crx' => ['Cr24', 'crx', 'application/x-google-chrome-extension'],
        ];
    }

    public function testAmbiguousOrIncompleteFatHeadersAreNotAssumedToBeJava(): void
    {
        foreach (
            [
                "\xCA\xFE\xBA\xBE",
                "\xCA\xFE\xBA\xBE" . \pack('N', 1),
                "\xCA\xFE\xBA\xBE" . \pack('N6', 1, 0, 3, 28, 28, 0),
                "\xCA\xFE\xBA\xBE" . \pack('N6', 1, 7, 3, 4, 28, 0),
                "\xCA\xFE\xBA\xBE" . \pack('n3', 0, 52, 0),
                "\xCA\xFE\xBA\xBF" . \pack('N3J2N2', 1, 7, 3, 40, 28, 0, 1),
                "\xCA\xFE\xBA\xBF" . \pack('N3J2N2', 1, 7, 3, 40, 28, 64, 0),
            ] as $data
        ) {
            $this->assertNull($this->detect(new ExecutableSignatureDetector(), $data));
        }
    }

    private static function portableExecutable(int $offset, string $optionalMagic): string
    {
        $dos = 'MZ' . \str_repeat("\0", 58) . \pack('V', $offset);

        return $dos . \str_repeat("\0", $offset - 64)
            . "PE\0\0" . \pack('vvVVVvv', 0x14C, 1, 0, 0, 0, 224, 0x102) . $optionalMagic;
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(ExecutableSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-exec-');
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
