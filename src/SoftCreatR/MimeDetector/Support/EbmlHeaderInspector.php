<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Support;

use SoftCreatR\MimeDetector\Detection\FileBuffer;

/**
 * Reads DocType only from bounded, structurally complete EBML header elements.
 */
final class EbmlHeaderInspector
{
    private const MAX_HEADER_BYTES = 4096;

    public function documentType(FileBuffer $buffer): ?string
    {
        if (!$buffer->checkString("\x1A\x45\xDF\xA3")) {
            return null;
        }

        $offset = 4;
        $limit = \min($buffer->length(), self::MAX_HEADER_BYTES);
        $length = $this->readVariableInteger($buffer, $offset, $limit, false);

        if ($length === null || $length > $limit - $offset) {
            return null;
        }

        $end = $offset + $length;
        $documentType = null;

        while ($offset < $end) {
            $id = $this->readVariableInteger($buffer, $offset, $end, true);
            $size = $this->readVariableInteger($buffer, $offset, $end, false);

            if ($id === null || $size === null || $size > $end - $offset) {
                return null;
            }

            if ($id === 0x4282) {
                if ($documentType !== null || $size === 0) {
                    return null;
                }

                $documentType = \rtrim($buffer->sliceAsString($offset, $size), "\0");
            }

            $offset += $size;
        }

        return $documentType;
    }

    /**
     * Decode an ID or finite size without allowing integer overflow or out-of-bounds reads.
     */
    private function readVariableInteger(FileBuffer $buffer, int &$offset, int $end, bool $identifier): ?int
    {
        if ($offset >= $end) {
            return null;
        }

        $first = $buffer->get($offset);
        $mask = 0x80;
        $width = 1;

        while ($mask > 0 && ($first & $mask) === 0) {
            $mask >>= 1;
            ++$width;
        }

        if ($mask === 0 || $width > ($identifier ? 4 : 8) || $width > $end - $offset) {
            return null;
        }

        $value = $identifier ? $first : $first & ($mask - 1);
        $unknownSize = !$identifier && $value === $mask - 1;
        ++$offset;

        for ($index = 1; $index < $width; ++$index) {
            $byte = $buffer->get($offset++);
            $unknownSize = $unknownSize && $byte === 0xFF;

            // Header sizes never need to exceed the bounded buffer.
            if (!$identifier && $value > \intdiv($end - $byte, 256)) {
                return null;
            }

            $value = ($value << 8) | $byte;
        }

        return $unknownSize ? null : $value;
    }
}
