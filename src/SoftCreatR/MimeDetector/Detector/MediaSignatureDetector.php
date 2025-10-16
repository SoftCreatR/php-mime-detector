<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Detects audio and video container formats.
 */
#[DetectorCategory('media')]
final class MediaSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        $brandMatch = $this->detectIsoBrandMatch($buffer);
        if ($brandMatch instanceof MimeTypeMatch) {
            return $brandMatch;
        }

        if ($buffer->checkForBytes([0x0B, 0x77])) {
            return $this->match('ac3', 'audio/vnd.dolby.dd-raw');
        }

        if ($buffer->checkString('MP+')) {
            return $this->match('mpc', 'audio/x-musepack');
        }

        if ($buffer->checkString('MPCK')) {
            return $this->match('mpc', 'audio/x-musepack');
        }

        if ($buffer->checkForBytes([0x44, 0x53, 0x44, 0x20])) {
            return $this->match('dsf', 'audio/x-dsf');
        }

        if (
            $buffer->checkForBytes([0x33, 0x67, 0x70, 0x35])
            || (
                $buffer->checkForBytes([0x00, 0x00, 0x00])
                && $buffer->checkForBytes([0x66, 0x74, 0x79, 0x70], 4)
                && (
                    $buffer->checkForBytes([0x6D, 0x70, 0x34, 0x31], 8)
                    || $buffer->checkForBytes([0x6D, 0x70, 0x34, 0x32], 8)
                    || $buffer->checkForBytes([0x69, 0x73, 0x6F, 0x6D], 8)
                    || $buffer->checkForBytes([0x69, 0x73, 0x6F, 0x32], 8)
                    || $buffer->checkForBytes([0x6D, 0x6D, 0x70, 0x34], 8)
                    || $buffer->checkForBytes([0x4D, 0x34, 0x56], 8)
                    || $buffer->checkForBytes([0x64, 0x61, 0x73, 0x68], 8)
                )
            )
        ) {
            return $this->match('mp4', 'video/mp4');
        }

        if ($buffer->checkForBytes([0x4D, 0x54, 0x68, 0x64])) {
            return $this->match('mid', 'audio/midi');
        }

        if ($buffer->checkForBytes([0x1A, 0x45, 0xDF, 0xA3])) {
            $idPos = $buffer->searchForBytes([0x42, 0x82]);

            if ($idPos !== -1) {
                if ($buffer->checkString('matroska', $idPos + 3)) {
                    return $this->match('mkv', 'video/x-matroska');
                }

                if ($buffer->checkString('webm', $idPos + 3)) {
                    return $this->match('webm', 'video/webm');
                }
            }
        }

        if (
            $buffer->checkForBytes([0x00, 0x00, 0x00, 0x14, 0x66, 0x74, 0x79, 0x70, 0x71, 0x74, 0x20, 0x20])
            || $buffer->checkForBytes([0x66, 0x72, 0x65, 0x65], 4)
            || $buffer->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x71, 0x74, 0x20, 0x20], 4)
            || $buffer->checkForBytes([0x6D, 0x64, 0x61, 0x74], 4)
            || $buffer->checkForBytes([0x6D, 0x6F, 0x6F, 0x76], 4)
            || $buffer->checkForBytes([0x77, 0x69, 0x64, 0x65], 4)
        ) {
            return $this->match('mov', 'video/quicktime');
        }

        if ($buffer->checkForBytes([0x2E, 0x52, 0x4D, 0x46])) {
            return $this->match('rm', 'application/vnd.rn-realmedia');
        }

        if ($buffer->checkForBytes([0x52, 0x49, 0x46, 0x46])) {
            if ($buffer->checkForBytes([0x41, 0x56, 0x49], 8)) {
                return $this->match('avi', 'video/vnd.avi');
            }

            if ($buffer->checkForBytes([0x57, 0x41, 0x56, 0x45], 8)) {
                return $this->match('wav', 'audio/vnd.wave');
            }

            if ($buffer->checkForBytes([0x51, 0x4C, 0x43, 0x4D], 8)) {
                return $this->match('qcp', 'audio/qcelp');
            }

            if ($buffer->checkForBytes([0x41, 0x43, 0x4F, 0x4E], 8)) {
                return $this->match('ani', 'application/x-navi-animation');
            }
        }

        if ($buffer->checkForBytes([0x30, 0x26, 0xB2, 0x75, 0x8E, 0x66, 0xCF, 0x11, 0xA6, 0xD9])) {
            return $this->match('wmv', 'video/x-ms-wmv');
        }

        if (
            $buffer->checkForBytes([0x00, 0x00, 0x01, 0xBA])
            || $buffer->checkForBytes([0x00, 0x00, 0x01, 0xB3])
        ) {
            return $this->match('mpg', 'video/mpeg');
        }

        if ($buffer->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x33, 0x67], 4)) {
            return $this->match('3gp', 'video/3gpp');
        }

        $limit = \min(2, \max(0, $buffer->length() - 16));

        for ($offset = 0; $offset < $limit; $offset++) {
            if (
                $buffer->checkForBytes([0x49, 0x44, 0x33], $offset)
                || $buffer->checkForBytes([0xFF, 0xE2], $offset, [0xFF, 0xE2])
            ) {
                return $this->match('mp3', 'audio/mpeg');
            }

            if ($buffer->checkForBytes([0xFF, 0xE4], $offset, [0xFF, 0xE4])) {
                return $this->match('mp2', 'audio/mpeg');
            }

            if ($buffer->checkForBytes([0xFF, 0xF8], $offset, [0xFF, 0xFC])) {
                return $this->match('mp2', 'audio/mpeg');
            }

            if ($buffer->checkForBytes([0xFF, 0xF0], $offset, [0xFF, 0xFC])) {
                return $this->match('mp4', 'audio/mpeg');
            }
        }

        if (
            $buffer->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x4D, 0x34, 0x41], 4)
            || $buffer->checkForBytes([0x4D, 0x34, 0x41, 0x20])
        ) {
            return $this->match('m4a', 'audio/mp4');
        }

        if ($buffer->checkForBytes([0x4F, 0x70, 0x75, 0x73, 0x48, 0x65, 0x61, 0x64], 28)) {
            return $this->match('opus', 'audio/opus');
        }

        if ($buffer->checkForBytes([0x4F, 0x67, 0x67, 0x53])) {
            if ($buffer->checkForBytes([0x80, 0x74, 0x68, 0x65, 0x6F, 0x72, 0x61], 28)) {
                return $this->match('ogv', 'video/ogg');
            }

            if ($buffer->checkForBytes([0x01, 0x76, 0x69, 0x64, 0x65, 0x6F, 0x00], 28)) {
                return $this->match('ogm', 'video/ogg');
            }

            if ($buffer->checkForBytes([0x7F, 0x46, 0x4C, 0x41, 0x43], 28)) {
                return $this->match('oga', 'audio/ogg');
            }

            if ($buffer->checkForBytes([0x53, 0x70, 0x65, 0x65, 0x78, 0x20, 0x20], 28)) {
                return $this->match('spx', 'audio/ogg');
            }

            if ($buffer->checkForBytes([0x01, 0x76, 0x6F, 0x72, 0x62, 0x69, 0x73], 28)) {
                return $this->match('ogg', 'audio/ogg');
            }

            return $this->match('ogx', 'application/ogg');
        }

        if ($buffer->checkForBytes([0x66, 0x4C, 0x61, 0x43])) {
            return $this->match('flac', 'audio/x-flac');
        }

        if ($buffer->checkForBytes([0x4D, 0x41, 0x43, 0x20])) {
            return $this->match('ape', 'audio/ape');
        }

        if ($buffer->checkForBytes([0x77, 0x76, 0x70, 0x6B])) {
            return $this->match('wv', 'audio/wavpack');
        }

        if ($buffer->checkForBytes([0x23, 0x21, 0x41, 0x4D, 0x52, 0x0A])) {
            return $this->match('amr', 'audio/amr');
        }

        if ($buffer->checkForBytes([0x46, 0x4F, 0x52, 0x4D, 0x00])) {
            return $this->match('aif', 'audio/aiff');
        }

        if (
            $buffer->checkForBytes([
                0x06, 0x0E, 0x2B, 0x34, 0x02, 0x05, 0x01, 0x01, 0x0D, 0x01, 0x02, 0x01, 0x01, 0x02,
            ])
        ) {
            return $this->match('mxf', 'application/mxf');
        }

        if (
            (
                $buffer->checkForBytes([0x47])
                && $buffer->checkForBytes([0x47], 188)
            )
            || (
                $buffer->checkForBytes([0x47], 4)
                && (
                    $buffer->checkForBytes([0x47], 192)
                    || $buffer->checkForBytes([0x47], 196)
                )
            )
        ) {
            return $this->match('mts', 'video/mp2t');
        }

        if ($buffer->checkForBytes([0x46, 0x4C, 0x56, 0x01])) {
            return $this->match('flv', 'video/x-flv');
        }

        if (
            $buffer->checkForBytes([0x64, 0x6E, 0x73, 0x2E])
            || $buffer->checkForBytes([0x2E, 0x73, 0x6E, 0x64])
        ) {
            return $this->match('au', 'audio/basic');
        }

        if ($buffer->checkString('IMPM')) {
            return $this->match('it', 'audio/x-it');
        }

        if ($buffer->checkString('SCRM', 44)) {
            return $this->match('s3m', 'audio/x-s3m');
        }

        if ($buffer->checkString('Extended Module:')) {
            return $this->match('xm', 'audio/x-xm');
        }

        if ($buffer->checkString('Creative Voice File')) {
            return $this->match('voc', 'audio/x-voc');
        }

        return null;
    }

    private function detectIsoBrandMatch(FileBuffer $buffer): ?MimeTypeMatch
    {
        if (!$buffer->checkForBytes([0x66, 0x74, 0x79, 0x70], 4)) {
            return null;
        }

        $brand = '';

        for ($i = 8; $i < 12; $i++) {
            $byte = $buffer->get($i);

            if ($byte === null) {
                return null;
            }

            $brand .= \chr($byte);
        }

        $brand = \str_replace("\0", ' ', $brand);
        $normalized = \strtoupper(\rtrim($brand, ' '));

        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'QT') {
            return $this->match('mov', 'video/quicktime');
        }

        if (\str_starts_with($normalized, '3G')) {
            if (\str_starts_with($normalized, '3G2')) {
                return $this->match('3g2', 'video/3gpp2');
            }

            return $this->match('3gp', 'video/3gpp');
        }

        if (\str_starts_with($normalized, 'M4V')) {
            return $this->match('m4v', 'video/x-m4v');
        }

        $map = [
            'M4P' => ['m4p', 'video/mp4'],
            'M4B' => ['m4b', 'audio/mp4'],
            'M4A' => ['m4a', 'audio/x-m4a'],
            'F4V' => ['f4v', 'video/mp4'],
            'F4P' => ['f4p', 'video/mp4'],
            'F4A' => ['f4a', 'audio/mp4'],
            'F4B' => ['f4b', 'audio/mp4'],
        ];

        if (isset($map[$normalized])) {
            [$extension, $mime] = $map[$normalized];

            return $this->match($extension, $mime);
        }

        return null;
    }
}
