# Changelog

## 5.3.0

### Added

- Detect QOI, DDS, OpenEXR, DjVu, JPEG-LS, Netpbm bitmap/graymap/pixmap,
  PCAPNG, nanosecond PCAP, lzop, zlib, AAC ADTS, MPEG layer I, SketchUp,
  32-bit and universal Mach-O, and specifically identified PE executables.
- Detect Core Audio Format (CAF), AMR-WB, and multi-channel AMR/AMR-WB files;
  include AIFF-C and `.aiff`/`.aifc` extension lookups.
- Audit the entire MIME catalogue against libmagic, shared-mime-info,
  WebKit, and IANA definitions. Support 131 alternate names across 72
  format groups, including historical libmagic spellings.
- Add fixture provenance, explicit expectations for the new detections,
  malformed-header regressions, and a development tool to compare the corpus
  with the locally installed `fileinfo` database.
- Add reproducible media fixture generators and Composer `format`/`format-check`
  commands. CI checks the fixer configuration and formatting on PHP 8.1.

### Fixed

- Resolve MIME aliases in extension lookups regardless of which spelling
  was registered, merging extensions registered under equivalent names.
- Preserve significant MIME parameter values, including codec identifier
  case, quoted separators, and escaped characters.
- Identify AAC and FLAC after ID3 metadata rather than assuming every ID3 tag
  belongs to MP3. Reject malformed tag lengths and reserved MPEG header fields.
- Prevent UTF-16 XML and registry exports from matching loose MPEG signatures.
- Check the UDIF trailer when identifying Apple disk images. A zlib header alone
  now identifies a zlib stream, not a DMG.
- Require the full SQLite 3 signature instead of accepting any `SQLi` prefix.
- Distinguish universal Mach-O from Java class files sharing `CAFEBABE` magic.
- Correct the MP4 lookup to `audio/mp4` and `video/mp4`.
- Read Ogg codec markers at the packet offset declared by the segment table,
  only on beginning-of-stream pages. Unrelated `OpusHead` bytes no longer
  identify a file as Opus.
- Parse Matroska/WebM DocType inside the bounded EBML header, supporting
  variable-width sizes and rejecting truncated headers, duplicate DocType
  elements, and markers embedded in unrelated data.
- Require an `AIFF` or `AIFC` form type instead of identifying every IFF file
  beginning with `FORM` as AIFF audio.

### Upgrade notes

Detected PE executables now return `application/vnd.microsoft.portable-executable`;
other MZ files retain `application/x-msdownload`. JPEG-LS returns `image/jls`
instead of `image/jpeg`. AAC returns `audio/aac` with extension `aac`, and MPEG
layer I returns extension `mp1`. Tagged FLAC now returns `audio/x-flac`. Review
allowlists that depended on the previous broad results or on ID3 alone.

Opt-in preferred names now include `application/vnd.sqlite3`, `video/matroska`,
and `image/jxr`. Alias extension lookups return the sorted union of extensions
registered under equivalent names; `all()` keeps the original registrations.
Adding `mp1` makes it the first extension returned for `audio/mpeg`.

Format-specific and generic names remain distinct. See the
[MIME compatibility audit](docs/mime-compatibility.md) for coverage and exclusions.

## 5.2.0

### Added

- Detect APNG, selected TIFF-based camera RAW formats (Sony ARW, Adobe DNG,
  Nikon NEF), ISO 9660 images, Apple Pages/Numbers/Keynote archives, WMA, and
  more specific XML formats including KML and GPX.
- Add `MimeDetector::getPreferredMimeType()` and `MimeTypeAliases` for comparing
  known alternate MIME names. The alias list is intentionally conservative;
  review differences before applying an upload policy.
- Fill gaps in the MIME-to-extension catalogue so detected types can be looked
  up in both directions.

### Changed

- APNG files now return `image/apng` instead of `image/png`. Recognized ARW,
  DNG, and NEF files return their specific MIME types instead of `image/tiff`.
- WMA files now return `audio/x-ms-wma` instead of `video/x-ms-wmv`. ASF files
  without an identified audio or video stream return `video/x-ms-asf`.
- Unidentified OLE compound files now return `application/x-cfb` instead of
  being assumed to be MSI installers. The old result could allow an unrelated
  compound file through an MSI-specific allowlist.
- File hashes are calculated when requested, instead of during file
  registration. A file removed or made unreadable after registration can now
  cause `getFileHash()` to throw.

### Upgrade notes

If your application checks `getMimeType()` or `getFileExtension()` against an
allowlist, review the newly distinguished types above before upgrading. In
particular, add `image/apng` only when animated images are permitted, and do
not treat generic OLE compound files as installers. The library identifies
format signatures; it does not validate a complete file or decide whether that
file is safe to accept.

`getPreferredMimeType()` is an opt-in spelling helper. It returns `audio/ogg`
for detected Ogg Opus files while `getMimeType()` retains the legacy
`audio/opus` result. It does not guarantee that every preferred name is IANA
registered.
