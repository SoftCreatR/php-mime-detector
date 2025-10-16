<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
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
            'elf' => ["\x7FELF", 'elf', 'application/x-elf'],
            'macho' => ["\xCF\xFA\xED\xFE", 'macho', 'application/x-mach-binary'],
            'macho-be' => ["\xFE\xED\xFA\xCF", 'macho', 'application/x-mach-binary'],
            'class' => ["\xCA\xFE\xBA\xBE", 'class', 'application/java-vm'],
            'swf' => ['CWS', 'swf', 'application/x-shockwave-flash'],
            'wasm' => ["\x00asm", 'wasm', 'application/wasm'],
            'luac' => ["\x1BLua", 'luac', 'application/x-lua-bytecode'],
            'nes' => ['NES' . "\x1A", 'nes', 'application/x-nintendo-nes-rom'],
            'crx' => ['Cr24', 'crx', 'application/x-google-chrome-extension'],
        ];
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
