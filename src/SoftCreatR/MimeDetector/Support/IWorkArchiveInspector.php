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
    private IWorkProtobufReader $reader;

    public function __construct()
    {
        $this->reader = new IWorkProtobufReader();
    }

    public function detect(ZipArchive $zip): ?string
    {
        $iwa = $zip->getFromName('Index/Document.iwa', SnappyBlockDecoder::MAX_BLOCK_SIZE + 4);

        if (!\is_string($iwa) || \strlen($iwa) < 4 || $iwa[0] !== "\0") {
            return null;
        }

        $compressedLength = \ord($iwa[1]) | (\ord($iwa[2]) << 8) | (\ord($iwa[3]) << 16);

        if (
            $compressedLength < 1
            || $compressedLength > SnappyBlockDecoder::MAX_BLOCK_SIZE
            || \strlen($iwa) < $compressedLength + 4
        ) {
            return null;
        }

        $block = (new SnappyBlockDecoder())->decompress(\substr($iwa, 4, $compressedLength));

        return $block === null ? null : $this->detectRootDocument($block);
    }

    private function detectRootDocument(string $block): ?string
    {
        $position = 0;
        $infoLength = $this->reader->readVarint($block, $position, \strlen($block));

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

        return $this->classifyRoot($type, $this->payloadFieldNumbers(\substr($block, $position, $payloadLength)));
    }

    /**
     * @param list<int> $fields
     */
    private function classifyRoot(?int $type, array $fields): ?string
    {
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
            $field = $this->reader->readField($bytes, $position, \strlen($bytes));

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
            $field = $this->reader->readField($bytes, $position, \strlen($bytes));

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
            $field = $this->reader->readField($bytes, $position, \strlen($bytes));

            if ($field === null) {
                return [];
            }

            $numbers[] = $field[0];
        }

        return $numbers;
    }
}
