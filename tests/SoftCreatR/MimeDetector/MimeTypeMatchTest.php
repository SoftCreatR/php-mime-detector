<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

class MimeTypeMatchTest extends TestCase
{
    public function testProvidesAccessorsAndArrayRepresentation(): void
    {
        $match = new MimeTypeMatch('png', 'image/png');

        $this->assertSame('png', $match->extension());
        $this->assertSame('image/png', $match->mimeType());
        $this->assertSame([
            'ext' => 'png',
            'mime' => 'image/png',
        ], $match->toArray());
    }
}
