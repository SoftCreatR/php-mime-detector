<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Detects font formats such as TTF, OTF and WOFF.
 */
#[DetectorCategory('font')]
final class FontSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if (
            $buffer->checkForBytes([0x77, 0x4F, 0x46])
            && (
                $buffer->checkForBytes([0x00, 0x01, 0x00, 0x00], 4)
                || $buffer->checkForBytes([0x4F, 0x54, 0x54, 0x4F], 4)
            )
        ) {
            $marker = $buffer->get(3);

            if ($marker === 0x46) {
                return $this->match('woff', 'font/woff');
            }

            if ($marker === 0x32) {
                return $this->match('woff2', 'font/woff2');
            }
        }

        if (
            $buffer->checkForBytes([0x4C, 0x50], 34)
            && (
                $buffer->checkForBytes([0x00, 0x00, 0x01], 8)
                || $buffer->checkForBytes([0x01, 0x00, 0x02], 8)
                || $buffer->checkForBytes([0x02, 0x00, 0x02], 8)
            )
        ) {
            return $this->match('eot', 'application/vnd.ms-fontobject');
        }

        if ($buffer->checkString('ttcf')) {
            return $this->match('ttc', 'font/collection');
        }

        if ($buffer->checkForBytes([0x00, 0x01, 0x00, 0x00, 0x00])) {
            return $this->match('ttf', 'font/ttf');
        }

        if ($buffer->checkForBytes([0x4F, 0x54, 0x54, 0x4F, 0x00])) {
            return $this->match('otf', 'font/otf');
        }

        if ($buffer->checkForBytes([0x00, 0x00, 0x01, 0x00])) {
            return $this->match('ico', 'image/x-icon');
        }

        if ($buffer->checkForBytes([0x00, 0x00, 0x02, 0x00])) {
            return $this->match('cur', 'image/x-icon');
        }

        return null;
    }
}
