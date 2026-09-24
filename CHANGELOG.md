# Changelog

## Unreleased

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
