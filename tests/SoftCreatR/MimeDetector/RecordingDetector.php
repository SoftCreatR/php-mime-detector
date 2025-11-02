<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

final class RecordingDetector implements FileSignatureDetectorInterface
{
    /** @var list<string> */
    public static array $calls = [];

    public function __construct(
        private readonly string $name,
        private readonly ?MimeTypeMatch $match = null,
    ) {
        // ...
    }

    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        self::$calls[] = $this->name;

        return $this->match;
    }
}
