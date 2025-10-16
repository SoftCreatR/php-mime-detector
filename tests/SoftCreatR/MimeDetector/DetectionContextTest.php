<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\Tests\MimeDetector;

use PHPUnit\Framework\TestCase;
use SoftCreatR\MimeDetector\ByteCacheHandler;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

class DetectionContextTest extends TestCase
{
    public function testStoresFileAndBuffer(): void
    {
        [$buffer, $file] = $this->createBuffer();

        try {
            $context = new DetectionContext($file, $buffer);

            $this->assertSame($file, $context->file());
            $this->assertSame($buffer, $context->buffer());
        } finally {
            \unlink($file);
        }
    }

    public function testRememberPersistsMatch(): void
    {
        [$buffer, $file] = $this->createBuffer();

        try {
            $context = new DetectionContext($file, $buffer);
            $match = new MimeTypeMatch('bin', 'application/octet-stream');

            $this->assertNull($context->remembered());

            $context->remember($match);

            $this->assertSame($match, $context->remembered());
        } finally {
            \unlink($file);
        }
    }

    /**
     * @return array{FileBuffer, string}
     */
    private function createBuffer(): array
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-context-');
        \file_put_contents($file, 'context');

        return [new FileBuffer(new ByteCacheHandler($file)), $file];
    }
}
