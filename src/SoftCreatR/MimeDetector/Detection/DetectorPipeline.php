<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Detection;

use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;

/**
 * Coordinates the sequence of signature detectors that should analyse a file.
 */
final class DetectorPipeline
{
    /** @var list<FileSignatureDetectorInterface> */
    private array $detectors;

    /**
     * @param iterable<FileSignatureDetectorInterface> $detectors
     */
    public function __construct(iterable $detectors)
    {
        $this->detectors = [];

        foreach ($detectors as $detector) {
            $this->detectors[] = $detector;
        }
    }

    /**
     * Create a pipeline from the provided detectors.
     */
    public static function create(FileSignatureDetectorInterface ...$detectors): self
    {
        return new self($detectors);
    }

    /**
     * Iterate through the configured detectors until one produces a match.
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        if ($context->remembered() !== null) {
            return $context->remembered();
        }

        foreach ($this->detectors as $detector) {
            $match = $detector->detect($context);

            if ($match !== null) {
                $context->remember($match);

                return $match;
            }
        }

        return null;
    }
}
