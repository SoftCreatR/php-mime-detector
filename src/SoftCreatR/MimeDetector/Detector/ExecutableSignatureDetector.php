<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Detects platform specific executable formats.
 */
#[DetectorCategory('binary')]
final class ExecutableSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($buffer->checkForBytes([0x4D, 0x5A])) {
            return $this->isPortableExecutable($context)
                ? $this->match('exe', 'application/vnd.microsoft.portable-executable')
                : $this->match('exe', 'application/x-msdownload');
        }

        if (
            $buffer->checkForBytes([0x7F, 0x45, 0x4C, 0x46])
        ) {
            return $this->match('elf', 'application/x-elf');
        }

        if (
            $buffer->checkForBytes([0xCF, 0xFA, 0xED, 0xFE])
            || $buffer->checkForBytes([0xFE, 0xED, 0xFA, 0xCF])
            || $buffer->checkForBytes([0xCE, 0xFA, 0xED, 0xFE])
            || $buffer->checkForBytes([0xFE, 0xED, 0xFA, 0xCE])
            || $this->isFatMachO($buffer)
        ) {
            return $this->match('macho', 'application/x-mach-binary');
        }

        if (
            $buffer->length() >= 10
            && $buffer->checkForBytes([0xCA, 0xFE, 0xBA, 0xBE])
            && \unpack('n', $buffer->sliceAsString(6, 2))[1] >= 43
            && \unpack('n', $buffer->sliceAsString(8, 2))[1] > 0
        ) {
            return $this->match('class', 'application/java-vm');
        }

        $marker = $buffer->get(0);

        if (
            ($marker === 0x43 || $marker === 0x46)
            && $buffer->checkForBytes([0x57, 0x53], 1)
        ) {
            return $this->match('swf', 'application/x-shockwave-flash');
        }

        if ($buffer->checkForBytes([0x00, 0x61, 0x73, 0x6D])) {
            return $this->match('wasm', 'application/wasm');
        }

        if ($buffer->checkForBytes([0x1B, 0x4C, 0x75, 0x61])) {
            return $this->match('luac', 'application/x-lua-bytecode');
        }

        if ($buffer->checkForBytes([0x4E, 0x45, 0x53, 0x1A])) {
            return $this->match('nes', 'application/x-nintendo-nes-rom');
        }

        if ($buffer->checkForBytes([0x43, 0x72, 0x32, 0x34])) {
            return $this->match('crx', 'application/x-google-chrome-extension');
        }

        return null;
    }

    private function isPortableExecutable(DetectionContext $context): bool
    {
        $bytes = $context->buffer()->sliceAsString(60, 4);

        if (\strlen($bytes) !== 4) {
            return false;
        }

        $offset = \unpack('V', $bytes)[1];

        if ($offset < 64) {
            return false;
        }

        // PE headers can live beyond the initial cache. Only read the fixed
        // COFF header and optional-header magic at the declared file offset.
        $handle = @\fopen($context->file(), 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $stat = \fstat($handle);

            if ($stat === false || $offset > $stat['size'] - 26 || @\fseek($handle, $offset) !== 0) {
                return false;
            }

            $header = @\fread($handle, 26);

            return \is_string($header)
                && \strlen($header) === 26
                && \substr($header, 0, 4) === "PE\0\0"
                && \in_array(\substr($header, 24, 2), ["\x0B\x01", "\x0B\x02"], true);
        } finally {
            \fclose($handle);
        }
    }

    private function isFatMachO(FileBuffer $buffer): bool
    {
        $magic = $buffer->sliceAsString(0, 4);

        $fatSignatures = ["\xCA\xFE\xBA\xBE", "\xBE\xBA\xFE\xCA", "\xCA\xFE\xBA\xBF", "\xBF\xBA\xFE\xCA"];

        if (!\in_array($magic, $fatSignatures, true)) {
            return false;
        }

        $littleEndian = \in_array($buffer->get(0), [0xBE, 0xBF], true);
        $wide = $magic === "\xCA\xFE\xBA\xBF" || $magic === "\xBF\xBA\xFE\xCA";
        $entrySize = $wide ? 32 : 20;
        $format = $littleEndian ? 'V' : 'N';
        $countBytes = $buffer->sliceAsString(4, 4);

        if (\strlen($countBytes) !== 4) {
            return false;
        }

        $count = \unpack($format, $countBytes)[1];

        if ($count < 1 || $count > 20 || $buffer->length() < 8 + $count * $entrySize) {
            return false;
        }

        if ($wide) {
            $entryFormat = $littleEndian
                ? 'Vcpu/Vsubtype/Poffset/Psize/Valign/Vreserved'
                : 'Ncpu/Nsubtype/Joffset/Jsize/Nalign/Nreserved';
        } else {
            $entryFormat = $littleEndian ? 'Vcpu/Vsubtype/Voffset/Vsize/Valign' : 'Ncpu/Nsubtype/Noffset/Nsize/Nalign';
        }

        for ($index = 0; $index < $count; $index++) {
            $entry = \unpack($entryFormat, $buffer->sliceAsString(8 + $index * $entrySize, $entrySize));

            if (
                $entry['cpu'] === 0
                || $entry['offset'] < 8 + $count * $entrySize
                || $entry['size'] <= 0
                || $entry['align'] > ($wide ? 63 : 31)
                || ($wide && $entry['reserved'] !== 0)
            ) {
                return false;
            }
        }

        return true;
    }
}
