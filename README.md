# PHP Mime Detector

Detecting the real type of (binary) files doesn't have to be difficult. Checking a file's extension is not reliable and can lead to serious security issues.

This package helps you determine the correct type of files by reading them byte by byte (up to 4096 bytes) and checking for [magic numbers](http://en.wikipedia.org/wiki/Magic_number_(programming)#Magic_numbers_in_files).

However, this package isn't a replacement for any security software. It simply aims to produce fewer false positives than a simple extension check would.

A list of supported file types can be found on [this Wiki page](https://github.com/SoftCreatR/php-mime-detector/wiki/Supported-file-types).

## Why a Separate Class?

You may wonder why we don't just rely on extensions like [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php). Here's a brief background:

We develop extensions and applications for an Open Source PHP Framework, creating web software for the masses. Many of our customers and users of our free products are on shared hosting without any ability to install or manage PHP extensions. Therefore, our goal is to develop solutions with minimal dependencies while providing as much functionality as possible.

When developing a solution that allows people to convert HEIF/HEIC files to a more "standardized" format (using our own external API), we had trouble detecting these files because this format isn't widely recognized by most web servers. Since checking the file extension isn't reliable, we needed to find a reusable solution that works for most of our clients. This led to the creation of our Mime Detector, based on magic number checks.

## Requirements

- PHP 8.1 or newer
- [Composer](https://getcomposer.org)

## Installation

Install this package using [Composer](https://getcomposer.org/) in the root directory of your project:

```bash
composer require softcreatr/php-mime-detector
```

## Usage

Here is an example of how this package makes it easy to determine the MIME type and corresponding file extension of a given file:

```php
<?php

use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

require 'vendor/autoload.php';

try {
    // Create an instance of MimeDetector with the file path
    $mimeDetector = new MimeDetector('foo.bar');

    // Get the MIME type and file extension
    $fileData = [
        'mime_type' => $mimeDetector->getMimeType(),
        'file_extension' => $mimeDetector->getFileExtension(),
        'file_hash' => $mimeDetector->getFileHash(),
    ];

    // Print the result
    echo '<pre>' . print_r($fileData, true) . '</pre>';
} catch (MimeDetectorException $e) {
    die('An error occurred while trying to load the given file: ' . $e->getMessage());
}
```

## Testing

This project uses PHPUnit for testing. To run tests, use the following command:

```bash
composer test
```

To run a full test suite, you can use a set of provided test files. These files are not included in the Composer package or Git repository, so you must clone this repository and initialize its submodules:

```bash
git clone https://github.com/SoftCreatR/php-mime-detector
cd php-mime-detector
git submodule update --init --recursive
```

After that, install the necessary dependencies with `composer install`, and run PHPUnit as described above.

## ToDo

- Reduce method sizes where possible.
- Add a method that accepts a MIME type and returns the corresponding file extension.
- Add a method that accepts a file extension and returns a list of corresponding MIME types.
- Add a method that returns a list of all detectable MIME types and their corresponding file extensions.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

When adding new detections, please provide at least one sample file to ensure the detection works as expected.

## License

[ISC License](LICENSE.md)

Free Software, Hell Yeah!
