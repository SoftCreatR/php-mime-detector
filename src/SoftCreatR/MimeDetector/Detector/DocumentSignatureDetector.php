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
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Detects office document and ebook formats.
 */
#[DetectorCategory('document')]
final class DocumentSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($buffer->checkForBytes([0x25, 0x50, 0x44, 0x46])) {
            return $this->match('pdf', 'application/pdf');
        }

        if ($buffer->checkForBytes([0x7B, 0x5C, 0x72, 0x74, 0x66])) {
            return $this->match('rtf', 'application/rtf');
        }

        if ($buffer->checkForBytes([0x42, 0x4F, 0x4F, 0x4B, 0x4D, 0x4F, 0x42, 0x49], 60)) {
            return $this->match('mobi', 'application/x-mobipocket-ebook');
        }

        if (
            $buffer->checkForBytes([0x25, 0x21])
            && $buffer->checkString('PS-Adobe-', 2)
            && $buffer->checkString(' EPSF-', 14)
        ) {
            return $this->match('eps', 'application/eps');
        }

        if ($buffer->checkForBytes([0xC5, 0xD0, 0xD3, 0xC6])) {
            return $this->match('eps', 'application/eps');
        }

        if ($buffer->checkForBytes([0x25, 0x21])) {
            return $this->match('ps', 'application/postscript');
        }

        if ($buffer->checkForBytes([0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1])) {
            if (
                $buffer->checkForBytes([
                    0x56, 0x00, 0x69, 0x00, 0x73,
                    0x00, 0x69, 0x00, 0x6F, 0x00,
                    0x44, 0x00, 0x6F, 0x00, 0x63,
                ], 1664)
            ) {
                return $this->match('vsd', 'application/vnd.visio');
            }

            if (
                $buffer->checkForBytes([0x09, 0x08, 0x10, 0x00, 0x00, 0x06, 0x05, 0x00], 2048)
                || $buffer->checkForBytes([0xFD, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF], 512)
            ) {
                return $this->match('xls', 'application/vnd.ms-excel');
            }

            return $this->match('msi', 'application/x-msi');
        }

        if ($buffer->checkString('ITSF')) {
            return $this->match('chm', 'application/vnd.ms-htmlhelp');
        }

        if ($buffer->checkForBytes([0x21, 0x42, 0x44, 0x4E])) {
            return $this->match('pst', 'application/vnd.ms-outlook');
        }

        if (
            $buffer->checkForBytes([
                0x06, 0x06, 0xED, 0xF5, 0xD8, 0x1D, 0x46, 0xE5,
                0xBD, 0x31, 0xEF, 0xE7, 0xFE, 0x74, 0xB7, 0x1D,
            ])
        ) {
            return $this->match('indd', 'application/x-indesign');
        }

        return null;
    }
}
