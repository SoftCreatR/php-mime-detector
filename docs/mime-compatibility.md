# MIME compatibility audit

## Scope and evidence

The 2026-10-04 audit reviewed all 212 MIME names in the bundled catalogue.
The comparison helper recognizes 131 alternate names across 72 format groups.
This covers documented naming differences for supported formats; it does not
promise to recognize every historical label or every format supported by libmagic.

The machine-readable [audit snapshot](https://github.com/SoftCreatR/php-mime-detector/blob/main/tests/SoftCreatR/MimeDetector/data/mime-alias-audit.json)
records the reviewed catalogue, source references and hashes, positive alias
groups, and 46 excluded comparisons. Tests cover every spelling in both
comparison directions, registration under either spelling, and exclusion in
both directions. Catalogue additions must update the reviewed snapshot.

Primary references:

- [libmagic magic definitions](https://github.com/file/file/tree/5e57b94cdd3f2bc2f6db8581dcce76a89fc9c9f7/magic/Magdir),
  inspected at commit `5e57b94cdd3f2bc2f6db8581dcce76a89fc9c9f7`, including
  older BMP names in the `FILE5_37` image definitions.
- [shared-mime-info 2.4-5 source](https://sources.debian.org/data/main/s/shared-mime-info/2.4-5/data/freedesktop.org.xml.in),
  distributed by Debian from the upstream freedesktop project. Its alias groups
  were reviewed individually: some groups combine more specific formats.
- [WebKit MIME type registry](https://github.com/WebKit/WebKit/blob/main/Source/WebCore/platform/MIMETypeRegistry.cpp),
  with the retrieved content hash recorded in the snapshot. Its Java association
  conflicts with shared-mime-info; `application/java` remains unaliased.
- [IANA media type registry](https://www.iana.org/assignments/media-types/media-types.xhtml),
  notably the registrations for BMP, Debian packages, SQLite, JPEG XR, Matroska,
  PE executables, and AAC; [RFC 6713](https://www.rfc-editor.org/rfc/rfc6713)
  for gzip/zlib and [RFC 8081](https://www.rfc-editor.org/rfc/rfc8081) for fonts.

Preferred names use registered names where the format definition supports them.
Some formats have only conventional names; `preferred()` is not an IANA validator.
Existing raw detection names remain available except for newly distinguished or
corrected formats listed in the [changelog](../CHANGELOG.md).

## Comparison and lookup policy

`equivalent()` compares reviewed alternate names for the same format. It does
not grant permission to upload a file. Unknown names compare by their normalized
spelling. Charset parameters are ignored; other parameters must match after
normalizing parameter names, whitespace, quoting and order. Parameter values
retain their case, including codec identifiers.

Extension lookup groups registrations by preferred MIME name. Registering a
legacy name allows lookup by the preferred name and every reviewed alias, and
extensions registered under equivalent names are merged, deduplicated and sorted.
`all()` and extension-to-MIME lookup retain the registered spellings.

Keep subtype and container comparisons in the consuming application's policy.
Examples that remain distinct include:

- Opus and Ogg, TTF/OTF and generic SFNT, APNG and PNG, TIFF RAW and TIFF.
- HEIC and HEIF, still images and image sequences, MP4 and MPEG-4 elementary streams.
- ADTS-specific AAC names and `audio/aac`, which also covers LATM/LOAS transport.
- Generic ELF and executables, generic compound files and MSI/Office, and
  `application/x-msdownload` and specifically identified PE executables.
- Generic module audio and IT/XM, generic PostScript and EPS, legacy Visio and
  modern XML packages, older iWork bundles and current ZIP-based documents.
- Audio/video or encrypted variants of 3GPP, codec-specific AVI labels, and
  ASCII/binary-specific STL names.

The linked snapshot gives a reason for every reviewed exclusion. Differing names
can reflect a more precise detector result, a coarser result, a misidentification,
or a genuinely different format. They must not be made equivalent just because
both tools observed the same filename extension.

## Accepted groups

The preferred name is in the first column. All names in a row compare as
equivalent, including comparisons between two aliases.

| Preferred name | Alternate names |
| --- | --- |
| `application/eps` | `application/x-eps`, `image/x-eps` |
| `application/gpx+xml` | `application/gpx`, `application/x-gpx`, `application/x-gpx+xml` |
| `application/gzip` | `application/x-gzip` |
| `application/java-archive` | `application/x-jar`, `application/x-java-archive` |
| `application/java-vm` | `application/java-byte-code`, `application/x-java`, `application/x-java-applet`, `application/x-java-class`, `application/x-java-vm` |
| `application/ogg` | `application/x-ogg` |
| `application/pdf` | `application/acrobat`, `application/nappdf`, `application/x-pdf`, `image/pdf` |
| `application/rdf+xml` | `text/rdf` |
| `application/rss+xml` | `text/rss` |
| `application/rtf` | `text/rtf` |
| `application/vnd.debian.binary-package` | `application/x-deb`, `application/x-debian-package` |
| `application/vnd.ms-asf` | `video/x-ms-asf`, `video/x-ms-asf-plugin`, `video/x-ms-wm` |
| `application/vnd.ms-cab-compressed` | `zz-application/zz-winassoc-cab` |
| `application/vnd.ms-excel` | `application/msexcel`, `application/x-msexcel`, `zz-application/zz-winassoc-xls` |
| `application/vnd.ms-htmlhelp` | `application/x-chm` |
| `application/vnd.rar` | `application/x-rar`, `application/x-rar-compressed` |
| `application/vnd.sqlite3` | `application/x-sqlite3` |
| `application/vnd.tcpdump.pcap` | `application/pcap`, `application/x-pcap` |
| `application/x-bzip2` | `application/bzip2`, `application/x-bzip` |
| `application/x-google-chrome-extension` | `application/x-chrome-extension` |
| `application/x-lzh-compressed` | `application/x-lha` |
| `application/x-ms-regedit` | `text/x-ms-regedit` |
| `application/x-nintendo-nes-rom` | `application/x-nes-rom` |
| `application/x-rpm` | `application/x-redhat-package-manager` |
| `application/x-shockwave-flash` | `application/vnd.adobe.flash.movie` |
| `application/x-tar` | `application/x-gtar`, `application/x-ustar` |
| `application/x-unix-archive` | `application/x-archive` |
| `application/x.ms.shortcut` | `application/x-ms-shortcut` |
| `application/xml` | `text/xml` |
| `application/zip` | `application/x-zip`, `application/x-zip-compressed` |
| `audio/aac` | `audio/x-aac` |
| `audio/aiff` | `audio/x-aiff` |
| `audio/amr` | `audio/x-amr` |
| `audio/ape` | `audio/x-ape` |
| `audio/flac` | `audio/x-flac` |
| `audio/midi` | `audio/mid`, `audio/x-mid`, `audio/x-midi` |
| `audio/mp4` | `audio/m4a`, `audio/x-m4a`, `audio/x-mp4` |
| `audio/mpeg` | `audio/mp3`, `audio/mpeg3`, `audio/mpg`, `audio/mpg3`, `audio/x-mp3`, `audio/x-mpeg`, `audio/x-mpeg3`, `audio/x-mpg` |
| `audio/ogg` | `audio/x-ogg` |
| `audio/vnd.wave` | `audio/wav`, `audio/x-wav` |
| `audio/x-dsf` | `audio/dsf` |
| `audio/x-ms-wma` | `audio/wma` |
| `font/otf` | `application/x-font-otf` |
| `font/sfnt` | `application/font-sfnt` |
| `font/ttf` | `application/x-font-ttf` |
| `font/woff` | `application/font-woff` |
| `image/apng` | `image/vnd.mozilla.apng` |
| `image/bmp` | `image/x-bitmap`, `image/x-bmp`, `image/x-ms-bitmap`, `image/x-ms-bmp`, `image/x-windows-bmp` |
| `image/icns` | `image/x-icns` |
| `image/j2c` | `image/x-jp2-codestream` |
| `image/jp2` | `image/jpeg2000`, `image/jpeg2000-image`, `image/x-jpeg2000-image` |
| `image/jpeg` | `image/jpg`, `image/pjpeg` |
| `image/jxr` | `image/vnd.ms-photo` |
| `image/mj2` | `video/mj2` |
| `image/png` | `image/x-png` |
| `image/vnd-ms.dds` | `image/x-dds` |
| `image/vnd.adobe.photoshop` | `application/photoshop`, `application/x-photoshop`, `image/photoshop`, `image/psd`, `image/x-photoshop`, `image/x-psd` |
| `image/vnd.microsoft.icon` | `application/ico`, `image/ico`, `image/icon`, `image/x-ico`, `image/x-icon`, `text/ico` |
| `image/x-fujifilm-raf` | `image/x-fuji-raf` |
| `image/x-panasonic-rw2` | `image/x-panasonic-raw2` |
| `image/x-portable-graymap` | `image/x-portable-greymap` |
| `model/3mf` | `application/vnd.ms-3mfdocument` |
| `text/calendar` | `application/ics`, `text/x-vcalendar` |
| `text/vcard` | `text/x-vcard` |
| `video/3gpp` | `video/3gp` |
| `video/matroska` | `video/x-matroska` |
| `video/mp4` | `video/x-m4v` |
| `video/mpeg` | `video/mpeg-system`, `video/x-mpeg`, `video/x-mpeg-system`, `video/x-mpeg2` |
| `video/ogg` | `video/x-ogg` |
| `video/quicktime` | `video/x-quicktime` |
| `video/vnd.avi` | `video/avi`, `video/msvideo`, `video/x-avi`, `video/x-msvideo` |
| `video/x-flv` | `application/x-flash-video`, `flv-application/octet-stream`, `video/flv` |

## Signature audit and fixtures

The new signature checks use libmagic as a reference and consult format
specifications for header fields. They cover QOI, DDS, OpenEXR, DjVu, Netpbm,
JPEG-LS, lzop, zlib, PCAPNG, nanosecond PCAP, AAC ADTS, MPEG layer I, SketchUp,
32-bit/universal Mach-O (32-bit and 64-bit FAT tables), and PE.
Universal headers follow [Apple's format definition](https://github.com/apple-oss-distributions/cctools/blob/main/include/mach-o/fat.h). SQLite requires its full 16-byte signature.
DMG inspection uses the UDIF footer; ID3 inspection skips bounded metadata
before identifying AAC, FLAC, or MPEG audio. These checks identify formats from
headers and trailers rather than validating complete decoded contents.

The fixture repository contains 20 new files: generated complete image/archive/
capture examples three MIT-licensed Mach-O samples, and a generated 64-bit FAT wrapper from
[file-type](https://github.com/sindresorhus/file-type/tree/32b087c6eaa2c03a083f599cf2f91fe9d1aba452/fixture).
`signature-audit-fixtures.json` in that repository records generators, source
URLs, licenses and SHA-256 hashes. Existing JPEG-LS, AAC, tagged FLAC, PE,
SketchUp, MPEG and UTF-16 fixtures have explicit expected results in the tests.

To compare every local fixture with the installed `fileinfo` database:

```sh
php tools/compare-fileinfo.php
php tools/compare-fileinfo.php /path/to/another/corpus
```

The development tool requires `ext-fileinfo` and writes JSON with counts and
individual differences. Generic `application/octet-stream` results are counted
as unidentified. It applies the same alias helper as consumers and leaves
container/subtype differences visible. Results depend on the local libmagic
version. This tool is excluded from Composer distribution archives.

The fixture corpus does not provide a real sample for every catalogue entry;
the alias audit covers the complete catalogue, and fixture assertions cover the
formats represented by actual samples. Further detection work should add a
complete sample, explicit expected output, a near-match rejection case, and a
source reference, then review the new MIME name and its aliases.
