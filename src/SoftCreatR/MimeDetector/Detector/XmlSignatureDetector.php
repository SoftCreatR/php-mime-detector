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

        if ($buffer->checkForBytes([0xFF, 0xFE]) && $this->checkUtf16LeSequence($buffer, '<?xml ', 2)) {
            return $this->match('xml', 'application/xml');
        }

        if ($buffer->checkForBytes([0xFE, 0xFF]) && $this->checkUtf16BeSequence($buffer, '<?xml ', 2)) {
            return $this->match('xml', 'application/xml');
        }

        if (
            $buffer->checkString('<!doctype html')
            || $buffer->checkString('<!DOCTYPE html')
            || $buffer->checkString('<html')
        ) {
            return $this->match('html', 'text/html');
        }

        return null;
    }

    private function detectAsciiXml(FileBuffer $buffer, int $offset): ?MimeTypeMatch
    {
        if (!$buffer->checkString('<?xml ', $offset)) {
            return null;
        }

        $searchOffset = $offset + 6;

        if (
            $buffer->searchForBytes($buffer->toBytes('<!doctype svg'), $searchOffset) !== -1
            || $buffer->searchForBytes($buffer->toBytes('<!DOCTYPE svg'), $searchOffset) !== -1
            || $buffer->searchForBytes($buffer->toBytes('<svg'), $searchOffset) !== -1
        ) {
            return $this->match('svg', 'image/svg+xml');
        }

        if (
            $buffer->searchForBytes($buffer->toBytes('<!doctype html'), $searchOffset) !== -1
            || $buffer->searchForBytes($buffer->toBytes('<!DOCTYPE html'), $searchOffset) !== -1
            || $buffer->searchForBytes($buffer->toBytes('<html'), $searchOffset) !== -1
        ) {
            return $this->match('html', 'text/html');
        }

        if ($buffer->searchForBytes($buffer->toBytes('<rdf:RDF'), $searchOffset) !== -1) {
            return $this->match('rdf', 'application/rdf+xml');
        }

        if ($buffer->searchForBytes($buffer->toBytes('<rss version="2.0"'), $searchOffset) !== -1) {
            return $this->match('rss', 'application/rss+xml');
        }

        return $this->match('xml', 'application/xml');
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
