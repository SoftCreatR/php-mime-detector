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
use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\DetectorPipeline;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

class DetectorPipelineTest extends TestCase
{
    public function testDetectReturnsFirstSuccessfulMatch(): void
    {
        [$context, $file] = $this->createContext();
        $match = new MimeTypeMatch('bin', 'application/octet-stream');

        $failingDetector = new class implements FileSignatureDetectorInterface {
            public function detect(DetectionContext $context): ?MimeTypeMatch
            {
                return null;
            }
        };

        $successfulDetector = new class ($match) implements FileSignatureDetectorInterface {
            public function __construct(private readonly MimeTypeMatch $match)
            {
                // ...
            }

            public function detect(DetectionContext $context): ?MimeTypeMatch
            {
                return $this->match;
            }
        };

        try {
            $pipeline = DetectorPipeline::create($failingDetector, $successfulDetector);

            $this->assertSame($match, $pipeline->detect($context));
        } finally {
            \unlink($file);
        }
    }

    public function testDetectReturnsRememberedMatchWithoutInvokingDetectorsAgain(): void
    {
        [$context, $file] = $this->createContext();

        $match = new MimeTypeMatch('bin', 'application/octet-stream');
        $tracker = new class {
            public int $calls = 0;
        };

        $detector = new class ($tracker, $match) implements FileSignatureDetectorInterface {
            public function __construct(private readonly object $tracker, private readonly MimeTypeMatch $match)
            {
                // ...
            }

            public function detect(DetectionContext $context): ?MimeTypeMatch
            {
                $this->tracker->calls++;

                return $this->match;
            }
        };

        try {
            $pipeline = DetectorPipeline::create($detector);

            $this->assertSame($match, $pipeline->detect($context));
            $this->assertSame($match, $pipeline->detect($context));
            $this->assertSame(1, $tracker->calls);
        } finally {
            \unlink($file);
        }
    }

    public function testDetectReturnsNullWhenNoDetectorsAreConfigured(): void
    {
        [$context, $file] = $this->createContext();

        try {
            $pipeline = new DetectorPipeline([]);

            $this->assertNull($pipeline->detect($context));
        } finally {
            \unlink($file);
        }
    }

    /**
     * @return array{DetectionContext, string}
     */
    private function createContext(): array
    {
        $file = \tempnam(\sys_get_temp_dir(), 'mime-pipeline-');
        \file_put_contents($file, 'pipeline');
        $buffer = new FileBuffer(new ByteCacheHandler($file));

        return [new DetectionContext($file, $buffer), $file];
    }
}
