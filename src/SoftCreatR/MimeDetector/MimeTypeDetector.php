<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Contract\MimeTypeResolverInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\DetectorPipeline;
use SoftCreatR\MimeDetector\Detection\FileBuffer;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;
use SoftCreatR\MimeDetector\Detector\ArchiveSignatureDetector;
use SoftCreatR\MimeDetector\Detector\DocumentSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ExecutableSignatureDetector;
use SoftCreatR\MimeDetector\Detector\FontSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ImageSignatureDetector;
use SoftCreatR\MimeDetector\Detector\MediaSignatureDetector;
use SoftCreatR\MimeDetector\Detector\MiscSignatureDetector;
use SoftCreatR\MimeDetector\Detector\XmlSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ZipSignatureDetector;
use SoftCreatR\MimeDetector\Support\DetectorExtensions;

/**
 * Default implementation of the MIME type resolver backed by signature
 * detectors and a mapping repository.
 */
final class MimeTypeDetector implements MimeTypeResolverInterface
{
    use DetectorExtensions;

    private DetectorPipeline $pipeline;

    private MimeTypeRepository $repository;

    private DetectionContext $context;

    private bool $hasResult = false;

    private ?MimeTypeMatch $match = null;

    /**
     * @throws MimeDetectorException
     */
    public function __construct(
        string $file,
        ?DetectorPipeline $pipeline = null,
        ?MimeTypeRepository $repository = null,
    ) {
        $this->repository = $repository ?? MimeTypeRepository::createDefault();

        $handler = new ByteCacheHandler($file);
        $buffer = new FileBuffer($handler);
        $this->context = new DetectionContext($file, $buffer);
        $this->pipeline = $pipeline ?? DetectorPipeline::create(...self::defaultDetectors());
    }

    public function detectFile(): ?MimeTypeMatch
    {
        return $this->detectMatch();
    }

    /**
     * @return array{ext: string, mime: string}
     */
    public function getFileType(): array
    {
        $match = $this->detectMatch();

        return $match?->toArray() ?? [];
    }

    public function getFileExtension(): string
    {
        $match = $this->detectMatch();

        return $match?->extension() ?? '';
    }

    public function getMimeType(): string
    {
        $match = $this->detectMatch();

        return $match?->mimeType() ?? '';
    }

    public function getExtensionForMimeType(string $mimeType): string
    {
        return $this->repository->getExtensionForMimeType($mimeType);
    }

    /**
     * @return list<string>
     */
    public function getMimeTypesForExtension(string $extension): array
    {
        return $this->repository->getMimeTypesForExtension($extension);
    }

    /**
     * @return array<string, list<string>>
     */
    public function listAllMimeTypes(): array
    {
        return $this->repository->all();
    }

    /**
     * @return list<FileSignatureDetectorInterface>
     */
    private static function defaultDetectors(): array
    {
        return [
            ...self::resolveDetectorExtensions(),
            new ImageSignatureDetector(),
            new ZipSignatureDetector(),
            new ArchiveSignatureDetector(),
            new MediaSignatureDetector(),
            new DocumentSignatureDetector(),
            new FontSignatureDetector(),
            new ExecutableSignatureDetector(),
            new MiscSignatureDetector(),
            new XmlSignatureDetector(),
        ];
    }

    /**
     * Run the pipeline once and cache the resulting match for future calls.
     */
    private function detectMatch(): ?MimeTypeMatch
    {
        if (!$this->hasResult) {
            $this->match = $this->pipeline->detect($this->context);
            $this->hasResult = true;
        }

        return $this->match;
    }
}
