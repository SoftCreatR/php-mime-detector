<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Support;

use ZipArchive;

/**
 * Reads the first, bounded IWA block to identify the iWork document family.
 * ZIP entry names are shared by Pages, Numbers, and Keynote.
 */
final class IWorkArchiveInspector
{
    private const MAX_BLOCK_SIZE = 65536;

    public function detect(ZipArchive $zip): ?string
    {
        $iwa = $zip->getFromName('Index/Document.iwa', self::MAX_BLOCK_SIZE + 4);

        if (!\is_string($iwa) || \strlen($iwa) < 4 || $iwa[0] !== "\0") {
            return null;
        }

        $compressedLength = \ord($iwa[1]) | (\ord($iwa[2]) << 8) | (\ord($iwa[3]) << 16);

        if (
            $compressedLength < 1
            || $compressedLength > self::MAX_BLOCK_SIZE
            || \strlen($iwa) < $compressedLength + 4
        ) {
            return null;
        }

        $block = $this->decompressSnappy(\substr($iwa, 4, $compressedLength));

        return $block === null ? null : $this->detectRootDocument($block);
    }

    private function detectRootDocument(string $block): ?string
    {
        $position = 0;
        $infoLength = $this->readVarint($block, $position, \strlen($block));

        if ($infoLength === null || $infoLength > \strlen($block) - $position) {
            return null;
        }

        $info = \substr($block, $position, $infoLength);
        $position += $infoLength;
        [$identifier, $messageInfo] = $this->archiveInfo($info);

        if ($identifier !== 1 || $messageInfo === null) {
            return null;
        }

        [$type, $payloadLength] = $this->messageInfo($messageInfo);

        if ($payloadLength === null || $payloadLength > \strlen($block) - $position) {
            return null;
        }

        $fields = $this->payloadFieldNumbers(\substr($block, $position, $payloadLength));

        if ($type === 10000 && \in_array(15, $fields, true)) {
            return 'pages';
        }

        if ($type === 1 && \in_array(8, $fields, true) && !\in_array(3, $fields, true)) {
            return 'numbers';
        }

        if ($type === 1 && \in_array(3, $fields, true) && !\in_array(8, $fields, true)) {
            return 'key';
        }

        return null;
    }

    /**
     * @return array{?int, ?string}
     */
    private function archiveInfo(string $bytes): array
    {
        $position = 0;
        $identifier = null;
        $messageInfo = null;

        while ($position < \strlen($bytes)) {
            $field = $this->readField($bytes, $position, \strlen($bytes));

            if ($field === null) {
                return [null, null];
            }

            if ($field[0] === 1 && \is_int($field[1])) {
                $identifier = $field[1];
            }

            if ($field[0] === 2 && \is_string($field[1]) && $messageInfo === null) {
                $messageInfo = $field[1];
            }
        }

        return [$identifier, $messageInfo];
    }

    /**
     * @return array{?int, ?int}
     */
    private function messageInfo(string $bytes): array
    {
        $position = 0;
        $type = null;
        $length = null;

        while ($position < \strlen($bytes)) {
            $field = $this->readField($bytes, $position, \strlen($bytes));

            if ($field === null) {
                return [null, null];
            }

            if ($field[0] === 1 && \is_int($field[1])) {
                $type = $field[1];
            }

            if ($field[0] === 3 && \is_int($field[1])) {
                $length = $field[1];
            }
        }

        return [$type, $length];
    }

    /**
     * @return list<int>
     */
    private function payloadFieldNumbers(string $bytes): array
    {
        $position = 0;
        $numbers = [];

        while ($position < \strlen($bytes)) {
            $field = $this->readField($bytes, $position, \strlen($bytes));

            if ($field === null) {
                return [];
            }

            $numbers[] = $field[0];
        }

        return $numbers;
    }

    /**
     * @return array{int, int|string|null}|null
     */
    private function readField(string $bytes, int &$position, int $end): ?array
    {
        $tag = $this->readVarint($bytes, $position, $end);

        if ($tag === null || $tag === 0) {
            return null;
        }

        $number = $tag >> 3;
        $wire = $tag & 7;

        if ($wire === 0) {
            $value = $this->readVarint($bytes, $position, $end);

            return $value === null ? null : [$number, $value];
        }

        if ($wire === 2) {
            $length = $this->readVarint($bytes, $position, $end);

            if ($length === null || $length > $end - $position) {
                return null;
            }

            $value = \substr($bytes, $position, $length);
            $position += $length;

            return [$number, $value];
        }

        $length = $wire === 1 ? 8 : ($wire === 5 ? 4 : 0);

        if ($length === 0 || $length > $end - $position) {
            return null;
        }

        $position += $length;

        return [$number, null];
    }

    private function decompressSnappy(string $bytes): ?string
    {
        $position = 0;
        $expected = $this->readVarint($bytes, $position, \strlen($bytes));

        if ($expected === null || $expected > self::MAX_BLOCK_SIZE) {
            return null;
        }

        $output = '';

        while ($position < \strlen($bytes) && \strlen($output) < $expected) {
            $tag = \ord($bytes[$position++]);
            $chunk = ($tag & 3) === 0
                ? $this->readLiteral($bytes, $position, $tag)
                : $this->readCopy($bytes, $position, $tag, $output);

            if ($chunk === null || \strlen($chunk) > $expected - \strlen($output)) {
                return null;
            }

            $output .= $chunk;
        }

        return \strlen($output) === $expected ? $output : null;
    }

    private function readLiteral(string $bytes, int &$position, int $tag): ?string
    {
        $length = $tag >> 2;

        if ($length < 60) {
            $length++;
        } else {
            $byteCount = $length - 59;

            if ($position + $byteCount > \strlen($bytes)) {
                return null;
            }

            $length = 1;

            for ($i = 0; $i < $byteCount; $i++) {
                $length += \ord($bytes[$position++]) << ($i * 8);
            }
        }

        if ($length > self::MAX_BLOCK_SIZE || $position + $length > \strlen($bytes)) {
            return null;
        }

        $literal = \substr($bytes, $position, $length);
        $position += $length;

        return $literal;
    }

    private function readCopy(string $bytes, int &$position, int $tag, string $output): ?string
    {
        $kind = $tag & 3;
        $offsetBytes = $kind === 1 ? 1 : ($kind === 2 ? 2 : 4);

        if ($position + $offsetBytes > \strlen($bytes)) {
            return null;
        }

        $offset = $kind === 1 ? ($tag & 0xE0) << 3 : 0;

        for ($i = 0; $i < $offsetBytes; $i++) {
            $offset |= \ord($bytes[$position++]) << ($i * 8);
        }

        if ($offset < 1 || $offset > \strlen($output)) {
            return null;
        }

        $length = $kind === 1 ? 4 + (($tag >> 2) & 7) : 1 + ($tag >> 2);
        $copy = '';

        while (\strlen($copy) < $length) {
            $copy .= \substr($output . $copy, -$offset, \min($length - \strlen($copy), $offset));
        }

        return $copy;
    }

    private function readVarint(string $bytes, int &$position, int $end): ?int
    {
        $value = 0;

        for ($i = 0; $i < 5 && $position < $end; $i++) {
            $byte = \ord($bytes[$position++]);
            $value |= ($byte & 0x7F) << ($i * 7);

            if (($byte & 0x80) === 0) {
                return $value;
            }
        }

        return null;
    }
}
