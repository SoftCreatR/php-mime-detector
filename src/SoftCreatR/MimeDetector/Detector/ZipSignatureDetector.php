<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use ZipArchive;

/**
 * Detects ZIP based formats, including OOXML documents and APKs.
 */
#[DetectorCategory('archive')]
final class ZipSignatureDetector extends AbstractSignatureDetector
{
    /**
     * Allow tests to override ZipArchive availability for deterministic coverage.
     */
    private static ?bool $zipArchiveAvailable = null;

    /**
     * Mapping of content-type markers found in Open Packaging documents.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const CONTENT_TYPE_MAP = [
        'application/epub+zip' => [
            'epub',
            'application/epub+zip',
        ],
        'application/vnd.oasis.opendocument.text-template' => [
            'ott',
            'application/vnd.oasis.opendocument.text-template',
        ],
        'application/vnd.oasis.opendocument.text' => [
            'odt',
            'application/vnd.oasis.opendocument.text',
        ],
        'application/vnd.oasis.opendocument.spreadsheet-template' => [
            'ots',
            'application/vnd.oasis.opendocument.spreadsheet-template',
        ],
        'application/vnd.oasis.opendocument.spreadsheet' => [
            'ods',
            'application/vnd.oasis.opendocument.spreadsheet',
        ],
        'application/vnd.oasis.opendocument.presentation-template' => [
            'otp',
            'application/vnd.oasis.opendocument.presentation-template',
        ],
        'application/vnd.oasis.opendocument.presentation' => [
            'odp',
            'application/vnd.oasis.opendocument.presentation',
        ],
        'application/vnd.oasis.opendocument.graphics-template' => [
            'otg',
            'application/vnd.oasis.opendocument.graphics-template',
        ],
        'application/vnd.oasis.opendocument.graphics' => [
            'odg',
            'application/vnd.oasis.opendocument.graphics',
        ],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'application/vnd.ms-word.document.macroenabled.12' => [
            'docm',
            'application/vnd.ms-word.document.macroenabled.12',
        ],
        'application/vnd.ms-word.document.macroenabled' => [
            'docm',
            'application/vnd.ms-word.document.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => [
            'dotx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        ],
        'application/vnd.ms-word.template.macroenabled.12' => [
            'dotm',
            'application/vnd.ms-word.template.macroenabled.12',
        ],
        'application/vnd.ms-word.template.macroenabledtemplate' => [
            'dotm',
            'application/vnd.ms-word.template.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
            'pptx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
        'application/vnd.ms-powerpoint.presentation.macroenabled.12' => [
            'pptm',
            'application/vnd.ms-powerpoint.presentation.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.presentationml.template' => [
            'potx',
            'application/vnd.openxmlformats-officedocument.presentationml.template',
        ],
        'application/vnd.ms-powerpoint.template.macroenabled.12' => [
            'potm',
            'application/vnd.ms-powerpoint.template.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => [
            'ppsx',
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        ],
        'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => [
            'ppsm',
            'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
            'xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'application/vnd.ms-excel.sheet.macroenabled.12' => [
            'xlsm',
            'application/vnd.ms-excel.sheet.macroenabled.12',
        ],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => [
            'xltx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        ],
        'application/vnd.ms-excel.template.macroenabled.12' => [
            'xltm',
            'application/vnd.ms-excel.template.macroenabled.12',
        ],
        'application/vnd.ms-package.3dmanufacturing-3dmodel+xml' => [
            '3mf',
            'model/3mf',
        ],
        'application/vnd.ms-visio.drawing' => [
            'vsdx',
            'application/vnd.visio',
        ],
        'application/vnd.ms-visio.drawing.main+xml' => [
            'vsdx',
            'application/vnd.visio',
        ],
        'application/vnd.ms-visio.template.main+xml' => [
            'vstx',
            'application/vnd.ms-visio.template.main+xml',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if (!$buffer->checkForBytes([0x50, 0x4B, 0x03, 0x04])) {
            return null;
        }

        $archiveMatch = $this->detectWithZipArchive($context);
        if ($archiveMatch instanceof MimeTypeMatch) {
            return $archiveMatch;
        }

        $rawContent = $buffer->sliceAsString();
        $lowerContent = \strtolower($rawContent);

        foreach (self::CONTENT_TYPE_MAP as $needle => [$extension, $mime]) {
            if (\str_contains($lowerContent, $needle)) {
                return $this->match($extension, $mime);
            }
        }

        if (\str_contains($lowerContent, 'meta-inf/mozilla.rsa')) {
            return $this->match('xpi', 'application/x-xpinstall');
        }

        if (\str_contains($lowerContent, 'classes.dex')) {
            return $this->match('apk', 'application/vnd.android.package-archive');
        }

        if (\str_contains($lowerContent, 'meta-inf/manifest.mf')) {
            return $this->match('jar', 'application/java-archive');
        }

        return $this->match('zip', 'application/zip');
    }

    private function detectWithZipArchive(DetectionContext $context): ?MimeTypeMatch
    {
        if (!self::zipArchiveAvailable()) {
            return null;
        }

        $zip = new ZipArchive();

        if ($zip->open($context->file()) !== true) {
            return null;
        }

        try {
            $mimetype = $zip->getFromName('mimetype');

            if (\is_string($mimetype)) {
                $mime = \strtolower(\trim($mimetype));

                if (isset(self::CONTENT_TYPE_MAP[$mime])) {
                    [$extension, $mappedMime] = self::CONTENT_TYPE_MAP[$mime];

                    return $this->match($extension, $mappedMime);
                }
            }

            $contentTypes = $zip->getFromName('[Content_Types].xml');

            if (\is_string($contentTypes)) {
                $lowerContent = \strtolower($contentTypes);

                foreach (self::CONTENT_TYPE_MAP as $needle => [$extension, $mappedMime]) {
                    if (\str_contains($lowerContent, $needle)) {
                        return $this->match($extension, $mappedMime);
                    }
                }
            }

            if ($zip->locateName('classes.dex', ZipArchive::FL_NOCASE) !== false) {
                return $this->match('apk', 'application/vnd.android.package-archive');
            }

            if ($zip->locateName('META-INF/mozilla.rsa', ZipArchive::FL_NOCASE) !== false) {
                return $this->match('xpi', 'application/x-xpinstall');
            }

            if ($zip->locateName('META-INF/MANIFEST.MF', ZipArchive::FL_NOCASE) !== false) {
                return $this->match('jar', 'application/java-archive');
            }
        } finally {
            $zip->close();
        }

        return null;
    }

    private static function zipArchiveAvailable(): bool
    {
        if (self::$zipArchiveAvailable !== null) {
            return self::$zipArchiveAvailable;
        }

        return \class_exists(ZipArchive::class);
    }
}
