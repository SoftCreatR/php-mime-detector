<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeTypeDetector;
use SoftCreatR\MimeDetector\MimeTypeRepository;
use ZipArchive;

final class KnownFixtureDetectionTest extends TestCase
{
    #[DataProvider('provideNewFormatFixtures')]
    public function testDistinguishesNewFormats(string $filename, string $extension, string $mimeType): void
    {
        $file = __DIR__ . '/fixtures/' . $filename;

        if (!\is_file($file)) {
            $this->markTestSkipped('The fixture submodule is unavailable or outdated.');
        }

        if (\in_array($extension, ['pages', 'numbers', 'key'], true) && !\class_exists(ZipArchive::class)) {
            $this->markTestSkipped('iWork inspection requires ZipArchive.');
        }

        $detector = new MimeDetector($file);

        $this->assertSame($extension, $detector->getFileExtension());
        $this->assertSame($mimeType, $detector->getMimeType());
        $this->assertContains($mimeType, $detector->getMimeTypesForExtension($extension));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideNewFormatFixtures(): array
    {
        return [
            'AAC MPEG-2' => ['fixture-adts-mpeg2.aac', 'aac', 'audio/aac'],
            'AAC MPEG-4' => ['fixture-adts-mpeg4.aac', 'aac', 'audio/aac'],
            'AAC MPEG-4 alternate' => ['fixture-adts-mpeg4-2.aac', 'aac', 'audio/aac'],
            'AAC after ID3 tag' => ['fixture-id3v2.aac', 'aac', 'audio/aac'],
            'FLAC after ID3 tag' => ['fixture-id3v2.flac', 'flac', 'audio/x-flac'],
            'MPEG layer I' => ['fixture.mp1', 'mp1', 'audio/mpeg'],
            'UTF-16 XML' => ['fixture-utf16-le-bom.xml', 'xml', 'application/xml'],
            'UTF-16 registry export' => ['fixture-win2000.reg', 'reg', 'application/x-ms-regedit'],
            'SketchUp' => ['fixture.skp', 'skp', 'application/vnd.sketchup.skp'],
            'zlib' => ['fixture-minimal.zlib', 'zlib', 'application/zlib'],
            'Apple UDIF' => ['fixture.dmg', 'dmg', 'application/x-apple-diskimage'],
            'QOI' => ['fixture-minimal.qoi', 'qoi', 'image/x-qoi'],
            'DDS' => ['fixture-minimal.dds', 'dds', 'image/vnd-ms.dds'],
            'OpenEXR' => ['fixture-minimal.exr', 'exr', 'image/x-exr'],
            'DjVu' => ['fixture-minimal.djvu', 'djvu', 'image/vnd.djvu'],
            'ASCII bitmap' => ['fixture-ascii.pbm', 'pbm', 'image/x-portable-bitmap'],
            'binary bitmap' => ['fixture-binary.pbm', 'pbm', 'image/x-portable-bitmap'],
            'ASCII graymap' => ['fixture-ascii.pgm', 'pgm', 'image/x-portable-graymap'],
            'binary graymap' => ['fixture-binary.pgm', 'pgm', 'image/x-portable-graymap'],
            'ASCII pixmap' => ['fixture-ascii.ppm', 'ppm', 'image/x-portable-pixmap'],
            'binary pixmap' => ['fixture-binary.ppm', 'ppm', 'image/x-portable-pixmap'],
            'little-endian PCAPNG' => ['fixture-little-endian.pcapng', 'pcapng', 'application/x-pcapng'],
            'big-endian PCAPNG' => ['fixture-big-endian.pcapng', 'pcapng', 'application/x-pcapng'],
            'little-endian nanosecond PCAP' => [
                'fixture-nanosecond-little.pcap', 'pcap', 'application/vnd.tcpdump.pcap',
            ],
            'big-endian nanosecond PCAP' => [
                'fixture-nanosecond-big.pcap', 'pcap', 'application/vnd.tcpdump.pcap',
            ],
            'lzop' => ['fixture-minimal.lzo', 'lzo', 'application/x-lzop'],
            'JPEG-LS' => ['fixture-normal.jls', 'jls', 'image/jls'],
            'JPEG-LS HP1' => ['fixture-hp1.jls', 'jls', 'image/jls'],
            'JPEG-LS HP2' => ['fixture-hp2.jls', 'jls', 'image/jls'],
            'JPEG-LS HP3' => ['fixture-hp3.jls', 'jls', 'image/jls'],
            'PE executable' => ['fixture.exe', 'exe', 'application/vnd.microsoft.portable-executable'],
            '32-bit Mach-O' => ['fixture-i386.macho', 'macho', 'application/x-mach-binary'],
            'PowerPC Mach-O' => ['fixture-ppc7400.macho', 'macho', 'application/x-mach-binary'],
            'universal Mach-O' => ['fixture-fat-binary.macho', 'macho', 'application/x-mach-binary'],
            'universal Mach-O with 64-bit offsets' => [
                'fixture-fat64.macho', 'macho', 'application/x-mach-binary',
            ],
            'Java class' => ['fixture.class', 'class', 'application/java-vm'],
            'animated PNG' => ['fixture.apng', 'apng', 'image/apng'],
            'ordinary PNG' => ['fixture.png', 'png', 'image/png'],
            'Sony RAW' => ['fixture-sony-zv-e10.arw', 'arw', 'image/x-sony-arw'],
            'Adobe DNG' => ['fixture-Leica-M10.dng', 'dng', 'image/x-adobe-dng'],
            'Nikon RAW' => ['fixture-nikon-raw.nef', 'nef', 'image/x-nikon-nef'],
            'ordinary TIFF' => ['fixture-bali.tif', 'tif', 'image/tiff'],
            'ISO 9660' => ['fixture-minimal.iso', 'iso', 'application/x-iso9660-image'],
            'Pages' => ['fixture-minimal.pages', 'pages', 'application/vnd.apple.pages'],
            'Pages with tables' => ['fixture-pages-with-tables.pages', 'pages', 'application/vnd.apple.pages'],
            'Numbers' => ['fixture-minimal.numbers', 'numbers', 'application/vnd.apple.numbers'],
            'Keynote' => ['fixture-minimal.key', 'key', 'application/vnd.apple.keynote'],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess) This uses the public repository factory.
     */
    public function testAllFixtureResultsAreInTheDefaultCatalogue(): void
    {
        $directory = __DIR__ . '/fixtures';

        if (!\is_file($directory . '/fixture.png')) {
            $this->markTestSkipped('The fixture submodule is unavailable.');
        }

        $repository = MimeTypeRepository::createDefault();
        $files = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $match = (new MimeTypeDetector($file->getPathname()))->detectFile();

            if ($match === null) {
                continue;
            }

            $this->assertContains(
                $match->mimeType(),
                $repository->getMimeTypesForExtension($match->extension()),
                $file->getFilename() . ' is missing from the default MIME catalogue.',
            );
        }
    }

    public function testOggOpusHasTheRecommendedContainerMediaType(): void
    {
        $file = __DIR__ . '/fixtures/fixture.opus';

        if (!\is_file($file)) {
            $this->markTestSkipped('The fixture submodule is unavailable.');
        }

        $detector = new MimeDetector($file);

        $this->assertSame('audio/opus', $detector->getMimeType());
        $this->assertSame('audio/ogg', $detector->getPreferredMimeType());
        $this->assertContains('audio/ogg', $detector->getMimeTypesForExtension('opus'));
    }

    public function testCompoundFileIsNotAssumedToBeAnInstallerOrSpreadsheet(): void
    {
        $file = __DIR__ . '/fixtures/fixture.doc.cfb';

        if (!\is_file($file)) {
            $this->markTestSkipped('The fixture submodule is unavailable.');
        }

        $detector = new MimeDetector($file);

        $this->assertSame('cfb', $detector->getFileExtension());
        $this->assertSame('application/x-cfb', $detector->getMimeType());
    }

    public function testAsfStreamTypeDeterminesAudioOrVideo(): void
    {
        $directory = __DIR__ . '/fixtures/';

        if (!\is_file($directory . 'fixture.wma.asf')) {
            $this->markTestSkipped('The fixture submodule is unavailable.');
        }

        $this->assertSame('audio/x-ms-wma', (new MimeDetector($directory . 'fixture.wma.asf'))->getMimeType());
        $this->assertSame('video/x-ms-wmv', (new MimeDetector($directory . 'fixture.wmv.asf'))->getMimeType());
    }
}
