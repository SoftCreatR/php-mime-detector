# PHP Mime Detector

A modern, extensible MIME type detector for PHP that analyses the actual bytes
of a file instead of trusting its extension. The detector ships with a modular
pipeline of signature matchers and a bidirectional repository of MIME type ↔
extension mappings so you can integrate it into security-sensitive workflows.

## Features

- **Real file inspection** – identifies file formats by signature instead of
  relying on filenames. You can find a list of supported file formats in the
  [Wiki](https://github.com/SoftCreatR/php-mime-detector/wiki/Supported-file-types).
- **Composable architecture** – category-specific detectors can be swapped in
  or extended without touching the core.
- **Rich lookup helpers** – translate between MIME types and extensions in both
  directions and enumerate the supported catalogue.
- **No external dependencies** – runs on any PHP 8.1+ installation without and can take
  requiring additional, external extensions, or packages, but takes advantage
  of `ZipArchive` when it is available.

## Installation

Install the package via Composer:

```bash
composer require softcreatr/php-mime-detector
```

## Quick start

Detecting the MIME type and the preferred extension for a file is as simple as
instantiating the façade and calling its helpers:

```php
<?php

use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

require 'vendor/autoload.php';

try {
    $detector = new MimeDetector(__DIR__ . '/example.png');

    echo $detector->getMimeType();       // image/png
    echo $detector->getFileExtension();  // png
    echo $detector->getFileHash();       // crc32 hash of the file contents
} catch (MimeDetectorException $exception) {
    // React to unreadable files or unsupported formats.
    echo $exception->getMessage();
}
```

## Resolving MIME types and extensions

The façade exposes several lookup helpers that do not require a file scan. They
operate on the shared repository of known mappings:

```php
$detector = new MimeDetector(__DIR__ . '/example.png');

// Retrieve the canonical extension for a MIME type.
$extension = $detector->getExtensionForMimeType('image/jpeg'); // "jpg"

// List every MIME type that corresponds to the given extension.
$mimeTypes = $detector->getMimeTypesForExtension('heic');

// Fetch the complete map as [mimeType => list of extensions].
$catalogue = $detector->listAllMimeTypes();
```

Need a data URI? The detector will encode the configured file for you:

```php
$dataUri = $detector->getBase64DataURI();
// data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
```

## Optional ZipArchive support

The detector is fully functional without PHP's `ZipArchive` extension; all ZIP
signatures are recognised by scanning the first 4 KiB of the file for well-known
markers such as `mimetype`, `[Content_Types].xml`, or `classes.dex`. When the
extension is present, the `ZipSignatureDetector` opens the archive and inspects
its entries directly. This deeper look allows the detector to resolve format
families like OOXML (`.docx`, `.pptx`, `.xlsx`), APK/JAR/XPI bundles, and other
ZIP-based containers even when their identifying files live deeper inside the
archive than the cached bytes.

If the extension is missing, the detector simply falls back to its heuristic
path and ultimately reports a generic `application/zip` match whenever a more
specific signature cannot be derived. Unit tests that require `ZipArchive` are
skipped automatically when the class is not available, so no additional setup is
needed to run the suite.

## Extending the detector

Custom formats can be added without modifying the library itself. Follow these
steps to teach the detector about a new signature and MIME mapping.

### 1. Implement a signature detector

Create a class that implements
`SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface`. The detector
receives the `DetectionContext`, which gives access to the file buffer and lets
you return a `MimeTypeMatch` when the signature is recognised.

```php
<?php

namespace App\MimeDetector;

use SoftCreatR\MimeDetector\Attribute\DetectorCategory;
use SoftCreatR\MimeDetector\Contract\FileSignatureDetectorInterface;
use SoftCreatR\MimeDetector\Detection\DetectionContext;
use SoftCreatR\MimeDetector\Detection\MimeTypeMatch;

#[DetectorCategory('custom')]
final class CustomContainerDetector implements FileSignatureDetectorInterface
{
    public function detect(DetectionContext $context): ?MimeTypeMatch
    {
        $buffer = $context->buffer();

        if ($buffer->checkForBytes([0x43, 0x55, 0x53, 0x54])) { // "CUST"
            return new MimeTypeMatch('custom', 'application/x-custom');
        }

        return null;
    }
}
```

### 2. Register MIME mappings

Extend the repository so your MIME type resolves to the expected extension(s):

```php
use SoftCreatR\MimeDetector\MimeTypeRepository;

$repository = MimeTypeRepository::createDefault();
$repository->register('custom', 'application/x-custom');
```

### 3. Compose a detector pipeline

Finally, plug your detector into the default pipeline and hand both pieces to
the façade. Placing your detector first ensures it runs before the built-in
matchers:

```php
use SoftCreatR\MimeDetector\Detection\DetectorPipeline;
use SoftCreatR\MimeDetector\Detector\ArchiveSignatureDetector;
use SoftCreatR\MimeDetector\Detector\DocumentSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ExecutableSignatureDetector;
use SoftCreatR\MimeDetector\Detector\FontSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ImageSignatureDetector;
use SoftCreatR\MimeDetector\Detector\MediaSignatureDetector;
use SoftCreatR\MimeDetector\Detector\MiscSignatureDetector;
use SoftCreatR\MimeDetector\Detector\XmlSignatureDetector;
use SoftCreatR\MimeDetector\Detector\ZipSignatureDetector;
use SoftCreatR\MimeDetector\MimeDetector;

$pipeline = DetectorPipeline::create(
    new CustomContainerDetector(),
    new ImageSignatureDetector(),
    new ZipSignatureDetector(),
    new ArchiveSignatureDetector(),
    new MediaSignatureDetector(),
    new DocumentSignatureDetector(),
    new FontSignatureDetector(),
    new ExecutableSignatureDetector(),
    new MiscSignatureDetector(),
    new XmlSignatureDetector(),
);

$detector = new MimeDetector(__DIR__ . '/file.cust', $repository, $pipeline);
```

From this point the new MIME type behaves exactly like the built-in ones – it
can be detected from files, resolved by MIME type, and listed in the catalogue.

## Testing

Fixture files for the test suite are stored in a Git submodule. After cloning
this repository run:

```bash
git submodule update --init --recursive
composer install
composer test
```

## Contributing

We welcome pull requests! Please review [CONTRIBUTING](CONTRIBUTING.md) for the
coding standards and workflow. When adding new detections, include at least one
fixture so behaviour can be verified automatically.

## License

Released under the [ISC License](LICENSE.md).
