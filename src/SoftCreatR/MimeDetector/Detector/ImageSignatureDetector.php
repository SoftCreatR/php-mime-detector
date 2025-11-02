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
 * Detects image formats based on well-known byte signatures.
 */
#[DetectorCategory('image')]
final class ImageSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($buffer->checkForBytes([0xFF, 0xD8, 0xFF])) {
            return $this->match('jpg', 'image/jpeg');
        }

        if ($buffer->checkForBytes([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A])) {
            return $this->match('png', 'image/png');
        }

        if ($buffer->checkForBytes([0x47, 0x49, 0x46])) {
            return $this->match('gif', 'image/gif');
        }

        if ($buffer->checkForBytes([0x57, 0x45, 0x42, 0x50], 8)) {
            return $this->match('webp', 'image/webp');
        }

        if ($buffer->checkForBytes([0x69, 0x63, 0x6E, 0x73])) {
            return $this->match('icns', 'image/icns');
        }

        if ($buffer->checkForBytes([0x46, 0x4C, 0x49, 0x46])) {
            return $this->match('flif', 'image/flif');
        }

        if (
            $buffer->checkForBytes([0x43, 0x52], 8)
            && (
                $buffer->checkForBytes([0x49, 0x49, 0x2A, 0x0])
                || $buffer->checkForBytes([0x4D, 0x4D, 0x0, 0x2A])
            )
        ) {
            return $this->match('cr2', 'image/x-canon-cr2');
        }

        if (
            $buffer->checkForBytes([0x49, 0x49, 0x2A, 0x0])
            || $buffer->checkForBytes([0x4D, 0x4D, 0x0, 0x2A])
        ) {
            return $this->match('tif', 'image/tiff');
        }

        if ($buffer->checkForBytes([0x42, 0x4D])) {
            return $this->match('bmp', 'image/bmp');
        }

        if ($buffer->checkForBytes([0x49, 0x49, 0xBC])) {
            return $this->match('jxr', 'image/vnd.ms-photo');
        }

        if ($buffer->checkForBytes([0x38, 0x42, 0x50, 0x53])) {
            return $this->match('psd', 'image/vnd.adobe.photoshop');
        }

        if ($buffer->checkForBytes([0x42, 0x50, 0x47, 0xFB])) {
            return $this->match('bpg', 'image/bpg');
        }

        if (
            $buffer->checkForBytes([0xFF, 0x0A])
            || $buffer->checkForBytes([0x00, 0x00, 0x00, 0x0C, 0x4A, 0x58, 0x4C, 0x20, 0x0D, 0x0A, 0x87, 0x0A])
        ) {
            return $this->match('jxl', 'image/jxl');
        }

        if ($buffer->checkForBytes([0x00, 0x00, 0x00, 0x0C, 0x6A, 0x50, 0x20, 0x20, 0x0D, 0x0A, 0x87, 0x0A])) {
            if ($buffer->checkForBytes([0x6A, 0x70, 0x32, 0x20], 20)) {
                return $this->match('jp2', 'image/jp2');
            }

            if ($buffer->checkForBytes([0x6A, 0x70, 0x78, 0x20], 20)) {
                return $this->match('jpx', 'image/jpx');
            }

            if ($buffer->checkForBytes([0x6A, 0x70, 0x6D, 0x20], 20)) {
                return $this->match('jpm', 'image/jpm');
            }

            if ($buffer->checkForBytes([0x6D, 0x6A, 0x70, 0x32], 20)) {
                return $this->match('mj2', 'image/mj2');
            }
        }

        if ($buffer->checkForBytes([0x66, 0x74, 0x79, 0x70], 4)) {
            if ($buffer->checkForBytes([0x6D, 0x69, 0x66, 0x31], 8)) {
                return $this->match('heic', 'image/heif');
            }

            if ($buffer->checkForBytes([0x6D, 0x73, 0x66, 0x31], 8)) {
                return $this->match('heic', 'image/heif-sequence');
            }

            if (
                $buffer->checkForBytes([0x68, 0x65, 0x69, 0x63], 8)
                || $buffer->checkForBytes([0x68, 0x65, 0x69, 0x78], 8)
            ) {
                return $this->match('heic', 'image/heic');
            }

            if (
                $buffer->checkForBytes([0x68, 0x65, 0x76, 0x63], 8)
                || $buffer->checkForBytes([0x68, 0x65, 0x76, 0x78], 8)
            ) {
                // @codeCoverageIgnoreStart
                return $this->match('heic', 'image/heic-sequence');
                // @codeCoverageIgnoreEnd
            }

            if (
                $buffer->checkForBytes([0x61, 0x76, 0x69, 0x66], 8)
                || $buffer->checkForBytes([0x61, 0x76, 0x69, 0x73], 8)
            ) {
                return $this->match('avif', 'image/avif');
            }

            if ($buffer->checkForBytes([0x63, 0x72, 0x78, 0x20], 8)) {
                return $this->match('cr3', 'image/x-canon-cr3');
            }
        }

        if ($buffer->checkForBytes([0xAB, 0x4B, 0x54, 0x58, 0x20, 0x31, 0x31, 0xBB, 0x0D, 0x0A, 0x1A, 0x0A])) {
            return $this->match('ktx', 'image/ktx');
        }

        if ($buffer->checkForBytes([0x44, 0x49, 0x43, 0x4D], 128)) {
            return $this->match('dcm', 'application/dicom');
        }

        if ($buffer->checkForBytes([0xFF, 0x4F, 0xFF, 0x51])) {
            return $this->match('j2c', 'image/j2c');
        }

        if ($buffer->checkForBytes([0x49, 0x49, 0x52, 0x4F, 0x08, 0x00, 0x00, 0x00, 0x18])) {
            return $this->match('orf', 'image/x-olympus-orf');
        }

        if ($buffer->checkString('FUJIFILMCCD-RAW')) {
            return $this->match('raf', 'image/x-fujifilm-raf');
        }

        if ($buffer->checkForBytes([0x49, 0x49, 0x55, 0x00, 0x18, 0x00, 0x00, 0x00, 0x88, 0xE7, 0x74, 0xD8])) {
            return $this->match('rw2', 'image/x-panasonic-rw2');
        }

        if ($buffer->checkString('gimp xcf ')) {
            return $this->match('xcf', 'image/x-xcf');
        }

        return null;
    }
}
