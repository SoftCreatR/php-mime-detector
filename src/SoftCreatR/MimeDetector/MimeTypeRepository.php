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
     */
    public function getExtensionsForMimeType(string $mimeType): array
    {
        $mimeType = \strtolower($mimeType);

        return $this->mimeToExtension[$mimeType] ?? [];
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
            '7z' => ['application/x-7z-compressed'],
            'ac3' => ['audio/vnd.dolby.dd-raw'],
            'ace' => ['application/x-ace-compressed'],
            'aif' => ['audio/aiff'],
            'alias' => ['application/x.apple.alias'],
            'amr' => ['audio/amr'],
            'ani' => ['application/x-navi-animation'],
            'ape' => ['audio/ape'],
            'apk' => ['application/vnd.android.package-archive'],
            'ar' => ['application/x-unix-archive'],
            'arj' => ['application/x-arj'],
            'arrow' => ['application/vnd.apache.arrow.file'],
            'asar' => ['application/x-asar'],
            'au' => ['audio/basic'],
            'avi' => ['video/vnd.avi'],
            'avif' => ['image/avif'],
            'avro' => ['application/avro'],
            'blend' => ['application/x-blender'],
            'bmp' => ['image/bmp'],
            'bpg' => ['image/bpg'],
            'bz2' => ['application/x-bzip2'],
            'cab' => ['application/vnd.ms-cab-compressed'],
            'chm' => ['application/vnd.ms-htmlhelp'],
            'class' => ['application/java-vm'],
            'cpio' => ['application/x-cpio'],
            'cr2' => ['image/x-canon-cr2'],
            'cr3' => ['image/x-canon-cr3'],
            'crx' => ['application/x-google-chrome-extension'],
            'cur' => ['image/x-icon'],
            'dat' => ['application/x-ft-windows-registry-hive'],
            'dcm' => ['application/dicom'],
            'deb' => ['application/x-deb'],
            'dmg' => ['application/x-apple-diskimage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'drc' => ['application/vnd.google.draco'],
            'dsf' => ['audio/x-dsf'],
            'dwg' => ['image/vnd.dwg'],
            'elf' => ['application/x-elf'],
            'eot' => ['application/vnd.ms-fontobject'],
            'eps' => ['application/eps'],
            'epub' => ['application/epub+zip'],
            'exe' => ['application/x-msdownload'],
            'fbx' => ['application/x.autodesk.fbx'],
            'flac' => ['audio/x-flac'],
            'flif' => ['image/flif'],
            'flv' => ['video/x-flv'],
            'g3drem' => ['application/octet-stream'],
            'gif' => ['image/gif'],
            'glb' => ['model/gltf-binary'],
            'gz' => ['application/gzip'],
            'heic' => ['image/heic', 'image/heic-sequence', 'image/heif', 'image/heif-sequence'],
            'html' => ['text/html'],
            'icc' => ['application/vnd.iccprofile'],
            'icns' => ['image/icns'],
            'ico' => ['image/x-icon'],
            'ics' => ['text/calendar'],
            'indd' => ['application/x-indesign'],
            'it' => ['audio/x-it'],
            'j2c' => ['image/j2c'],
            'jar' => ['application/java-archive'],
            'jp2' => ['image/jp2'],
            'jpg' => ['image/jpeg'],
            'jpm' => ['image/jpm'],
            'jpx' => ['image/jpx'],
            'jxl' => ['image/jxl'],
            'jxr' => ['image/vnd.ms-photo'],
            'ktx' => ['image/ktx'],
            'lnk' => ['application/x.ms.shortcut'],
            'luac' => ['application/x-lua-bytecode'],
            'lz' => ['application/x-lzip'],
            'lz4' => ['application/x-lz4'],
            'lzh' => ['application/x-lzh-compressed'],
            'm4a' => ['audio/mp4'],
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
            'nes' => ['application/x-nintendo-nes-rom'],
            'odp' => ['application/vnd.oasis.opendocument.presentation'],
            'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
            'odt' => ['application/vnd.oasis.opendocument.text'],
            'oga' => ['audio/ogg'],
            'ogg' => ['audio/ogg'],
            'ogm' => ['video/ogg'],
            'ogv' => ['video/ogg'],
            'ogx' => ['application/ogg'],
            'opus' => ['audio/opus'],
            'orf' => ['image/x-olympus-orf'],
            'otf' => ['font/otf'],
            'parquet' => ['application/vnd.apache.parquet'],
            'pcap' => ['application/vnd.tcpdump.pcap'],
            'pdf' => ['application/pdf'],
            'pgp' => ['application/pgp-encrypted'],
            'png' => ['image/png'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'ps' => ['application/postscript'],
            'psd' => ['image/vnd.adobe.photoshop'],
            'pst' => ['application/vnd.ms-outlook'],
            'qcp' => ['audio/qcelp'],
            'raf' => ['image/x-fujifilm-raf'],
            'rar' => ['application/x-rar-compressed'],
            'rdf' => ['application/rdf+xml'],
            'reg' => ['application/x-ms-regedit'],
            'rm' => ['application/vnd.rn-realmedia'],
            'rpm' => ['application/x-rpm'],
            'rss' => ['application/rss+xml'],
            'rtf' => ['application/rtf'],
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
            'vtt' => ['text/vtt'],
            'wasm' => ['application/wasm'],
            'wav' => ['audio/vnd.wave'],
            'webm' => ['video/webm'],
            'webp' => ['image/webp'],
            'wmv' => ['video/x-ms-wmv'],
            'woff' => ['font/woff'],
            'woff2' => ['font/woff2'],
            'wv' => ['audio/wavpack'],
            'xcf' => ['image/x-xcf'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xm' => ['audio/x-xm'],
            'xml' => ['application/xml'],
            'xpi' => ['application/x-xpinstall'],
            'xz' => ['application/x-xz'],
            'z' => ['application/x-compress'],
            'zip' => ['application/zip'],
            'zst' => ['application/zstd'],
        ];
    }
}
