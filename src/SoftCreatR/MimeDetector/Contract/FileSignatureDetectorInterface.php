<?php

declare(strict_types=1);

namespace SoftCreatR\MimeDetector\Contract;

use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

/**
 * Defines the contract for a signature detector that can analyse a file and
 * return a MIME type match when it recognises the underlying format.
 */
interface FileSignatureDetectorInterface
{
    /**
     * Analyse the provided detection context and return a match when the
     * detector recognises the byte signature.
     */
    public function detect(DetectionContext $context): ?MimeTypeMatch;
}
