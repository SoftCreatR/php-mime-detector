<?php

/**
 * Mime Detector for PHP.
 *
 * @license https://github.com/SoftCreatR/php-mime-detector/blob/main/LICENSE  ISC License
 */

declare(strict_types=1);

namespace SoftCreatR\MimeDetector;

class MimeDetector
{
    private MimeTypeDetector $mimeTypeDetector;

    private FileHandler $fileHandler;

    /**
     * @throws MimeDetectorException
     */
    public function __construct(string $filePath)
    {
        $this->fileHandler = new FileHandler();
        $this->fileHandler->setFile($filePath);

        $this->mimeTypeDetector = new MimeTypeDetector($filePath);
    }

    public function getMimeType(): string
    {
        return $this->mimeTypeDetector->getMimeType();
    }

    public function getFileExtension(): string
    {
        return $this->mimeTypeDetector->getFileExtension();
    }

    public function getFileHash(): string
    {
        return $this->fileHandler->getFileHash();
    }

    public function getBase64DataURI(): string
    {
        $fileMimeType = $this->getMimeType();
        $fileContents = @\file_get_contents($this->fileHandler->getFileHash());

        if ($fileContents === false || empty($fileMimeType)) {
            return '';
        }

        $base64String = \base64_encode($fileContents);

        if (!empty($base64String)) {
            return 'data:' . $fileMimeType . ';base64,' . $base64String;
        }

        return '';
    }

    public function getFontAwesomeIcon(string $fileMimeType = '', bool $fixedWidth = false): string
    {
        $iconClass = 'fa-file-o';

        $fileMimeType = $fileMimeType ?: $this->getMimeType();

        $iconClass = match (true) {
            \str_contains($fileMimeType, 'image') => 'fa-file-image-o',
            \str_contains($fileMimeType, 'audio') => 'fa-file-audio-o',
            \str_contains($fileMimeType, 'video') => 'fa-file-video-o',
            default => $iconClass,
        };

        return 'fa ' . $iconClass . ($fixedWidth ? ' fa-fw' : '');
    }
}
