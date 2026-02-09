<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Detects XML family formats such as SVG and RSS.
 */
#[DetectorCategory('text')]
final class XmlSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();
        $match = $this->detectAsciiXml($buffer, 0);

        if ($match instanceof MimeTypeMatch) {
            return $match;
        }

        if ($buffer->checkForBytes([0xEF, 0xBB, 0xBF])) {
            $match = $this->detectAsciiXml($buffer, 3);

            if ($match instanceof MimeTypeMatch) {
                return $match;
            }
        }

        if ($buffer->checkForBytes([0xFF, 0xFE]) && $this->isUtf16LeXmlDeclaration($buffer, 2)) {
            return $this->match('xml', 'application/xml');
        }

        if ($buffer->checkForBytes([0xFE, 0xFF]) && $this->isUtf16BeXmlDeclaration($buffer, 2)) {
            return $this->match('xml', 'application/xml');
        }

        return null;
    }

    private function detectAsciiXml(FileBuffer $buffer, int $offset): ?MimeTypeMatch
    {
        $snippet = $buffer->sliceAsString($offset);

        if ($snippet === '') {
            return null;
        }

        $snippet = $this->ltrimAsciiWhitespace($snippet);

        if ($snippet === '') {
            return null;
        }

        $snippet = \strtolower($snippet);

        if (
            !$this->startsWithXmlDeclaration($snippet)
            && !\str_starts_with($snippet, '<?xpacket')
            && !$this->startsWithXmlTag($snippet)
        ) {
            return null;
        }

        return $this->detectXmlFamily($snippet);
    }

    private function detectXmlFamily(string $snippet): MimeTypeMatch
    {
        if (\str_contains($snippet, '<!doctype svg') || \str_contains($snippet, '<svg')) {
            return $this->match('svg', 'image/svg+xml');
        }

        if (\str_contains($snippet, '<!doctype html') || \str_contains($snippet, '<html')) {
            return $this->match('html', 'text/html');
        }

        if (\str_contains($snippet, '<x:xmpmeta') || \str_contains($snippet, '<rdf:rdf')) {
            return $this->match('rdf', 'application/rdf+xml');
        }

        if (\str_contains($snippet, '<rss version="2.0"')) {
            return $this->match('rss', 'application/rss+xml');
        }

        return $this->match('xml', 'application/xml');
    }

    private function startsWithXmlDeclaration(string $snippet): bool
    {
        if (!\str_starts_with($snippet, '<?xml')) {
            return false;
        }

        return $this->isAsciiWhitespaceByte($snippet, 5);
    }

    private function startsWithXmlTag(string $snippet): bool
    {
        if (!isset($snippet[0], $snippet[1]) || $snippet[0] !== '<') {
            return false;
        }

        $next = $snippet[1];

        if ($next === '?' || $next === '!') {
            return true;
        }

        $byte = \ord($next);

        return ($byte >= 0x61 && $byte <= 0x7A) || ($byte >= 0x41 && $byte <= 0x5A) || $next === '_' || $next === ':';
    }

    private function isAsciiWhitespaceByte(string $snippet, int $offset): bool
    {
        if (!isset($snippet[$offset])) {
            return false;
        }

        $byte = \ord($snippet[$offset]);

        return \in_array($byte, [0x09, 0x0A, 0x0D, 0x20], true);
    }

    private function ltrimAsciiWhitespace(string $snippet): string
    {
        return \ltrim($snippet, " \t\r\n");
    }

    private function isUtf16LeXmlDeclaration(FileBuffer $buffer, int $offset): bool
    {
        if (!$this->checkUtf16LeSequence($buffer, '<?xml', $offset)) {
            return false;
        }

        return $this->isUtf16LeWhitespace($buffer, $offset + 10);
    }

    private function isUtf16BeXmlDeclaration(FileBuffer $buffer, int $offset): bool
    {
        if (!$this->checkUtf16BeSequence($buffer, '<?xml', $offset)) {
            return false;
        }

        return $this->isUtf16BeWhitespace($buffer, $offset + 10);
    }

    private function isUtf16LeWhitespace(FileBuffer $buffer, int $offset): bool
    {
        $lowByte = $buffer->get($offset);
        $highByte = $buffer->get($offset + 1);

        return $lowByte !== null && $highByte === 0x00 && $this->isAsciiWhitespace($lowByte);
    }

    private function isUtf16BeWhitespace(FileBuffer $buffer, int $offset): bool
    {
        $highByte = $buffer->get($offset);
        $lowByte = $buffer->get($offset + 1);

        return $highByte === 0x00 && $lowByte !== null && $this->isAsciiWhitespace($lowByte);
    }

    private function isAsciiWhitespace(int $byte): bool
    {
        return \in_array($byte, [0x09, 0x0A, 0x0D, 0x20], true);
    }

    private function checkUtf16LeSequence(FileBuffer $buffer, string $value, int $offset): bool
    {
        $bytes = [];
        $length = \strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $bytes[] = \ord($value[$i]);
            $bytes[] = 0x00;
        }

        return $buffer->checkForBytes($bytes, $offset);
    }

    private function checkUtf16BeSequence(FileBuffer $buffer, string $value, int $offset): bool
    {
        $bytes = [];
        $length = \strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $bytes[] = 0x00;
            $bytes[] = \ord($value[$i]);
        }

        return $buffer->checkForBytes($bytes, $offset);
    }
}
