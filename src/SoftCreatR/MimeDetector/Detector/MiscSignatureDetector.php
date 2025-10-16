<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

use const JSON_ERROR_NONE;

/**
 * Detects file types that do not fit into other dedicated categories.
 */
#[DetectorCategory('misc')]
final class MiscSignatureDetector extends AbstractSignatureDetector
{
    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($buffer->checkForBytes([0x42, 0x4C, 0x45, 0x4E, 0x44, 0x45, 0x52])) {
            return $this->match('blend', 'application/x-blender');
        }

        if ($buffer->checkForBytes([0x53, 0x51, 0x4C, 0x69])) {
            return $this->match('sqlite', 'application/x-sqlite3');
        }

        if ($buffer->checkForBytes([0x67, 0x33, 0x64, 0x72, 0x65, 0x6D])) {
            return $this->match('g3drem', 'application/octet-stream');
        }

        if ($buffer->checkForBytes([0x73, 0x69, 0x6C, 0x68, 0x6F, 0x75, 0x65, 0x74, 0x74, 0x65, 0x30, 0x35])) {
            return $this->match('studio3', 'application/octet-stream');
        }

        if ($buffer->checkForBytes([0x44, 0x52, 0x41, 0x43, 0x4F])) {
            return $this->match('drc', 'application/vnd.google.draco');
        }

        if ($this->isMie($buffer)) {
            return $this->match('mie', 'application/x-mie');
        }

        if ($this->isDwg($buffer)) {
            return $this->match('dwg', 'image/vnd.dwg');
        }

        if ($buffer->checkForBytes([0x41, 0x52, 0x52, 0x4F, 0x57, 0x31, 0x00, 0x00])) {
            return $this->match('arrow', 'application/vnd.apache.arrow.file');
        }

        if ($buffer->checkForBytes([0x4F, 0x62, 0x6A, 0x01])) {
            return $this->match('avro', 'application/avro');
        }

        if ($this->isAsar($buffer)) {
            return $this->match('asar', 'application/x-asar');
        }

        if ($buffer->checkForBytes([0x67, 0x6C, 0x54, 0x46, 0x02, 0x00, 0x00, 0x00])) {
            return $this->match('glb', 'model/gltf-binary');
        }

        if ($buffer->checkString("Kaydara FBX Binary  \x00")) {
            return $this->match('fbx', 'application/x.autodesk.fbx');
        }

        if ($this->isIccProfile($buffer)) {
            return $this->match('icc', 'application/vnd.iccprofile');
        }

        if (
            $buffer->checkForBytes([0xD4, 0xC3, 0xB2, 0xA1])
            || $buffer->checkForBytes([0xA1, 0xB2, 0xC3, 0xD4])
        ) {
            return $this->match('pcap', 'application/vnd.tcpdump.pcap');
        }

        if ($buffer->checkString('regf')) {
            return $this->match('dat', 'application/x-ft-windows-registry-hive');
        }

        if (
            $buffer->checkForBytes(
                [0x62, 0x6F, 0x6F, 0x6B, 0x00, 0x00, 0x00, 0x00, 0x6D, 0x61, 0x72, 0x6B, 0x00, 0x00, 0x00, 0x00]
            )
        ) {
            return $this->match('alias', 'application/x.apple.alias');
        }

        if (
            $buffer->checkForBytes([
                0x4C, 0x00, 0x00, 0x00, 0x01, 0x14, 0x02, 0x00, 0x00, 0x00,
                0x00, 0x00, 0xC0, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x46,
            ])
        ) {
            return $this->match('lnk', 'application/x.ms.shortcut');
        }

        if (
            $buffer->checkForBytes([0x50, 0x41, 0x52, 0x31])
            || $buffer->checkForBytes([0x50, 0x41, 0x52, 0x45])
        ) {
            return $this->match('parquet', 'application/vnd.apache.parquet');
        }

