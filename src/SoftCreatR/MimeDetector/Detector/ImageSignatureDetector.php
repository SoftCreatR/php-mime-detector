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
            return $this->isAnimatedPng($buffer)
                ? $this->match('apng', 'image/apng')
                : $this->match('png', 'image/png');
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
            $rawMatch = $this->detectTiffRaw($buffer);

            if ($rawMatch !== null) {
                return $rawMatch;
            }

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

    private function isAnimatedPng(FileBuffer $buffer): bool
    {
        if ($buffer->sliceAsString(8, 8) !== "\x00\x00\x00\x0DIHDR") {
            return false;
        }

        $offset = 33;

        for ($chunk = 1; $chunk < 128 && $offset + 12 <= $buffer->length(); $chunk++) {
            $lengthBytes = $buffer->sliceAsString($offset, 4);
            $length = \unpack('N', $lengthBytes)[1];
            $type = $buffer->sliceAsString($offset + 4, 4);

            if ($length > $buffer->length() - $offset - 12) {
                return false;
            }

            if ($type === 'IDAT') {
                return false;
            }

            if ($type === 'acTL') {
                return $length === 8;
            }

            $offset += $length + 12;
        }

        return false;
    }

    private function detectTiffRaw(FileBuffer $buffer): ?MimeTypeMatch
    {
        $littleEndian = $buffer->checkString('II');
        $ifdOffset = $this->readTiffLong($buffer, 4, $littleEndian);

        if ($ifdOffset === null || $ifdOffset < 8 || $ifdOffset + 2 > $buffer->length()) {
            return null;
        }

        $tagCount = $this->readTiffShort($buffer, $ifdOffset, $littleEndian);

        if ($tagCount === null || $tagCount > 256) {
            return null;
        }

        return $this->scanTiffTags($buffer, $ifdOffset, $tagCount, $littleEndian);
    }

    private function scanTiffTags(FileBuffer $buffer, int $ifdOffset, int $tagCount, bool $littleEndian): ?MimeTypeMatch
    {
        $hasNikonMake = false;
        $hasSubIfds = false;

        for ($i = 0; $i < $tagCount; $i++) {
            $tagOffset = $ifdOffset + 2 + $i * 12;

            if ($tagOffset + 12 > $buffer->length()) {
                break;
            }

            $tag = $this->readTiffShort($buffer, $tagOffset, $littleEndian);

            if ($tag === 50341) {
                return $this->match('arw', 'image/x-sony-arw');
            }

            if ($tag === 50706) {
                return $this->match('dng', 'image/x-adobe-dng');
            }

            if ($tag === 271 && $this->isNikonMakeTag($buffer, $tagOffset, $littleEndian)) {
                $hasNikonMake = true;
            }

            if ($tag === 330) {
                $hasSubIfds = true;
            }
        }

        if ($hasNikonMake && $hasSubIfds && $this->hasNefHeader($buffer)) {
            return $this->match('nef', 'image/x-nikon-nef');
        }

        return null;
    }

    private function hasNefHeader(FileBuffer $buffer): bool
    {
        return $buffer->checkForBytes([0x1C, 0x00, 0xFE, 0x00], 8)
            || $buffer->checkForBytes([0x1F, 0x00, 0x0B, 0x00], 8)
            || $buffer->checkForBytes([0x00, 0x1C, 0x00, 0xFE], 8)
            || $buffer->checkForBytes([0x00, 0x1F, 0x00, 0x0B], 8);
    }

    private function isNikonMakeTag(FileBuffer $buffer, int $offset, bool $littleEndian): bool
    {
        if ($this->readTiffShort($buffer, $offset + 2, $littleEndian) !== 2) {
            return false;
        }

        $length = $this->readTiffLong($buffer, $offset + 4, $littleEndian);
        $valueOffset = $this->readTiffLong($buffer, $offset + 8, $littleEndian);

        if ($length === null || $length < 6 || $length > 256 || $valueOffset === null) {
            return false;
        }

        return \strtoupper($buffer->sliceAsString($valueOffset, 5)) === 'NIKON';
    }

    private function readTiffLong(FileBuffer $buffer, int $offset, bool $littleEndian): ?int
    {
        $bytes = $buffer->sliceAsString($offset, 4);

        if (\strlen($bytes) !== 4) {
            return null;
        }

        return \unpack($littleEndian ? 'V' : 'N', $bytes)[1];
    }

    private function readTiffShort(FileBuffer $buffer, int $offset, bool $littleEndian): ?int
    {
        $first = $buffer->get($offset);
        $second = $buffer->get($offset + 1);

        if ($first === null || $second === null) {
            return null;
        }

        return $littleEndian ? $first | ($second << 8) : ($first << 8) | $second;
    }
}
