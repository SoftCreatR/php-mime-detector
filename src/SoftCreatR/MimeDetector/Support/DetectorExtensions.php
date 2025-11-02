<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Support;

use InvalidArgumentException;
use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;

/**
 * Allows registering runtime detector extensions that join the default pipeline.
 */
trait DetectorExtensions
{
    /**
     * @var array<string, array{factory: callable(): list<FileSignatureDetectorInterface>, priority: int, index: int}>
     */
    private static array $detectorExtensions = [];

    private static int $detectorExtensionIndex = 0;

    /**
     * Register a new detector extension.
     *
     * @param callable|FileSignatureDetectorInterface $detector Callback that resolves to one or more detectors.
     */
    public static function extend(
        string $name,
        callable|FileSignatureDetectorInterface $detector,
        int $priority = 0,
    ): void {
        if ($detector instanceof FileSignatureDetectorInterface) {
            $factory = static fn(): array => [$detector];
        } else {
            $factory = static function () use ($detector, $name): array {
                $resolved = $detector();

                if ($resolved instanceof FileSignatureDetectorInterface) {
                    return [$resolved];
                }

                if (!\is_iterable($resolved)) {
                    throw new InvalidArgumentException(\sprintf(
                        'Detector extension "%s" must return an instance of %s or an iterable of detectors.',
                        $name,
                        FileSignatureDetectorInterface::class,
                    ));
                }

                $detectors = [];

                foreach ($resolved as $candidate) {
                    if (!$candidate instanceof FileSignatureDetectorInterface) {
                        throw new InvalidArgumentException(\sprintf(
                            'Detector extension "%s" must only yield instances of %s.',
                            $name,
                            FileSignatureDetectorInterface::class,
                        ));
                    }

                    $detectors[] = $candidate;
                }

                return $detectors;
            };
        }

        static::$detectorExtensions[$name] = [
            'factory' => $factory,
            'priority' => $priority,
            'index' => static::$detectorExtensionIndex++,
        ];

        static::sortDetectorExtensions();
    }

    /**
     * Determine whether a detector extension with the given name exists.
     */
    public static function hasExtension(string $name): bool
    {
        return isset(static::$detectorExtensions[$name]);
    }

    /**
     * Forget a previously registered detector extension.
     */
    public static function forgetExtension(string $name): void
    {
        unset(static::$detectorExtensions[$name]);
    }

    /**
     * Remove all registered detector extensions.
     */
    public static function flushExtensions(): void
    {
        static::$detectorExtensions = [];
        static::$detectorExtensionIndex = 0;
    }

    /**
     * Resolve the registered detector extensions.
     *
     * @return list<FileSignatureDetectorInterface>
     */
    protected static function resolveDetectorExtensions(): array
    {
        $detectors = [];

        foreach (static::$detectorExtensions as $extension) {
            foreach ($extension['factory']() as $detector) {
                $detectors[] = $detector;
            }
        }

        return $detectors;
    }

    private static function sortDetectorExtensions(): void
    {
        \uasort(
            static::$detectorExtensions,
            static function (array $left, array $right): int {
                if ($left['priority'] === $right['priority']) {
                    return $left['index'] <=> $right['index'];
                }

                return $right['priority'] <=> $left['priority'];
            },
        );
    }
}
