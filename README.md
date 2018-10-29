# PHP Mime Detector [![Build Status](https://travis-ci.com/SoftCreatR/php-mime-detector.svg?branch=master)](https://travis-ci.com/SoftCreatR/php-mime-detector) [![Codeship Status for SoftCreatR/php-mime-detector](https://app.codeship.com/projects/9ed81740-b269-0136-7bd2-3ad13aca57c1/status?branch=master)](https://app.codeship.com/projects/310674)

[![CodeFactor](https://www.codefactor.io/repository/github/softcreatr/php-mime-detector/badge)](https://www.codefactor.io/repository/github/softcreatr/php-mime-detector)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/4d404e53d8ec465197a38d9b15c4746e)](https://www.codacy.com/app/SoftCreatR/php-mime-detector?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=SoftCreatR/php-mime-detector&amp;utm_campaign=Badge_Grade)
[![codecov](https://codecov.io/gh/SoftCreatR/php-mime-detector/branch/master/graph/badge.svg)](https://codecov.io/gh/SoftCreatR/mime-detector)

Detecting the real type of a (binary) file doesn't have to be hard. Checking a file's extension is not reliable and can cause serious security issues.

This package helps you to determine the correct type of a file, by reading it byte for byte (up to 4096) and check for [magic numbers](http://en.wikipedia.org/wiki/Magic_number_(programming)#Magic_numbers_in_files).

However, this package isn't a replacement for any security software. It just aims to produce less false positives, than a simple extension check would produce.

A list of supported file types can be found on [this Wiki page](https://github.com/SoftCreatR/php-mime-detector/wiki/Supported-file-types).

__Why a separate class?__

You may wonder, why we don't just rely on extensions like [Fileinfo](https://secure.php.net/manual/en/book.fileinfo.php)? First off all, a short background story:

We are building extensions and applications for an Open Source PHP Framework, thus we are creating web software for the masses. Many of our customers and/or users of our free products are on shared hosting without any possibility to install or manage installed PHP extensions. So our goal is to develop solutions with as few dependencies as necessary, but with as much functionality as possible.

While developing a solution, that allows people to convert HEIF/HEIC files to a more "standardized" format (using our own, external API), we had troubles detecting these files, because especially this format isn't known by most Webservers, yet. While checking the file extension isn't reliable, we had to find a reusable solution that works for most of our clients. So we started to build a magic number based check for these files. That was the birth of our Mime Detector.

__Why are the unit tests so poorly written?__

Short answer: I have just little experience in unit testing. This project was a good training and even if the unit tests could be better: I am proud of my progress :)

## Requirements

-   PHP 7.1 or newer
-   [Composer](https://getcomposer.org)

If you are looking for a solution that works on older PHP versions (5.3.2+), head over to the [oldphp](https://github.com/SoftCreatR/php-mime-detector/tree/oldphp) branch.

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
$mimeDetector = new MimeDetector();

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

Or short:

```php
use SoftCreatR\MimeDetector\MimeDetector;
use SoftCreatR\MimeDetector\MimeDetectorException;

try {
    echo '<pre>' . print_r((new MimeDetector())->setFile('foo.bar')->getFileType(), true) . '</pre>';
} catch (MimeDetectorException $e) {
    die('An error occured while trying to load the given file.');
}
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

## ToDo

-   Reduce method sizes, when possible
-   Add a method, that accepts a mime type and returns the corresponding file extension
-   Add a method, that accepts a file extension and returns a list of corresponding mime types
-   Add a method, that returns a list of all detectable mime types and their corresponding file extensions

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

When addding new detections, please make sure to provide at least one sample file.

## License

[![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg)](https://www.gnu.org/licenses/lgpl-3.0)

Free Software, Hell Yeah!

## Support on BMC
Hey! Help us out with some cups of :coffee:!

[![BMC](https://www.buymeacoffee.com/assets/img/guidelines/download-assets-sm-2.svg)](https://www.buymeacoff.ee/softcreatr)
