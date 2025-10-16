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
use SoftCreatR\MimeDetector\Detector\XmlSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

/**
 * @covers \SoftCreatR\MimeDetector\Detector\XmlSignatureDetector
 */
final class XmlSignatureDetectorTest extends TestCase
{
    #[DataProvider('provideXmlSamples')]
    public function testDetectsXmlVariants(string $payload, string $expectedExtension, string $expectedMime): void
    {
        $detector = new XmlSignatureDetector();

        $match = $this->detect($detector, $payload);

        $this->assertInstanceOf(MimeTypeMatch::class, $match);
        $this->assertSame($expectedExtension, $match->extension());
        $this->assertSame($expectedMime, $match->mimeType());
    }

    public static function provideXmlSamples(): iterable
    {
        yield 'svg' => ['<?xml <svg></svg>', 'svg', 'image/svg+xml'];
        yield 'html xml' => ['<?xml <!DOCTYPE html><html></html>', 'html', 'text/html'];
        yield 'rdf' => ['<?xml <rdf:RDF></rdf:RDF>', 'rdf', 'application/rdf+xml'];
        yield 'rss' => ['<?xml <rss version="2.0"></rss>', 'rss', 'application/rss+xml'];
        yield 'generic xml' => ['<?xml <root/>', 'xml', 'application/xml'];
        yield 'utf8 bom xml' => ["\xEF\xBB\xBF<?xml <root/>", 'xml', 'application/xml'];
        yield 'utf16-le xml' => ["\xFF\xFE" . self::toUtf16Le('<?xml <root/>'), 'xml', 'application/xml'];
        yield 'utf16-be xml' => ["\xFE\xFF" . self::toUtf16Be('<?xml <root/>'), 'xml', 'application/xml'];
    }

    #[DataProvider('provideHtmlSamples')]
    public function testDetectsHtmlWithoutXmlHeader(string $payload): void
    {
        $detector = new XmlSignatureDetector();

        $match = $this->detect($detector, $payload);

        $this->assertSame('html', $match?->extension());
        $this->assertSame('text/html', $match?->mimeType());
    }

    public static function provideHtmlSamples(): iterable
    {
        yield '<!doctype>' => ['<!doctype html><html></html>'];
        yield '<html>' => ['<html><body></body></html>'];
    }

    public function testReturnsNullForNonXmlContent(): void
    {
        $detector = new XmlSignatureDetector();

        $match = $this->detect($detector, 'plain text document');

        $this->assertNull($match);
    }

    /**
     * @throws MimeDetectorException
     */
    private function detect(XmlSignatureDetector $detector, string $data): ?MimeTypeMatch
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-xml-');
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

    private static function toUtf16Le(string $value): string
    {
        $buffer = '';

        $length = \strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $buffer .= $value[$i] . "\x00";
        }

        return $buffer;
    }

    private static function toUtf16Be(string $value): string
    {
        $buffer = '';

        $length = \strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $buffer .= "\x00" . $value[$i];
        }

        return $buffer;
    }
}
