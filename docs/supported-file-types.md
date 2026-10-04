# Supported file types

This reference describes the **default detector pipeline in php-mime-detector 5.3.0**.
It is based on the [released detector implementations](https://github.com/SoftCreatR/php-mime-detector/tree/5.3.0/src/SoftCreatR/MimeDetector/Detector)
and the [fixture corpus](https://github.com/SoftCreatR/mime-detector-fixtures/tree/1598b78).
Custom detectors and repositories can extend or change these results.

## Reading this reference

- **Returned extension** is the value from `getFileExtension()`. A detected file can have a different filename suffix.
- **Detected MIME type** is the exact value from `getMimeType()`. These are the names an application receives when applying MIME patterns or allowlists.
- Multiple rows for an extension describe different results selected by the file's contents.
- **Preferred MIME names** are documented separately below. Use `getPreferredMimeType()` when you want the library's preferred spelling or, for Ogg Opus, its recommended container name.

Detection inspects recognizable signatures and selected container metadata. It does
not decode or validate every part of a file. The tables describe implemented checks;
coverage varies between format versions and variants. If no detector matches,
`getMimeType()` and `getFileExtension()` return empty strings.

```php
use SoftCreatR\MimeDetector\MimeDetector;

$detector = new MimeDetector('/path/to/file.wav');

$detector->getFileExtension();     // wav
$detector->getMimeType();          // audio/vnd.wave
$detector->getPreferredMimeType(); // audio/vnd.wave
```

## Contents

- [Archives, compression and disk images](#archives-compression-and-disk-images)
- [ZIP container formats](#zip-container-formats)
- [Documents and compound files](#documents-and-compound-files)
- [Images, textures and camera RAW](#images-textures-and-camera-raw)
- [Audio and video](#audio-and-video)
- [Fonts](#fonts)
- [Executables and bytecode](#executables-and-bytecode)
- [Markup and structured text](#markup-and-structured-text)
- [Data, databases, CAD and 3D](#data-databases-cad-and-3d)
- [System and other formats](#system-and-other-formats)
- [Preferred names and MIME aliases](#preferred-names-and-mime-aliases)
- [Extension lookup catalogue](#extension-lookup-catalogue)
- [Variant coverage and optional features](#variant-coverage-and-optional-features)
- [Fixture coverage](#fixture-coverage)

## Archives, compression and disk images

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| 7-Zip compressed archive | `7z` | `application/x-7z-compressed` |
| ACE archive | `ace` | `application/x-ace-compressed` |
| Unix archive (ar) | `ar` | `application/x-unix-archive` |
| ARJ archive | `arj` | `application/x-arj` |
| Electron ASAR archive | `asar` | `application/x-asar` |
| bzip2 compressed data | `bz2` | `application/x-bzip2` |
| Microsoft Cabinet | `cab` | `application/vnd.ms-cab-compressed` |
| CPIO archive | `cpio` | `application/x-cpio` |
| Debian package | `deb` | `application/x-deb` |
| Apple disk image | `dmg` | `application/x-apple-diskimage` |
| gzip compressed data | `gz` | `application/gzip` |
| ISO 9660 disk image | `iso` | `application/x-iso9660-image` |
| Lzip compressed data | `lz` | `application/x-lzip` |
| LZ4 frame | `lz4` | `application/x-lz4` |
| LHA/LZH archive | `lzh` | `application/x-lzh-compressed` |
| lzop-compressed data | `lzo` | `application/x-lzop` |
| RAR archive | `rar` | `application/x-rar-compressed` |
| RPM package | `rpm` | `application/x-rpm` |
| tar archive | `tar` | `application/x-tar` |
| XZ compressed data | `xz` | `application/x-xz` |
| compress(1) (Z) | `z` | `application/x-compress` |
| ZIP archive | `zip` | `application/zip` |
| zlib-compressed stream | `zlib` | `application/zlib` |
| Zstandard compressed data | `zst` | `application/zstd` |

## ZIP container formats

ZIP subtypes depend on recognizable entries or internal MIME markers.
`ZipArchive` enables inspection of compressed metadata and is required for
the iWork detections listed here. See [optional ZIP inspection](#optional-zip-inspection).

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| 3D Manufacturing Format | `3mf` | `model/3mf` |
| Android package | `apk` | `application/vnd.android.package-archive` |
| Word macro-enabled | `docm` | `application/vnd.ms-word.document.macroenabled.12` |
| Word (OOXML) | `docx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` |
| Word macro template | `dotm` | `application/vnd.ms-word.template.macroenabled.12` |
| Word template | `dotx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.template` |
| EPUB eBook | `epub` | `application/epub+zip` |
| Java archive | `jar` | `application/java-archive` |
| Apple Keynote ZIP document | `key` | `application/vnd.apple.keynote` |
| Apple Numbers ZIP document | `numbers` | `application/vnd.apple.numbers` |
| ODG | `odg` | `application/vnd.oasis.opendocument.graphics` |
| ODP | `odp` | `application/vnd.oasis.opendocument.presentation` |
| ODS | `ods` | `application/vnd.oasis.opendocument.spreadsheet` |
| ODT | `odt` | `application/vnd.oasis.opendocument.text` |
| ODG template | `otg` | `application/vnd.oasis.opendocument.graphics-template` |
| ODP template | `otp` | `application/vnd.oasis.opendocument.presentation-template` |
| ODS template | `ots` | `application/vnd.oasis.opendocument.spreadsheet-template` |
| ODT template | `ott` | `application/vnd.oasis.opendocument.text-template` |
| Apple Pages ZIP document | `pages` | `application/vnd.apple.pages` |
| PPT macro template | `potm` | `application/vnd.ms-powerpoint.template.macroenabled.12` |
| PPT template | `potx` | `application/vnd.openxmlformats-officedocument.presentationml.template` |
| PPT slideshow macro | `ppsm` | `application/vnd.ms-powerpoint.slideshow.macroenabled.12` |
| PowerPoint slideshow | `ppsx` | `application/vnd.openxmlformats-officedocument.presentationml.slideshow` |
| PPT macro-enabled | `pptm` | `application/vnd.ms-powerpoint.presentation.macroenabled.12` |
| PowerPoint (OOXML) | `pptx` | `application/vnd.openxmlformats-officedocument.presentationml.presentation` |
| Visio Drawing | `vsdx` | `application/vnd.visio` |
| Visio Template | `vstx` | `application/vnd.ms-visio.template.main+xml` |
| Excel macro-enabled | `xlsm` | `application/vnd.ms-excel.sheet.macroenabled.12` |
| Excel (OOXML) | `xlsx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| Excel macro template | `xltm` | `application/vnd.ms-excel.template.macroenabled.12` |
| Excel template | `xltx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.template` |
| Mozilla add-on | `xpi` | `application/x-xpinstall` |

## Documents and compound files

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| Unidentified OLE compound file | `cfb` | `application/x-cfb` |
| Microsoft Compiled HTML Help | `chm` | `application/vnd.ms-htmlhelp` |
| DjVu document/image | `djvu` | `image/vnd.djvu` |
| Encapsulated PostScript | `eps` | `application/eps` |
| Adobe InDesign | `indd` | `application/x-indesign` |
| Mobipocket eBook | `mobi` | `application/x-mobipocket-ebook` |
| Portable Document Format | `pdf` | `application/pdf` |
| PostScript | `ps` | `application/postscript` |
| Outlook Personal Storage | `pst` | `application/vnd.ms-outlook` |
| Rich Text Format | `rtf` | `application/rtf` |
| Visio (binary) | `vsd` | `application/vnd.visio` |
| Excel (binary) | `xls` | `application/vnd.ms-excel` |

## Images, textures and camera RAW

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| Animated cursor (RIFF) | `ani` | `application/x-navi-animation` |
| Animated PNG | `apng` | `image/apng` |
| Sony RAW (TIFF-based) | `arw` | `image/x-sony-arw` |
| AVIF still-image or sequence brand | `avif` | `image/avif` |
| Bitmap | `bmp` | `image/bmp` |
| Better Portable Graphics | `bpg` | `image/bpg` |
| Canon RAW (CR2) | `cr2` | `image/x-canon-cr2` |
| Canon RAW (CR3) | `cr3` | `image/x-canon-cr3` |
| Windows cursor | `cur` | `image/x-icon` |
| DICOM medical image | `dcm` | `application/dicom` |
| DirectDraw Surface texture | `dds` | `image/vnd-ms.dds` |
| Adobe Digital Negative (TIFF-based) | `dng` | `image/x-adobe-dng` |
| OpenEXR image | `exr` | `image/x-exr` |
| Free Lossless Image Format | `flif` | `image/flif` |
| GIF | `gif` | `image/gif` |
| HEIC still image | `heic` | `image/heic` |
| HEIC image sequence | `heic` | `image/heic-sequence` |
| HEIF still image | `heic` | `image/heif` |
| HEIF image sequence | `heic` | `image/heif-sequence` |
| macOS icon | `icns` | `image/icns` |
| Windows icon | `ico` | `image/x-icon` |
| JPEG-2000 codestream | `j2c` | `image/j2c` |
| JPEG-LS image | `jls` | `image/jls` |
| JPEG 2000 image | `jp2` | `image/jp2` |
| JPEG | `jpg` | `image/jpeg` |
| JPEG 2000 (JPM) | `jpm` | `image/jpm` |
| JPEG 2000 (JPX) | `jpx` | `image/jpx` |
| JPEG XL | `jxl` | `image/jxl` |
| JPEG XR | `jxr` | `image/vnd.ms-photo` |
| Khronos Texture version 1 | `ktx` | `image/ktx` |
| Motion JPEG 2000 | `mj2` | `image/mj2` |
| Nikon RAW (TIFF-based) | `nef` | `image/x-nikon-nef` |
| Olympus RAW | `orf` | `image/x-olympus-orf` |
| Netpbm bitmap, ASCII or binary | `pbm` | `image/x-portable-bitmap` |
| Netpbm graymap, ASCII or binary | `pgm` | `image/x-portable-graymap` |
| Portable Network Graphic | `png` | `image/png` |
| Netpbm pixmap, ASCII or binary | `ppm` | `image/x-portable-pixmap` |
| Photoshop document | `psd` | `image/vnd.adobe.photoshop` |
| Quite OK Image | `qoi` | `image/x-qoi` |
| Fujifilm RAW | `raf` | `image/x-fujifilm-raf` |
| Panasonic RAW | `rw2` | `image/x-panasonic-rw2` |
| TIFF | `tif` | `image/tiff` |
| WebP | `webp` | `image/webp` |
| GIMP image | `xcf` | `image/x-xcf` |

## Audio and video

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| 3GPP2 video | `3g2` | `video/3gpp2` |
| 3GPP video | `3gp` | `video/3gpp` |
| AAC ADTS audio | `aac` | `audio/aac` |
| AC-3 | `ac3` | `audio/vnd.dolby.dd-raw` |
| AIFF or AIFF-C audio | `aif` | `audio/aiff` |
| AMR narrowband, single or multi-channel | `amr` | `audio/amr` |
| Monkey’s Audio | `ape` | `audio/ape` |
| ASF without an identified audio/video stream | `asf` | `video/x-ms-asf` |
| Sun/NeXT AU | `au` | `audio/basic` |
| AVI | `avi` | `video/vnd.avi` |
| AMR-WB, single or multi-channel | `awb` | `audio/amr-wb` |
| Apple Core Audio Format | `caf` | `audio/x-caf` |
| DSD stream (Sony) | `dsf` | `audio/x-dsf` |
| F4A audio container | `f4a` | `audio/mp4` |
| F4B audio book | `f4b` | `audio/mp4` |
| F4P protected media container | `f4p` | `video/mp4` |
| F4V video container | `f4v` | `video/mp4` |
| FLAC | `flac` | `audio/x-flac` |
| Flash video | `flv` | `video/x-flv` |
| Impulse Tracker | `it` | `audio/x-it` |
| M4A fallback marker | `m4a` | `audio/mp4` |
| M4A with a recognized ISO brand | `m4a` | `audio/x-m4a` |
| M4B audio book | `m4b` | `audio/mp4` |
| M4P protected media container | `m4p` | `video/mp4` |
| MPEG-4 Video (Apple) | `m4v` | `video/x-m4v` |
| MIDI | `mid` | `audio/midi` |
| Matroska | `mkv` | `video/x-matroska` |
| QuickTime | `mov` | `video/quicktime` |
| MPEG audio layer I | `mp1` | `audio/mpeg` |
| MPEG audio layer II | `mp2` | `audio/mpeg` |
| MPEG audio layer III | `mp3` | `audio/mpeg` |
| MP4 container identified from supported brands | `mp4` | `video/mp4` |
| Musepack | `mpc` | `audio/x-musepack` |
| MPEG Program Stream/Video | `mpg` | `video/mpeg` |
| MPEG-TS (AVCHD) | `mts` | `video/mp2t` |
| Material eXchange Format | `mxf` | `application/mxf` |
| Ogg FLAC audio | `oga` | `audio/ogg` |
| Ogg Vorbis audio | `ogg` | `audio/ogg` |
| Ogg media with a video stream header | `ogm` | `video/ogg` |
| Ogg Theora video | `ogv` | `video/ogg` |
| Ogg container (generic) | `ogx` | `application/ogg` |
| Opus in Ogg | `opus` | `audio/opus` |
| QCELP | `qcp` | `audio/qcelp` |
| RealMedia container | `rm` | `application/vnd.rn-realmedia` |
| Scream Tracker 3 | `s3m` | `audio/x-s3m` |
| Ogg Speex audio | `spx` | `audio/ogg` |
| Creative Voice | `voc` | `audio/x-voc` |
| WAV (RIFF) | `wav` | `audio/vnd.wave` |
| WebM (Matroska subset) | `webm` | `video/webm` |
| ASF with an identified audio stream | `wma` | `audio/x-ms-wma` |
| ASF with an identified video stream | `wmv` | `video/x-ms-wmv` |
| WavPack audio | `wv` | `audio/wavpack` |
| FastTracker XM | `xm` | `audio/x-xm` |

## Fonts

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| Embedded OpenType | `eot` | `application/vnd.ms-fontobject` |
| OpenType font | `otf` | `font/otf` |
| TrueType Collection | `ttc` | `font/collection` |
| TrueType font | `ttf` | `font/ttf` |
| WOFF | `woff` | `font/woff` |
| WOFF2 | `woff2` | `font/woff2` |

## Executables and bytecode

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| Java bytecode | `class` | `application/java-vm` |
| Chrome extension package | `crx` | `application/x-google-chrome-extension` |
| Executable and Linkable Format | `elf` | `application/x-elf` |
| Identified Portable Executable (PE) | `exe` | `application/vnd.microsoft.portable-executable` |
| Other MZ binary | `exe` | `application/x-msdownload` |
| Lua bytecode | `luac` | `application/x-lua-bytecode` |
| Mach-O, thin 32/64-bit or universal binary | `macho` | `application/x-mach-binary` |
| NES ROM | `nes` | `application/x-nintendo-nes-rom` |
| Adobe Flash | `swf` | `application/x-shockwave-flash` |
| WebAssembly binary | `wasm` | `application/wasm` |

## Markup and structured text

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| GPS Exchange Format XML | `gpx` | `application/gpx+xml` |
| HTML recognized from a document element or doctype | `html` | `text/html` |
| iCalendar with a BEGIN:VCALENDAR header | `ics` | `text/calendar` |
| Keyhole Markup Language XML | `kml` | `application/vnd.google-earth.kml+xml` |
| ASCII-armored PGP message | `pgp` | `application/pgp-encrypted` |
| RDF/XML or recognized XMP metadata | `rdf` | `application/rdf+xml` |
| Windows .reg | `reg` | `application/x-ms-regedit` |
| RSS 2.0 feed | `rss` | `application/rss+xml` |
| Scalable Vector Graphics | `svg` | `image/svg+xml` |
| vCard with a BEGIN:VCARD header | `vcf` | `text/vcard` |
| WebVTT captions | `vtt` | `text/vtt` |
| XML (generic) | `xml` | `application/xml` |

## Data, databases, CAD and 3D

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| Apache Arrow file | `arrow` | `application/vnd.apache.arrow.file` |
| Apache Avro | `avro` | `application/avro` |
| Blender scene | `blend` | `application/x-blender` |
| Draco 3D mesh | `drc` | `application/vnd.google.draco` |
| AutoCAD DWG (R13+) | `dwg` | `image/vnd.dwg` |
| Autodesk FBX (binary) | `fbx` | `application/x.autodesk.fbx` |
| g3drem vendor-specific data marker | `g3drem` | `application/octet-stream` |
| glTF 2.0 binary | `glb` | `model/gltf-binary` |
| ICC color profile | `icc` | `application/vnd.iccprofile` |
| Meta Information Encapsulation | `mie` | `application/x-mie` |
| Apache Parquet | `parquet` | `application/vnd.apache.parquet` |
| PCAP, microsecond or nanosecond timestamps | `pcap` | `application/vnd.tcpdump.pcap` |
| PCAP Next Generation | `pcapng` | `application/x-pcapng` |
| ESRI Shapefile main file | `shp` | `application/x-esri-shape` |
| SketchUp model | `skp` | `application/vnd.sketchup.skp` |
| SQLite DB | `sqlite` | `application/x-sqlite3` |
| ASCII STL with a solid header | `stl` | `model/stl` |
| Silhouette Studio v3 | `studio3` | `application/octet-stream` |

## System and other formats

| Format | Returned extension | Detected MIME type |
| --- | --- | --- |
| macOS alias | `alias` | `application/x.apple.alias` |
| Windows registry hive | `dat` | `application/x-ft-windows-registry-hive` |
| Windows shortcut | `lnk` | `application/x.ms.shortcut` |
| Custom/proprietary marker | `unicorn` | `application/unicorn` |

## Preferred names and MIME aliases

The detection tables show raw results. The following names change when using
`MimeDetector::getPreferredMimeType()`:

| Raw detection result | Preferred result |
| --- | --- |
| `application/x-deb` | `application/vnd.debian.binary-package` |
| `application/x-rar-compressed` | `application/vnd.rar` |
| `application/x-sqlite3` | `application/vnd.sqlite3` |
| `audio/opus` | `audio/ogg` |
| `audio/x-flac` | `audio/flac` |
| `audio/x-m4a` | `audio/mp4` |
| `image/vnd.ms-photo` | `image/jxr` |
| `image/x-icon` | `image/vnd.microsoft.icon` |
| `video/x-m4v` | `video/mp4` |
| `video/x-matroska` | `video/matroska` |
| `video/x-ms-asf` | `application/vnd.ms-asf` |

The Ogg Opus conversion is specific to files detected with extension `opus`.
`MimeTypeAliases::preferred('audio/opus')` itself retains `audio/opus`, and
`MimeTypeAliases::equivalent('audio/opus', 'audio/ogg')` returns `false`.

For ordinary alternative spellings, use `MimeTypeAliases::equivalent()`:

```php
use SoftCreatR\MimeDetector\MimeTypeAliases;

MimeTypeAliases::equivalent('image/bmp', 'image/x-ms-bmp');             // true
MimeTypeAliases::equivalent('application/gzip', 'application/x-gzip'); // true
MimeTypeAliases::equivalent('audio/vnd.wave', 'audio/x-wav');          // true
```

Version 5.3.0 recognizes 131 alternate names across 72 reviewed format groups.
Preferred names can be registered or conventional; normalization is not an IANA
registration check. Charset parameters are ignored when comparing names; other
parameters must agree, preserving their value case.

Container and subtype relationships remain separate. APNG/PNG, camera RAW/TIFF,
Opus/Ogg, TTF/OTF/SFNT, and generic compound files/MSI are distinct comparisons.
An application can accept an appropriate specific-to-container relationship while
retaining the specific detected MIME name for its allowlist.

See the [MIME compatibility audit](https://github.com/SoftCreatR/php-mime-detector/blob/5.3.0/docs/mime-compatibility.md)
for the complete alias table, exclusions and source references.

## Extension lookup catalogue

The bundled repository contains 214 MIME names, including alternative spellings
and lookup-only registrations. This count includes several names for some formats.
A lookup result describes a MIME/extension association; it does not establish that
the default pipeline independently detects that format or emits every associated name.

```php
use SoftCreatR\MimeDetector\MimeTypeRepository;

$repository = MimeTypeRepository::createDefault();

$repository->getMimeTypesForExtension('mp4'); // ['audio/mp4', 'video/mp4']
$repository->getMimeTypesForExtension('msi'); // ['application/x-msi']
$repository->getExtensionsForMimeType('image/x-ms-bmp'); // ['bmp']

$catalogue = $repository->all(); // [registered MIME name => list of extensions]
```

`MimeDetector::listAllMimeTypes()` exposes the same registered catalogue.
Lookups by equivalent MIME names merge, deduplicate and sort the associated
extensions. `all()` retains the registered spellings.

Three filename extensions appear in the default lookup catalogue without being
returned as separate detection extensions:

- `aiff` and `aifc`: recognized AIFF/AIFF-C files return `aif`.
- `msi`: the default pipeline has no dedicated MSI identification. A compound
  file without the recognized Excel or Visio markers returns `cfb` and
  `application/x-cfb`.

Lookups by `audio/mpeg` return `mp1` first because extensions are sorted.

## Variant coverage and optional features

### Optional ZIP inspection

With `ext-zip`, the detector opens ZIP archives and inspects `mimetype`,
`[Content_Types].xml`, and selected entry names. This enables subtype recognition
when the relevant metadata is compressed or lies beyond the initial buffer.

Without `ext-zip`, it searches the buffered ZIP prefix for supported MIME and
entry-name markers. Files whose markers are absent from that prefix can return
`zip` and `application/zip`. Installing `ext-zip` improves coverage for the ZIP
subtypes listed above.

Pages, Numbers and Keynote detection uses the iWork archive inspector and requires
`ext-zip`. It covers recognized current ZIP-based document structures; older bundle
formats are outside these checks.

### Images and camera RAW

- APNG is recognized from a complete `acTL` chunk before image data within the
  buffered PNG prefix. An animation marker beyond that prefix can leave the result as PNG.
- ARW, DNG and NEF recognition uses selected TIFF metadata. Coverage depends on
  the camera/file variant and whether identifying tags are available in the buffer;
  another TIFF-based RAW file can retain a generic TIFF result.
- HEIF/HEIC results depend on the recognized major brand. Still images and sequences
  have separate HEIF/HEIC MIME results. Both `avif` and `avis` brands currently return `image/avif`.
- KTX coverage is for the version 1 signature.

### Audio and video

- MP4-family identification uses recognized container brands; it does not inspect
  every track to determine whether the file contains audio, video or both.
- A normal `M4A ` brand returns `m4a` and `audio/x-m4a`; a fallback M4A marker can
  return `audio/mp4`. Both names normalize to `audio/mp4`.
- AAC ADTS frames return `aac` and `audio/aac`. They are separate from MP4 audio containers.
- ASF checks recognizable stream-type identifiers to choose WMA or WMV; otherwise
  it returns the generic ASF result.
- Ogg uses the first page's segment table to find the initial packet. Codec-specific
  results require a beginning-of-stream page. Unknown or continued streams can return
  `ogx` and `application/ogg`; checks do not verify the full stream or its CRCs.
- Matroska/WebM DocType inspection requires complete EBML header elements within
  the first 4096 buffered bytes. Malformed or oversized headers can remain unidentified.

### Documents, text and data

- Legacy compound-file recognition includes specific Excel and Visio signatures.
  Other OLE files, including files named `.doc`, `.ppt` or `.msi`, can return generic CFB.
- Markup checks use a recognizable document element, doctype or declaration in the
  buffered prefix. UTF-16 XML declarations with a byte-order mark return generic XML;
  the detector does not classify their root elements into SVG, KML or other subtypes.
- PGP detection covers the ASCII-armored message header.
- STL detection covers an ASCII `solid ` header; there is no dedicated binary STL check.
- `g3drem` and `studio3` have recognized format markers but return `application/octet-stream`.

### Executables and disk images

- PE identification checks the signature at the DOS header's declared offset;
  other MZ files return `application/x-msdownload`.
- Mach-O recognition includes thin 32/64-bit signatures and universal headers
  with 32/64-bit FAT tables. Universal Mach-O and Java class files are distinguished.
- ISO coverage checks an ISO 9660 volume descriptor. DMG coverage checks the UDIF trailer.
- These checks and ID3 metadata skipping can perform bounded seeks beyond the initial buffer.

## Fixture coverage

The [published fixture corpus](https://github.com/SoftCreatR/mime-detector-fixtures/tree/1598b78)
contains examples producing 204 of the 215 extension/MIME output pairs listed
in this reference. The following implemented output pairs have no matching file
in that corpus snapshot:

| Returned extension | Detected MIME type without a matching corpus sample |
| --- | --- |
| `asf` | `video/x-ms-asf` |
| `exe` | `application/x-msdownload` |
| `heic` | `image/heic-sequence` |
| `m4a` | `audio/mp4` |
| `potm` | `application/vnd.ms-powerpoint.template.macroenabled.12` |
| `ppsm` | `application/vnd.ms-powerpoint.slideshow.macroenabled.12` |
| `pptm` | `application/vnd.ms-powerpoint.presentation.macroenabled.12` |
| `vstx` | `application/vnd.ms-visio.template.main+xml` |
| `xls` | `application/vnd.ms-excel` |
| `xlsm` | `application/vnd.ms-excel.sheet.macroenabled.12` |
| `xltm` | `application/vnd.ms-excel.template.macroenabled.12` |

Corpus presence records an exercised detection result. It does not prove coverage
of every valid variant or complete validation of the file's contents. The
[explicit fixture expectations](https://github.com/SoftCreatR/php-mime-detector/blob/5.3.0/tests/SoftCreatR/MimeDetector/KnownFixtureDetectionTest.php)
and detector tests provide regression coverage; generated audit fixtures include
source references, generators and hashes in the fixture repository.

For development comparisons against your installed `fileinfo` database:

```sh
php tools/compare-fileinfo.php
```

That tool requires `ext-fileinfo`. The runtime library requires PHP 8.1+ and
`ext-ctype`, with `ext-zip` optional. Tests, fixtures and development tools are
excluded from Composer distribution archives.

## Maintaining this reference

This page mirrors [docs/supported-file-types.md](https://github.com/SoftCreatR/php-mime-detector/blob/main/docs/supported-file-types.md)
in the package repository. Update the source document and wiki together when
releasing changes to the default pipeline. Review literal results, dynamic MIME
maps, extension registrations and fixture expectations; a catalogue addition
alone should not add a detection claim.
