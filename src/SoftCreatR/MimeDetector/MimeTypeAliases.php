<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE.md  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

/**
 * Known alternative media type names for the same file format.
 *
 * Aliases are audited against upstream format definitions (see
 * docs/mime-compatibility.md). A container type such as audio/ogg
 * is not equivalent to a particular codec such as audio/opus.
 */
final class MimeTypeAliases
{
    /**
     * @var array<string, string>
     */
    private const PREFERRED = [
        'application/acrobat' => 'application/pdf',
        'application/bzip2' => 'application/x-bzip2',
        'application/font-sfnt' => 'font/sfnt',
        'application/font-woff' => 'font/woff',
        'application/gpx' => 'application/gpx+xml',
        'application/ico' => 'image/vnd.microsoft.icon',
        'application/ics' => 'text/calendar',
        'application/java-byte-code' => 'application/java-vm',
        'application/msexcel' => 'application/vnd.ms-excel',
        'application/nappdf' => 'application/pdf',
        'application/pcap' => 'application/vnd.tcpdump.pcap',
        'application/photoshop' => 'image/vnd.adobe.photoshop',
        'application/vnd.adobe.flash.movie' => 'application/x-shockwave-flash',
        'application/vnd.ms-3mfdocument' => 'model/3mf',
        'application/x-archive' => 'application/x-unix-archive',
        'application/x-bzip' => 'application/x-bzip2',
        'application/x-chm' => 'application/vnd.ms-htmlhelp',
        'application/x-chrome-extension' => 'application/x-google-chrome-extension',
        'application/x-deb' => 'application/vnd.debian.binary-package',
        'application/x-debian-package' => 'application/vnd.debian.binary-package',
        'application/x-eps' => 'application/eps',
        'application/x-flash-video' => 'video/x-flv',
        'application/x-font-otf' => 'font/otf',
        'application/x-font-ttf' => 'font/ttf',
        'application/x-gpx' => 'application/gpx+xml',
        'application/x-gpx+xml' => 'application/gpx+xml',
        'application/x-gtar' => 'application/x-tar',
        'application/x-gzip' => 'application/gzip',
        'application/x-jar' => 'application/java-archive',
        'application/x-java' => 'application/java-vm',
        'application/x-java-applet' => 'application/java-vm',
        'application/x-java-archive' => 'application/java-archive',
        'application/x-java-class' => 'application/java-vm',
        'application/x-java-vm' => 'application/java-vm',
        'application/x-lha' => 'application/x-lzh-compressed',
        'application/x-ms-shortcut' => 'application/x.ms.shortcut',
        'application/x-msexcel' => 'application/vnd.ms-excel',
        'application/x-nes-rom' => 'application/x-nintendo-nes-rom',
        'application/x-ogg' => 'application/ogg',
        'application/x-pcap' => 'application/vnd.tcpdump.pcap',
        'application/x-pdf' => 'application/pdf',
        'application/x-photoshop' => 'image/vnd.adobe.photoshop',
        'application/x-rar' => 'application/vnd.rar',
        'application/x-rar-compressed' => 'application/vnd.rar',
        'application/x-redhat-package-manager' => 'application/x-rpm',
        'application/x-sqlite3' => 'application/vnd.sqlite3',
        'application/x-ustar' => 'application/x-tar',
        'application/x-zip' => 'application/zip',
        'application/x-zip-compressed' => 'application/zip',
        'audio/dsf' => 'audio/x-dsf',
        'audio/m4a' => 'audio/mp4',
        'audio/mid' => 'audio/midi',
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/mpg' => 'audio/mpeg',
        'audio/mpg3' => 'audio/mpeg',
        'audio/wav' => 'audio/vnd.wave',
        'audio/wma' => 'audio/x-ms-wma',
        'audio/x-aac' => 'audio/aac',
        'audio/x-aiff' => 'audio/aiff',
        'audio/x-amr' => 'audio/amr',
        'audio/x-ape' => 'audio/ape',
        'audio/x-flac' => 'audio/flac',
        'audio/x-m4a' => 'audio/mp4',
        'audio/x-mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        'audio/x-ogg' => 'audio/ogg',
        'audio/x-wav' => 'audio/vnd.wave',
        'flv-application/octet-stream' => 'video/x-flv',
        'image/ico' => 'image/vnd.microsoft.icon',
        'image/icon' => 'image/vnd.microsoft.icon',
        'image/jpeg2000' => 'image/jp2',
        'image/jpeg2000-image' => 'image/jp2',
        'image/jpg' => 'image/jpeg',
        'image/pdf' => 'application/pdf',
        'image/photoshop' => 'image/vnd.adobe.photoshop',
        'image/pjpeg' => 'image/jpeg',
        'image/psd' => 'image/vnd.adobe.photoshop',
        'image/vnd.mozilla.apng' => 'image/apng',
        'image/vnd.ms-photo' => 'image/jxr',
        'image/x-bitmap' => 'image/bmp',
        'image/x-bmp' => 'image/bmp',
        'image/x-eps' => 'application/eps',
        'image/x-dds' => 'image/vnd-ms.dds',
        'image/x-fuji-raf' => 'image/x-fujifilm-raf',
        'image/x-icns' => 'image/icns',
        'image/x-ico' => 'image/vnd.microsoft.icon',
        'image/x-icon' => 'image/vnd.microsoft.icon',
        'image/x-jp2-codestream' => 'image/j2c',
        'image/x-jpeg2000-image' => 'image/jp2',
        'image/x-ms-bitmap' => 'image/bmp',
        'image/x-ms-bmp' => 'image/bmp',
        'image/x-panasonic-raw2' => 'image/x-panasonic-rw2',
        'image/x-photoshop' => 'image/vnd.adobe.photoshop',
        'image/x-png' => 'image/png',
        'image/x-portable-greymap' => 'image/x-portable-graymap',
        'image/x-psd' => 'image/vnd.adobe.photoshop',
        'image/x-windows-bmp' => 'image/bmp',
        'text/ico' => 'image/vnd.microsoft.icon',
        'text/rdf' => 'application/rdf+xml',
        'text/rss' => 'application/rss+xml',
        'text/rtf' => 'application/rtf',
        'text/x-ms-regedit' => 'application/x-ms-regedit',
        'text/x-vcalendar' => 'text/calendar',
        'text/x-vcard' => 'text/vcard',
        'text/xml' => 'application/xml',
        'video/3gp' => 'video/3gpp',
        'video/avi' => 'video/vnd.avi',
        'video/flv' => 'video/x-flv',
        'video/mj2' => 'image/mj2',
        'video/mpeg-system' => 'video/mpeg',
        'video/msvideo' => 'video/vnd.avi',
        'video/x-avi' => 'video/vnd.avi',
        'video/x-m4v' => 'video/mp4',
        'video/x-matroska' => 'video/matroska',
        'video/x-mpeg' => 'video/mpeg',
        'video/x-mpeg-system' => 'video/mpeg',
        'video/x-mpeg2' => 'video/mpeg',
        'video/x-ms-asf' => 'application/vnd.ms-asf',
        'video/x-ms-asf-plugin' => 'application/vnd.ms-asf',
        'video/x-ms-wm' => 'application/vnd.ms-asf',
        'video/x-msvideo' => 'video/vnd.avi',
        'video/x-ogg' => 'video/ogg',
        'video/x-quicktime' => 'video/quicktime',
        'zz-application/zz-winassoc-cab' => 'application/vnd.ms-cab-compressed',
        'zz-application/zz-winassoc-xls' => 'application/vnd.ms-excel',
    ];

