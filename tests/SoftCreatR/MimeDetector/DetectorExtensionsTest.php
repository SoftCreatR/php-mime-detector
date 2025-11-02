<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\MimeTypeDetector;

/**
 * @covers \SoftCreatR\MimeDetector\Support\DetectorExtensions
 * @covers \SoftCreatR\MimeDetector\MimeTypeDetector::defaultDetectors
 */
final class DetectorExtensionsTest extends TestCase
{
    protected function setUp(): void
    {
        MimeTypeDetector::flushExtensions();
    }

    protected function tearDown(): void
    {
        MimeTypeDetector::flushExtensions();
    }

    public function testCustomDetectorCanBeRegisteredViaExtension(): void
    {
        $path = $this->createTemporaryFile('extension');

        MimeTypeDetector::extend('custom', static function (): FileSignatureDetectorInterface {
            return new class implements FileSignatureDetectorInterface {
                public function detect(DetectionContext $context): ?MimeTypeMatch
                {
                    $buffer = $context->buffer();

                    if ($buffer->checkForBytes([0x65, 0x78, 0x74, 0x65, 0x6E])) { // "exten"
                        return new MimeTypeMatch('ext', 'application/x-extension');
                    }

                    return null;
                }
            };
        }, priority: 50);

        $detector = new MimeTypeDetector($path);

        self::assertSame('application/x-extension', $detector->getMimeType());
        self::assertSame('ext', $detector->getFileExtension());
    }

    public function testDetectorInstancesCanBeRegisteredDirectly(): void
    {
        $path = $this->createTemporaryFile('instance');

        MimeTypeDetector::extend(
            'instance',
            new RecordingDetector('instance', new MimeTypeMatch('inst', 'application/x-instance')),
            priority: 40,
        );

        $detector = new MimeTypeDetector($path);

        self::assertSame('application/x-instance', $detector->getMimeType());
        self::assertSame('inst', $detector->getFileExtension());
    }

    public function testExtensionFactoriesCanReturnIterables(): void
    {
        RecordingDetector::$calls = [];

        MimeTypeDetector::extend('iterable', static function (): iterable {
            return [
                new RecordingDetector('first'),
                new RecordingDetector('second', new MimeTypeMatch('second', 'application/x-second')),
            ];
        }, priority: 30);

        $detector = new MimeTypeDetector($this->createTemporaryFile('iterable'));

        self::assertSame('application/x-second', $detector->getMimeType());
        self::assertSame(['first', 'second'], RecordingDetector::$calls);
    }

    public function testDetectorPrioritiesControlExecutionOrder(): void
    {
        RecordingDetector::$calls = [];

        MimeTypeDetector::extend('fallback', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('fallback', new MimeTypeMatch('fallback', 'application/x-fallback'));
        }, priority: -10);

        MimeTypeDetector::extend('preflight', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('preflight');
        }, priority: 10);

        $detector = new MimeTypeDetector($this->createTemporaryFile('priority'));

        $detector->getMimeType();

        self::assertSame(['preflight', 'fallback'], RecordingDetector::$calls);
    }

    public function testDetectorsWithEqualPriorityRespectRegistrationOrder(): void
    {
        RecordingDetector::$calls = [];

        MimeTypeDetector::extend('first', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('first');
        });

        MimeTypeDetector::extend('second', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('second', new MimeTypeMatch('second', 'application/x-second'));
        });

        $detector = new MimeTypeDetector($this->createTemporaryFile('order'));

        self::assertSame('application/x-second', $detector->getMimeType());
        self::assertSame(['first', 'second'], RecordingDetector::$calls);
    }

    public function testExtensionsCanBeForgotten(): void
    {
        MimeTypeDetector::extend('transient', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('transient');
        });

        self::assertTrue(MimeTypeDetector::hasExtension('transient'));

        MimeTypeDetector::forgetExtension('transient');

        self::assertFalse(MimeTypeDetector::hasExtension('transient'));
    }

    public function testExtensionsCanBeFlushed(): void
    {
        MimeTypeDetector::extend('flushable', static function (): FileSignatureDetectorInterface {
            return new RecordingDetector('flushable');
        });

        self::assertTrue(MimeTypeDetector::hasExtension('flushable'));

        MimeTypeDetector::flushExtensions();

        self::assertFalse(MimeTypeDetector::hasExtension('flushable'));
    }

    public function testExtensionFactoriesMustReturnDetectors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Detector extension "%s" must return an instance of %s or an iterable of detectors.',
            'invalid',
            FileSignatureDetectorInterface::class,
        ));

        MimeTypeDetector::extend('invalid', static fn() => 'not-a-detector');

        new MimeTypeDetector($this->createTemporaryFile('invalid'));
    }

    public function testExtensionFactoriesMustOnlyYieldDetectors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Detector extension "%s" must only yield instances of %s.',
            'invalid-iterable',
            FileSignatureDetectorInterface::class,
        ));

        MimeTypeDetector::extend('invalid-iterable', static function (): iterable {
            return [
                new RecordingDetector('valid'),
                new class {
                    // ...
                },
            ];
        });

        new MimeTypeDetector($this->createTemporaryFile('invalid-iterable'));
    }

    private function createTemporaryFile(string $contents): string
    {
        $path = \tempnam(\sys_get_temp_dir(), 'mime-detector-');

        \file_put_contents($path, $contents);

        return $path;
    }
}
