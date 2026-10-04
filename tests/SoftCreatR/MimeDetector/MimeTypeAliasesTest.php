<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\MimeTypeAliases;
use SoftCreatR\MimeDetector\MimeTypeRepository;

/**
 * @SuppressWarnings(PHPMD.StaticAccess) The public alias API is static by design.
 */
final class MimeTypeAliasesTest extends TestCase
{
    #[DataProvider('provideAliasGroups')]
    public function testAuditedAliasesAreSymmetricAndResolveInEitherRegistration(
        string $preferred,
        array $aliases,
    ): void {
        $names = [$preferred, ...$aliases];

        foreach ($names as $name) {
            $this->assertSame($preferred, MimeTypeAliases::preferred($name));
            $this->assertSame($preferred, MimeTypeAliases::preferred(MimeTypeAliases::preferred($name)));
            $repository = new MimeTypeRepository(['sample' => [$name]]);

            foreach ($names as $other) {
                $this->assertTrue(MimeTypeAliases::equivalent($name, $other), "$name versus $other");
                $this->assertSame(['sample'], $repository->getExtensionsForMimeType($other));
            }
        }
    }

    #[DataProvider('provideExcludedComparisons')]
    public function testGenericAndSpecificTypesRemainDistinct(string $first, string $second, string $reason): void
    {
        $this->assertFalse(MimeTypeAliases::equivalent($first, $second), $reason);
        $this->assertFalse(MimeTypeAliases::equivalent($second, $first), $reason);
    }

    public function testEveryCatalogueTypeHasBeenIncludedInTheAudit(): void
    {
        $catalogue = \array_keys(MimeTypeRepository::createDefault()->all());
        \sort($catalogue);

        $this->assertSame(self::audit()['reviewedCatalogue'], $catalogue);

        $repository = MimeTypeRepository::createDefault();

        foreach (self::audit()['groups'] as $preferred => $aliases) {
            foreach ([$preferred, ...$aliases] as $name) {
                $this->assertSame(
                    $repository->getExtensionsForMimeType($preferred),
                    $repository->getExtensionsForMimeType($name),
                    $name,
                );
            }
        }
    }

    #[DataProvider('provideParameterComparisons')]
    public function testParameterComparison(string $first, string $second, bool $expected): void
    {
        $this->assertSame($expected, MimeTypeAliases::equivalent($first, $second));
        $this->assertSame($expected, MimeTypeAliases::equivalent($second, $first));
    }

    public static function provideParameterComparisons(): array
    {
        return [
            'charset' => [' TEXT/XML; charset=UTF-8 ', 'application/xml', true],
            'charset whitespace' => ['text/xml; CHARSET = "UTF-8"', 'application/xml', true],
            'codec quote and whitespace' => ['audio/ogg; CODECS = "opus"', 'audio/ogg; codecs=opus', true],
            'codec mismatch' => ['audio/ogg; codecs=opus', 'audio/ogg; codecs=vorbis', false],
            'missing codec' => ['audio/ogg; codecs=opus', 'audio/ogg', false],
            'codec value case' => ['audio/ogg; codecs=Opus', 'audio/ogg; codecs=opus', false],
            'parameter order' => ['video/mp4; codecs=avc1; profile=main', 'video/mp4; profile=main; codecs=avc1', true],
            'quoted separator' => ['application/pdf; extra="a;b"', 'application/pdf; extra="a;c"', false],
            'quoted parameter text' => [
                'audio/ogg; extra="value;codecs=opus"', 'audio/ogg; extra=value; codecs=opus', false,
            ],
            'quoted escape' => ['application/pdf; extra="a\\b"', 'application/pdf; extra=ab', true],
            'unknown normalized name' => ['APPLICATION/X-PRIVATE', 'application/x-private', true],
            'unknown alias' => ['application/x-private', 'application/private', false],
            'unrelated' => ['image/flif', 'text/html', false],
            'empty' => ['', '', false],
        ];
    }

    public static function provideAliasGroups(): iterable
    {
        foreach (self::audit()['groups'] as $preferred => $aliases) {
            yield $preferred => [$preferred, $aliases];
        }
    }

    public static function provideExcludedComparisons(): iterable
    {
        foreach (self::audit()['excluded'] as $comparison) {
            yield $comparison['first'] . ' vs ' . $comparison['second'] => [
                $comparison['first'], $comparison['second'], $comparison['reason'],
            ];
        }
    }

    private static function audit(): array
    {
        $data = \file_get_contents(__DIR__ . '/data/mime-alias-audit.json');

        return \json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}
