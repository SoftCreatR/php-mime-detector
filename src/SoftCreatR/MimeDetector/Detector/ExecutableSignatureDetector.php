<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
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
            return $this->match('exe', 'application/x-msdownload');
        }

        if (
            $buffer->checkForBytes([0x7F, 0x45, 0x4C, 0x46])
        ) {
            return $this->match('elf', 'application/x-elf');
        }

        if (
            $buffer->checkForBytes([0xCF, 0xFA, 0xED, 0xFE])
            || $buffer->checkForBytes([0xFE, 0xED, 0xFA, 0xCF])
        ) {
            return $this->match('macho', 'application/x-mach-binary');
        }

        if ($buffer->checkForBytes([0xCA, 0xFE, 0xBA, 0xBE])) {
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
}
