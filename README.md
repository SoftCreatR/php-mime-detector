[![Codacy Badge](https://api.codacy.com/project/badge/Grade/515de901e3194e249ad7da95662ae27d)](https://app.codacy.com/app/SoftCreatR/php-mime-detector?utm_source=github.com&utm_medium=referral&utm_content=SoftCreatR/php-mime-detector&utm_campaign=Badge_Grade_Settings)
# PHP Mime Detector [![Build Status](https://travis-ci.com/SoftCreatR/php-mime-detector.svg?branch=master)](https://travis-ci.com/SoftCreatR/php-mime-detector)

Detecting the real type of a (binary) file doesn't have to be hard. Checking a file's extension is not reliable and can cause serious security issues.

This package helps you to determine the correct type of a file, by reading it byte for byte (up to 4096) and check for [magic numbers](http://en.wikipedia.org/wiki/Magic_number_(programming)#Magic_numbers_in_files).

However, this package isn't a replacement for any security software. It just aims to produce less false positives, than a simple extension check would produce.

A list of supported file types can be found on [this Wiki page](https://github.com/SoftCreatR/php-mime-detector/wiki/Supported-file-types).

## Requirements

- PHP 7.1 or newer
- HTTP server with PHP support (eg: Apache, Nginx, Caddy)
- [Composer](https://getcomposer.org)

## Installation

Require this package using [Composer](https://getcomposer.org/), in the root directory of your project:

``` bash
$ composer require softcreatr/php-mime-detector
```

## Usage

Here is an example on how this package makes it very easy to determine the mime type (and it's corresponding file extension) of a given file:

```php
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

// create an instance of the MimeDetector
$mimeDetector = MimeDetector::getInstance();

// set our file to read
try {
    $mimeDetector->setFile('foo.bar');
} catch (MimeDetectorException $e) {
    die('An error occured while trying to load the given file.');
}

// try to determine it's mime type and the correct file extension
$fileData = $mimeDetector->getFileType();

// print the result
echo '<pre>' . print_r($fileData, true) . '</pre>';
```

## Testing

Testing utilizes PHPUnit (what else?) by running this command:

``` bash
$ composer test
```

However, you may check out a bunch of test files for a full test. Test files are no longer included in the composer package nor the Git repository itself, so you have to perform a checkout of this repository and install it's submodules:

``` bash
$ git clone https://github.com/SoftCreatR/php-mime-detector
$ cd php-mime-detector
$ git submodule update --init --recursive
```

When done, perform a `composer install` and run PHPUnit as described above.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

When addding new detections, please make sure to provide at least one sample file.

## License

[![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg)](https://www.gnu.org/licenses/lgpl-3.0)

Free Software, Hell Yeah!

## Support on BMC
Hey! Help us out with some cups of :coffee:!

[![BMC](https://www.buymeacoffee.com/assets/img/guidelines/download-assets-sm-2.svg)](https://www.buymeacoff.ee/softcreatr)
