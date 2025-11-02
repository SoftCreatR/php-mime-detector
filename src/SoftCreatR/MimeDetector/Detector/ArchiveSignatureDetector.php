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
 * Detects archive formats such as RAR, TAR, and gzip.
 */
#[DetectorCategory('archive')]
final class ArchiveSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($this->isTarArchive($buffer)) {
            return $this->match('tar', 'application/x-tar');
        }

        if (
            $buffer->checkForBytes([0xC7, 0x71])
            || $buffer->checkString('070707')
        ) {
            return $this->match('cpio', 'application/x-cpio');
        }

        $byte = $buffer->get(6);

        if (
            ($byte === 0x0 || $byte === 0x1)
            && $buffer->checkForBytes([0x52, 0x61, 0x72, 0x21, 0x1A, 0x07])
        ) {
            return $this->match('rar', 'application/x-rar-compressed');
        }

        if ($buffer->checkForBytes([0x1F, 0x8B, 0x08])) {
            return $this->match('gz', 'application/gzip');
        }

        if ($buffer->checkForBytes([0x42, 0x5A, 0x68])) {
            return $this->match('bz2', 'application/x-bzip2');
        }

        if ($buffer->checkForBytes([0x37, 0x7A, 0xBC, 0xAF, 0x27, 0x1C])) {
            return $this->match('7z', 'application/x-7z-compressed');
        }

        if ($buffer->checkForBytes([0x78, 0x01])) {
            return $this->match('dmg', 'application/x-apple-diskimage');
        }

        if (
            $buffer->checkForBytes([0x4D, 0x53, 0x43, 0x46])
            || $buffer->checkForBytes([0x49, 0x53, 0x63, 0x28])
        ) {
            return $this->match('cab', 'application/vnd.ms-cab-compressed');
        }

        if (
            $buffer->checkForBytes([
                0x21, 0x3C, 0x61, 0x72, 0x63, 0x68, 0x3E,
                0x0A, 0x64, 0x65, 0x62, 0x69, 0x61, 0x6E,
                0x2D, 0x62, 0x69, 0x6E, 0x61, 0x72, 0x79,
            ])
        ) {
            return $this->match('deb', 'application/x-deb');
        }

        if ($buffer->checkForBytes([0x21, 0x3C, 0x61, 0x72, 0x63, 0x68, 0x3E])) {
            return $this->match('ar', 'application/x-unix-archive');
        }

        if ($buffer->checkForBytes([0xED, 0xAB, 0xEE, 0xDB])) {
            return $this->match('rpm', 'application/x-rpm');
        }

        if ($buffer->checkForBytes([0x1F, 0xA0]) || $buffer->checkForBytes([0x1F, 0x9D])) {
            return $this->match('z', 'application/x-compress');
        }

        if ($buffer->checkForBytes([0x4C, 0x5A, 0x49, 0x50])) {
            return $this->match('lz', 'application/x-lzip');
        }

        if ($buffer->checkForBytes([0xFD, 0x37, 0x7A, 0x58, 0x5A, 0x00])) {
            return $this->match('xz', 'application/x-xz');
        }

        if ($buffer->checkForBytes([0x28, 0xB5, 0x2F, 0xFD])) {
            return $this->match('zst', 'application/zstd');
        }

        if ($buffer->checkForBytes([0x04, 0x22, 0x4D, 0x18])) {
            return $this->match('lz4', 'application/x-lz4');
        }

        if (
            $buffer->checkString('**ACE', 7)
            && $buffer->checkString('**', 12)
        ) {
            return $this->match('ace', 'application/x-ace-compressed');
        }

        if ($buffer->checkForBytes([0x60, 0xEA])) {
            return $this->match('arj', 'application/x-arj');
        }

        if ($this->isLzhArchive($buffer)) {
            return $this->match('lzh', 'application/x-lzh-compressed');
        }

        return null;
    }

    private function isTarArchive(FileBuffer $buffer): bool
    {
        if ($buffer->length() < 512) {
            return false;
        }

        if ($buffer->checkForBytes([0x75, 0x73, 0x74, 0x61, 0x72], 257)) {
            return true;
        }

        return $this->tarHeaderChecksumMatches($buffer);
    }

    private function tarHeaderChecksumMatches(FileBuffer $buffer): bool
    {
        $checksumField = '';

        for ($i = 148; $i < 156; $i++) {
            $byte = $buffer->get($i);

            if ($byte === null) {
                return false;
            }

            $checksumField .= \chr($byte);
        }

        $trimmedChecksum = \rtrim($checksumField, " \0");

        if ($trimmedChecksum === '') {
            return false;
        }

        if (!\preg_match('/^[0-7]+$/', $trimmedChecksum)) {
            return false;
        }

        $readChecksum = \octdec($trimmedChecksum);

        $sum = 0;

        for ($i = 0; $i < 512; $i++) {
            $byte = $buffer->get($i);

            if ($byte === null) {
                return false;
            }

            if ($i >= 148 && $i < 156) {
                $sum += 0x20;
                continue;
            }

            $sum += $byte;
        }

        return $readChecksum === $sum;
    }

    private function isLzhArchive(FileBuffer $buffer): bool
    {
        $signatures = [
            '-lh0-',
            '-lh1-',
            '-lh2-',
            '-lh3-',
            '-lh4-',
            '-lh5-',
            '-lh6-',
            '-lh7-',
            '-lzs-',
            '-lz4-',
            '-lz5-',
            '-lhd-',
        ];

        foreach ($signatures as $signature) {
            if ($buffer->checkString($signature, 2)) {
                return true;
            }
        }

        return false;
    }
}
