<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

/**
 * Known alternative media type names for the same file format.
 *
 * This is deliberately a small allowlist. A container type such as audio/ogg
 * is not equivalent to a particular codec such as audio/opus.
 */
final class MimeTypeAliases
{
    private const PREFERRED = [
        'audio/x-wav' => 'audio/vnd.wave',
        'audio/wav' => 'audio/vnd.wave',
        'audio/x-flac' => 'audio/flac',
        'audio/x-aiff' => 'audio/aiff',
        'audio/x-m4a' => 'audio/mp4',
        'video/x-msvideo' => 'video/vnd.avi',
        'application/x-rar' => 'application/vnd.rar',
        'application/x-rar-compressed' => 'application/vnd.rar',
        'video/x-ms-asf' => 'application/vnd.ms-asf',
        'text/rtf' => 'application/rtf',
        'text/xml' => 'application/xml',
        'image/x-icon' => 'image/vnd.microsoft.icon',
        'application/x-deb' => 'application/vnd.debian.binary-package',
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
     * @return list<string>
     */
    private static function significantParameters(string $mimeType): array
    {
        $parts = \explode(';', $mimeType);
        \array_shift($parts);
        $parameters = [];

        foreach ($parts as $part) {
            $part = \strtolower(\trim($part));

            if ($part !== '' && !\str_starts_with($part, 'charset=')) {
                $parameters[] = $part;
            }
        }

        \sort($parameters);

        return $parameters;
    }
}