    /**
     * Return the preferred spelling for a known alias, or the normalized input.
     * MIME parameters do not form part of the media type name.
     */
    public static function preferred(string $mimeType): string
    {
        $name = \strtolower(\trim(\explode(';', $mimeType, 2)[0]));

        return self::PREFERRED[$name] ?? $name;
    }

    public static function equivalent(string $first, string $second): bool
    {
        $firstParameters = self::significantParameters($first);
        $secondParameters = self::significantParameters($second);
        $first = self::preferred($first);

        return $first !== ''
            && $first === self::preferred($second)
            && $firstParameters === $secondParameters;
    }

    /**
     * Charset does not change the file format. Other parameters, especially
     * codecs, must match so distinct media streams are never conflated.
     *
     * @return list<array{string, string|null}>
     */
    private static function significantParameters(string $mimeType): array
    {
        // Split only at separators outside quoted strings. Parameter names
        // are case insensitive, but values such as codec identifiers are not.
        \preg_match_all('/(?:[^;"\\\\]|\\\\.|"(?:[^"\\\\]|\\\\.)*")+/', $mimeType, $matches);
        $parts = $matches[0];
        \array_shift($parts);
        $parameters = [];

        foreach ($parts as $part) {
            $pair = \explode('=', \trim($part), 2);
            $name = \strtolower(\trim($pair[0]));

            if ($name === '' || $name === 'charset') {
                continue;
            }

            $value = isset($pair[1]) ? \trim($pair[1]) : null;

            if ($value !== null && \strlen($value) >= 2 && $value[0] === '"' && \str_ends_with($value, '"')) {
                $value = \preg_replace('/\\\\(.)/s', '$1', \substr($value, 1, -1));
            }

            $parameters[] = [$name, $value];
        }

        \sort($parameters);

        return $parameters;
    }
}
