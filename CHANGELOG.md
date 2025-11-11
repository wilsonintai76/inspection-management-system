# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project adheres (informally) to Semantic Versioning.

## [Unreleased]
- Future improvements: pagination, storage dashboard, indexing, cleanup utilities, enhanced security, export/archival, rate limiting, external storage, Apache hardening.

## [1.2.0] - 2025-11-11
### Added
- Support for government multi-line asset inspection CSV format in `department-bulk-import.php` for extracting departments, locations, supervisors.
- Dual upload modes: Initial Setup vs Annual Reset (Replace All) in Departments bulk import UI.
- Comprehensive documentation:
  - `ASSET_UPLOAD_FORMATS.md` (standard vs multi-line formats)
  - `CSV_IMPORT_ENHANCEMENT.md`
  - `CSV_QUICK_REFERENCE.md`
  - `MID_YEAR_UPDATE_GUIDE.md`
  - `FILE_UPLOAD_ERROR_GUIDE.md`
  - Example CSV files for departments and assets.
- Error troubleshooting guide for bulk uploads.

### Changed
- Refined destructive confirmation dialogs (annual reset) in `AssetManagement.vue` and `Departments.vue`.
- Restored simpler CSV parser in `asset-uploads.php` to avoid misinterpreting department bootstrapping files.
- Improved supervisor handling: name lookup with graceful fallback.
- Clarified overwrite (Replace All) semantics: full database reset including departments, locations, inspections.

### Fixed
- Removed stray braces causing parse errors in `department-bulk-import.php` during format detection integration.
- Ensured multi-file batch import consistently merges rows and deduplicates departments/locations.

### Security / Stability
- Added stricter column validation and clearer error messages when required headers are missing.

## [1.1.0] - 2025-11-05
### Added
- Modern full-page upload UI for asset and department imports (replacing legacy modal).
- Overwrite mode (full reset) for annual data refresh.

### Changed
- Flexible supervisor lookup by name (no longer requires staff ID in CSV).
- Column alias support (Bahagian/Jabatan, Lokasi Terkini/Lokasi, Pegawai Penempatan/Pegawai Penyelia).

## [1.0.0] - 2025-10-20
### Added
- Initial release: core inspection management backend (PHP/MySQL) and Vue 3 frontend.
- Basic department/location CRUD and asset inspection upload.

[Unreleased]: https://github.com/wilsonintai76/inspection-management-system/compare/main...HEAD
[1.2.0]: https://github.com/wilsonintai76/inspection-management-system/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/wilsonintai76/inspection-management-system/compare/1.0.0...1.1.0
