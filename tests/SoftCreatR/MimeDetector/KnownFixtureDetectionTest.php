<?php

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

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
            'animated PNG' => ['fixture.apng', 'apng', 'image/apng'],
            'ordinary PNG' => ['fixture.png', 'png', 'image/png'],
            'Sony RAW' => ['fixture-sony-zv-e10.arw', 'arw', 'image/x-sony-arw'],
            'Adobe DNG' => ['fixture-Leica-M10.dng', 'dng', 'image/x-adobe-dng'],
            'Nikon RAW' => ['fixture-nikon-raw.nef', 'nef', 'image/x-nikon-nef'],
            'ordinary TIFF' => ['fixture-bali.tif', 'tif', 'image/tiff'],
            'ISO 9660' => ['fixture-minimal.iso', 'iso', 'application/x-iso9660-image'],
            'Pages' => ['fixture-minimal.pages', 'pages', 'application/vnd.apple.pages'],
            'Numbers' => ['fixture-minimal.numbers', 'numbers', 'application/vnd.apple.numbers'],
            'Keynote' => ['fixture-minimal.key', 'key', 'application/vnd.apple.keynote'],
        ];
    }

    public function testAllFixtureResultsAreInTheDefaultCatalogue(): void
    {
        $directory = __DIR__ . '/fixtures';

        if (!\is_file($directory . '/fixture.png')) {
            $this->markTestSkipped('The fixture submodule is unavailable.');
        }

        $repository = MimeTypeRepository::createDefault();
        $files = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

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
                $file->getFilename() . ' is missing from the default MIME catalogue.'
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
