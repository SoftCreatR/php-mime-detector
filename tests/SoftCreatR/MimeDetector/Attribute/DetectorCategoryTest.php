<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector\Attribute;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\Attribute\DetectorCategory;

/**
 * @covers \SoftCreatR\MimeDetector\Attribute\DetectorCategory
 */
final class DetectorCategoryTest extends TestCase
{
    public function testExposesConfiguredCategoryName(): void
    {
        $attribute = new DetectorCategory('media');

        $this->assertSame('media', $attribute->name);
    }
}
