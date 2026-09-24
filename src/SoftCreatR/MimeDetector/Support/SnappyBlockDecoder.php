<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Support;

/** Decodes a bounded raw Snappy block without requiring an extension. */
final class SnappyBlockDecoder
{
    public const MAX_BLOCK_SIZE = 65536;

    public function decompress(string $bytes): ?string
    {
        $position = 0;
        $expected = (new IWorkProtobufReader())->readVarint($bytes, $position, \strlen($bytes));

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
            return $this->readLiteralBytes($bytes, $position, $length + 1);
        }

        $byteCount = $length - 59;

        if ($position + $byteCount > \strlen($bytes)) {
            return null;
        }

        $length = 1;

        for ($i = 0; $i < $byteCount; $i++) {
            $length += \ord($bytes[$position++]) << ($i * 8);
        }

        return $this->readLiteralBytes($bytes, $position, $length);
    }

    private function readLiteralBytes(string $bytes, int &$position, int $length): ?string
    {
        if ($length > self::MAX_BLOCK_SIZE || $position + $length > \strlen($bytes)) {
            return null;
        }

        $literal = \substr($bytes, $position, $length);
        $position += $length;

        return $literal;
    }

    private function readCopy(string $bytes, int &$position, int $tag, string $output): ?string
    {
        $offset = $this->readOffset($bytes, $position, $tag);

        if ($offset === null || $offset < 1 || $offset > \strlen($output)) {
            return null;
        }

        $length = ($tag & 3) === 1 ? 4 + (($tag >> 2) & 7) : 1 + ($tag >> 2);

        return $this->copyFromOutput($output, $offset, $length);
    }

    private function readOffset(string $bytes, int &$position, int $tag): ?int
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

        return $offset;
    }

    private function copyFromOutput(string $output, int $offset, int $length): string
    {
        $copy = '';

        while (\strlen($copy) < $length) {
            $copy .= \substr($output . $copy, -$offset, \min($length - \strlen($copy), $offset));
        }

        return $copy;
    }
}
