<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Support;

/** Reads only the protobuf wire types needed by an iWork archive header. */
final class IWorkProtobufReader
{
    /**
     * @return array{int, int|string|null}|null
     */
    public function readField(string $bytes, int &$position, int $end): ?array
    {
        $tag = $this->readVarint($bytes, $position, $end);

        if ($tag === null || $tag === 0) {
            return null;
        }

        $number = $tag >> 3;

        return match ($tag & 7) {
            0 => $this->readIntegerField($bytes, $position, $end, $number),
            2 => $this->readBytesField($bytes, $position, $end, $number),
            1 => $this->skipFixedField($position, $end, $number, 8),
            5 => $this->skipFixedField($position, $end, $number, 4),
            default => null,
        };
    }

    /**
     * @return array{int, int}|null
     */
    private function readIntegerField(string $bytes, int &$position, int $end, int $number): ?array
    {
        $value = $this->readVarint($bytes, $position, $end);

        return $value === null ? null : [$number, $value];
    }

    /**
     * @return array{int, string}|null
     */
    private function readBytesField(string $bytes, int &$position, int $end, int $number): ?array
    {
        $length = $this->readVarint($bytes, $position, $end);

        if ($length === null || $length > $end - $position) {
            return null;
        }

        $value = \substr($bytes, $position, $length);
        $position += $length;

        return [$number, $value];
    }

    /**
     * @return array{int, null}|null
     */
    private function skipFixedField(int &$position, int $end, int $number, int $length): ?array
    {
        if ($length > $end - $position) {
            return null;
        }

        $position += $length;

        return [$number, null];
    }

    public function readVarint(string $bytes, int &$position, int $end): ?int
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