        if ($buffer->checkForBytes([0x27, 0x0A, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00], 2)) {
            return $this->match('shp', 'application/x-esri-shape');
        }

        if ($buffer->checkString('solid ')) {
            return $this->match('stl', 'model/stl');
        }

        if ($buffer->checkString('UNICORN')) {
            return $this->match('unicorn', 'application/unicorn');
        }

        if ($buffer->checkString('BEGIN:VCALENDAR')) {
            return $this->match('ics', 'text/calendar');
        }

        if ($buffer->checkString('BEGIN:VCARD')) {
            return $this->match('vcf', 'text/vcard');
        }

        if ($buffer->checkString('-----BEGIN PGP MESSAGE-----')) {
            return $this->match('pgp', 'application/pgp-encrypted');
        }

        if ($buffer->checkString('WEBVTT')) {
            $next = $buffer->get(6);

            if ($next === null || \in_array($next, [0x0A, 0x0D, 0x09, 0x20, 0x00], true)) {
                return $this->match('vtt', 'text/vtt');
            }
        }

        if ($buffer->checkString('REGEDIT4')) {
            return $this->match('reg', 'application/x-ms-regedit');
        }

        if ($buffer->checkString('Windows Registry Editor Version 5.00')) {
            return $this->match('reg', 'application/x-ms-regedit');
        }

        if (
            $buffer->checkForBytes([0xFF, 0xFE])
            && $this->checkUtf16LeString($buffer, 'Windows Registry Editor Version 5.00', 2)
        ) {
            return $this->match('reg', 'application/x-ms-regedit');
        }

        return null;
    }

    private function isMie(FileBuffer $buffer): bool
    {
        return
            $buffer->checkForBytes([0x30, 0x4D, 0x49, 0x45], 4)
            && ($buffer->checkForBytes([0x7E, 0x10, 0x04]) || $buffer->checkForBytes([0x7E, 0x18, 0x04]));
    }

    private function isDwg(FileBuffer $buffer): bool
    {
        if (!$buffer->checkString('AC')) {
            return false;
        }

        $version = '';

        for ($i = 2; $i < 6; $i++) {
            $byte = $buffer->get($i);
            if ($byte === null) {
                return false;
            }

            $version .= \chr($byte);
        }

        if (!\ctype_digit($version)) {
            return false;
        }

        $numeric = (int)$version;

        return $numeric >= 1000 && $numeric <= 1050;
    }

    private function isAsar(FileBuffer $buffer): bool
    {
        if (!$buffer->checkForBytes([0x04, 0x00, 0x00, 0x00])) {
            return false;
        }

        $jsonLength = $this->readUint32Le($buffer, 12);

        if ($jsonLength === null || $jsonLength <= 12) {
            return false;
        }

        if ($jsonLength + 16 > $buffer->length()) {
            return false;
        }

        $json = $buffer->sliceAsString(16, $jsonLength);

        if ($json === '') {
            return false;
        }

        $decoded = \json_decode($json, true);

        return $decoded !== null && \json_last_error() === JSON_ERROR_NONE && isset($decoded['files']);
    }

    private function isIccProfile(FileBuffer $buffer): bool
    {
        if ($buffer->length() < 128) {
            return false;
        }

        return $buffer->checkForBytes([0x61, 0x63, 0x73, 0x70], 36);
    }

    private function readUint32Le(FileBuffer $buffer, int $offset): ?int
    {
        $value = 0;

        for ($i = 0; $i < 4; $i++) {
            $byte = $buffer->get($offset + $i);

            if ($byte === null) {
                return null;
            }

            $value |= $byte << ($i * 8);
        }

        return $value;
    }

    private function checkUtf16LeString(FileBuffer $buffer, string $value, int $offset): bool
    {
        $bytes = [];
        $length = \strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $bytes[] = \ord($value[$i]);
            $bytes[] = 0x00;
        }

        return $buffer->checkForBytes($bytes, $offset);
    }
}
