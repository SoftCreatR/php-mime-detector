<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

/**
 * Bidirectional map of file extensions and MIME types.
 */
final class MimeTypeRepository
{
    /** @var array<string, list<string>> */
    private array $extensionToMime = [];

    /** @var array<string, list<string>> */
    private array $mimeToExtension = [];

    /**
     * @param array<string, list<string>> $map
     */
    public function __construct(array $map = [])
    {
        foreach ($map as $extension => $mimeTypes) {
            $this->register($extension, ...$mimeTypes);
        }
    }

    /**
     * Build a repository with the bundled default mapping.
     */
    public static function createDefault(): self
    {
        return new self(self::defaultMap());
    }

    /**
     * Register a relationship between an extension and one or more MIME types.
     */
    public function register(string $extension, string ...$mimeTypes): void
    {
        $extension = \strtolower($extension);
        $mimeTypes = \array_map('strtolower', $mimeTypes);

        if (!isset($this->extensionToMime[$extension])) {
            $this->extensionToMime[$extension] = [];
        }

        foreach ($mimeTypes as $mimeType) {
            if (!isset($this->mimeToExtension[$mimeType])) {
                $this->mimeToExtension[$mimeType] = [];
            }

            if (!\in_array($mimeType, $this->extensionToMime[$extension], true)) {
                $this->extensionToMime[$extension][] = $mimeType;
                \sort($this->extensionToMime[$extension]);
            }

            if (!\in_array($extension, $this->mimeToExtension[$mimeType], true)) {
                $this->mimeToExtension[$mimeType][] = $extension;
                \sort($this->mimeToExtension[$mimeType]);
            }
        }
    }

    /**
     * @return list<string>
     */
    public function getMimeTypesForExtension(string $extension): array
    {
        $extension = \strtolower($extension);

        return $this->extensionToMime[$extension] ?? [];
    }

    public function getExtensionForMimeType(string $mimeType): string
    {
        $extensions = $this->getExtensionsForMimeType($mimeType);

        return $extensions[0] ?? '';
    }

