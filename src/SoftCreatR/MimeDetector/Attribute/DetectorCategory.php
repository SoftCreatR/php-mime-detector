<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Attribute;

use Attribute;

/**
 * Attribute to group signature detectors by a logical category.
 *
 * The category metadata can be used by consumers to build reporting or
 * filtering logic around the shipped signature detectors.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DetectorCategory
{
    public function __construct(public readonly string $name)
    {
        // ...
    }
}
