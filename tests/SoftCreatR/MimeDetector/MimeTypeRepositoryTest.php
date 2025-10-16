<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeTypeRepository;

class MimeTypeRepositoryTest extends TestCase
{
    public function testRegistersMappingsAndPreventsDuplicates(): void
    {
        $repository = new MimeTypeRepository([
            'txt' => ['text/plain'],
        ]);

        $repository->register('TXT', 'text/plain', 'text/custom');
        $repository->register('bin', 'application/octet-stream');
        $repository->register('txt', 'text/plain');

        $this->assertSame(['text/custom', 'text/plain'], $repository->getMimeTypesForExtension('txt'));
        $this->assertSame(['application/octet-stream'], $repository->getMimeTypesForExtension('BIN'));
        $this->assertSame('txt', $repository->getExtensionForMimeType('text/plain'));
        $this->assertSame(['bin'], $repository->getExtensionsForMimeType('APPLICATION/OCTET-STREAM'));
    }

    public function testListsAllMappings(): void
    {
        $repository = new MimeTypeRepository();
        $repository->register('png', 'image/png');
        $repository->register('jpg', 'image/jpeg');

        $this->assertSame([
            'image/png' => ['png'],
            'image/jpeg' => ['jpg'],
        ], $repository->all());
    }

    public function testCreatesRepositoryWithDefaultMap(): void
    {
        $repository = MimeTypeRepository::createDefault();

        $this->assertNotEmpty($repository->getMimeTypesForExtension('mp4'));
        $this->assertContains('mp4', $repository->getExtensionsForMimeType('video/mp4'));
    }
}