    /**
     * @return list<string>
     * @SuppressWarnings(PHPMD.StaticAccess) The alias utility has no state.
     */
    public function getExtensionsForMimeType(string $mimeType): array
    {
        $mimeType = \strtolower($mimeType);

        return $this->mimeToExtension[$mimeType]
            ?? $this->mimeToExtension[MimeTypeAliases::preferred($mimeType)]
            ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->mimeToExtension;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function defaultMap(): array
    {
        return [
            '3g2' => ['video/3gpp2'],
            '3gp' => ['video/3gpp'],
            '3mf' => ['model/3mf'],
            '7z' => ['application/x-7z-compressed'],
            'ac3' => ['audio/vnd.dolby.dd-raw'],
            'ace' => ['application/x-ace-compressed'],
            'aif' => ['audio/aiff', 'audio/x-aiff'],
            'alias' => ['application/x.apple.alias'],
            'amr' => ['audio/amr'],
            'ani' => ['application/x-navi-animation'],
            'ape' => ['audio/ape'],
            'apng' => ['image/apng'],
            'apk' => ['application/vnd.android.package-archive'],
            'ar' => ['application/x-unix-archive'],
            'arj' => ['application/x-arj'],
            'arrow' => ['application/vnd.apache.arrow.file'],
            'arw' => ['image/x-sony-arw'],
            'asar' => ['application/x-asar'],
            'asf' => ['video/x-ms-asf', 'application/vnd.ms-asf'],
            'au' => ['audio/basic'],
            'avi' => ['video/vnd.avi', 'video/x-msvideo'],
            'avif' => ['image/avif'],
            'avro' => ['application/avro'],
            'blend' => ['application/x-blender'],
            'bmp' => ['image/bmp'],
            'bpg' => ['image/bpg'],
            'bz2' => ['application/x-bzip2'],
            'cab' => ['application/vnd.ms-cab-compressed'],
            'cfb' => ['application/x-cfb'],
            'chm' => ['application/vnd.ms-htmlhelp'],
            'class' => ['application/java-vm'],
            'cpio' => ['application/x-cpio'],
            'cr2' => ['image/x-canon-cr2'],
            'cr3' => ['image/x-canon-cr3'],
            'crx' => ['application/x-google-chrome-extension'],
            'cur' => ['image/x-icon'],
            'dat' => ['application/x-ft-windows-registry-hive'],
            'dcm' => ['application/dicom'],
            'deb' => ['application/x-deb', 'application/vnd.debian.binary-package'],
            'dmg' => ['application/x-apple-diskimage'],
            'dng' => ['image/x-adobe-dng'],
            'docm' => ['application/vnd.ms-word.document.macroenabled.12'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'dotm' => ['application/vnd.ms-word.template.macroenabled.12'],
            'dotx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.template'],
            'drc' => ['application/vnd.google.draco'],
            'dsf' => ['audio/x-dsf'],
            'dwg' => ['image/vnd.dwg'],
            'elf' => ['application/x-elf'],
            'eot' => ['application/vnd.ms-fontobject'],
            'eps' => ['application/eps'],
            'epub' => ['application/epub+zip'],
            'exe' => ['application/x-msdownload'],
            'f4a' => ['audio/mp4'],
            'f4b' => ['audio/mp4'],
            'f4p' => ['video/mp4'],
            'f4v' => ['video/mp4'],
            'fbx' => ['application/x.autodesk.fbx'],
            'flac' => ['audio/x-flac', 'audio/flac'],
            'flif' => ['image/flif'],
            'flv' => ['video/x-flv'],
            'g3drem' => ['application/octet-stream'],
            'gif' => ['image/gif'],
            'glb' => ['model/gltf-binary'],
            'gpx' => ['application/gpx+xml'],
            'gz' => ['application/gzip'],
            'heic' => ['image/heic', 'image/heic-sequence', 'image/heif', 'image/heif-sequence'],
            'html' => ['text/html'],
            'icc' => ['application/vnd.iccprofile'],
            'icns' => ['image/icns'],
            'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
            'ics' => ['text/calendar'],
            'indd' => ['application/x-indesign'],
            'iso' => ['application/x-iso9660-image'],
            'it' => ['audio/x-it'],
            'j2c' => ['image/j2c'],
            'jar' => ['application/java-archive'],
            'jp2' => ['image/jp2'],
            'jpg' => ['image/jpeg'],
            'jpm' => ['image/jpm'],
            'jpx' => ['image/jpx'],
            'jxl' => ['image/jxl'],
            'jxr' => ['image/vnd.ms-photo'],
            'key' => ['application/vnd.apple.keynote'],
            'kml' => ['application/vnd.google-earth.kml+xml'],
            'ktx' => ['image/ktx'],
            'lnk' => ['application/x.ms.shortcut'],
            'luac' => ['application/x-lua-bytecode'],
            'lz' => ['application/x-lzip'],
            'lz4' => ['application/x-lz4'],
            'lzh' => ['application/x-lzh-compressed'],
            'm4a' => ['audio/mp4', 'audio/x-m4a'],
            'm4b' => ['audio/mp4'],
            'm4p' => ['video/mp4'],
            'm4v' => ['video/x-m4v'],
            'macho' => ['application/x-mach-binary'],
            'mid' => ['audio/midi'],
            'mie' => ['application/x-mie'],
            'mj2' => ['image/mj2'],
            'mkv' => ['video/x-matroska'],
            'mobi' => ['application/x-mobipocket-ebook'],
            'mov' => ['video/quicktime'],
            'mp2' => ['audio/mpeg'],
            'mp3' => ['audio/mpeg'],
            'mp4' => ['audio/mpeg', 'video/mp4'],
            'mpc' => ['audio/x-musepack'],
            'mpg' => ['video/mpeg'],
            'msi' => ['application/x-msi'],
            'mts' => ['video/mp2t'],
            'mxf' => ['application/mxf'],
            'nef' => ['image/x-nikon-nef'],
            'nes' => ['application/x-nintendo-nes-rom'],
            'numbers' => ['application/vnd.apple.numbers'],
            'odg' => ['application/vnd.oasis.opendocument.graphics'],
            'odp' => ['application/vnd.oasis.opendocument.presentation'],
            'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
            'odt' => ['application/vnd.oasis.opendocument.text'],
            'oga' => ['audio/ogg'],
            'ogg' => ['audio/ogg'],
            'ogm' => ['video/ogg'],
            'ogv' => ['video/ogg'],
            'ogx' => ['application/ogg'],
            'opus' => ['audio/opus', 'audio/ogg'],
            'orf' => ['image/x-olympus-orf'],
            'otf' => ['font/otf'],
            'otg' => ['application/vnd.oasis.opendocument.graphics-template'],
            'otp' => ['application/vnd.oasis.opendocument.presentation-template'],
            'ots' => ['application/vnd.oasis.opendocument.spreadsheet-template'],
            'ott' => ['application/vnd.oasis.opendocument.text-template'],
            'pages' => ['application/vnd.apple.pages'],
            'parquet' => ['application/vnd.apache.parquet'],
            'pcap' => ['application/vnd.tcpdump.pcap'],
            'pdf' => ['application/pdf'],
            'pgp' => ['application/pgp-encrypted'],
            'png' => ['image/png'],
            'potm' => ['application/vnd.ms-powerpoint.template.macroenabled.12'],
            'potx' => ['application/vnd.openxmlformats-officedocument.presentationml.template'],
            'ppsm' => ['application/vnd.ms-powerpoint.slideshow.macroenabled.12'],
            'ppsx' => ['application/vnd.openxmlformats-officedocument.presentationml.slideshow'],
            'pptm' => ['application/vnd.ms-powerpoint.presentation.macroenabled.12'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'ps' => ['application/postscript'],
            'psd' => ['image/vnd.adobe.photoshop'],
            'pst' => ['application/vnd.ms-outlook'],
            'qcp' => ['audio/qcelp'],
            'raf' => ['image/x-fujifilm-raf'],
            'rar' => ['application/x-rar-compressed', 'application/x-rar', 'application/vnd.rar'],
            'rdf' => ['application/rdf+xml'],
            'reg' => ['application/x-ms-regedit'],
            'rm' => ['application/vnd.rn-realmedia'],
            'rpm' => ['application/x-rpm'],
            'rss' => ['application/rss+xml'],
            'rtf' => ['application/rtf', 'text/rtf'],
            'rw2' => ['image/x-panasonic-rw2'],
            's3m' => ['audio/x-s3m'],
            'shp' => ['application/x-esri-shape'],
            'spx' => ['audio/ogg'],
            'sqlite' => ['application/x-sqlite3'],
            'stl' => ['model/stl'],
            'studio3' => ['application/octet-stream'],
            'svg' => ['image/svg+xml'],
            'swf' => ['application/x-shockwave-flash'],
            'tar' => ['application/x-tar'],
            'tif' => ['image/tiff'],
            'ttc' => ['font/collection'],
            'ttf' => ['font/ttf'],
            'unicorn' => ['application/unicorn'],
            'vcf' => ['text/vcard'],
            'voc' => ['audio/x-voc'],
            'vsd' => ['application/vnd.visio'],
            'vsdx' => ['application/vnd.visio'],
            'vstx' => ['application/vnd.ms-visio.template.main+xml'],
            'vtt' => ['text/vtt'],
            'wasm' => ['application/wasm'],
            'wav' => ['audio/vnd.wave', 'audio/x-wav', 'audio/wav'],
            'webm' => ['video/webm'],
            'webp' => ['image/webp'],
            'wma' => ['audio/x-ms-wma'],
            'wmv' => ['video/x-ms-wmv'],
            'woff' => ['font/woff'],
            'woff2' => ['font/woff2'],
            'wv' => ['audio/wavpack'],
            'xcf' => ['image/x-xcf'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsm' => ['application/vnd.ms-excel.sheet.macroenabled.12'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xltm' => ['application/vnd.ms-excel.template.macroenabled.12'],
            'xltx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.template'],
            'xm' => ['audio/x-xm'],
            'xml' => ['application/xml', 'text/xml'],
            'xpi' => ['application/x-xpinstall'],
            'xz' => ['application/x-xz'],
            'z' => ['application/x-compress'],
            'zip' => ['application/zip'],
            'zst' => ['application/zstd'],
        ];
    }
}
