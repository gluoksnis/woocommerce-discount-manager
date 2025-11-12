# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2025-11-12

### Added
- **HPOS Compatibility**: Declared compatibility with WooCommerce High-Performance Order Storage
- **Automatic Cloudflare Purge**: Auto-purges cache if Cloudflare plugin detected (no checkbox needed)
- **Multiple Cloudflare Plugin Support**: Works with various Cloudflare plugins automatically
- **Typesense Integration**: Proper WooCommerce API usage triggers search index updates

### Changed
- **Removed Cloudflare Checkbox**: Now auto-detects and purges automatically
- **WooCommerce API Usage**: All operations now use `$product->save()` instead of direct SQL
- **Better Hook Integration**: Discount operations properly trigger WooCommerce hooks
- **"Clear All" Improved**: Now uses batch processor with WooCommerce API instead of raw SQL queries

### Fixed
- **Typesense Reindexing**: Search results now update immediately when discounts change
- **HPOS Warning**: Plugin no longer shows incompatibility warning with High-Performance Order Storage
- **Hook Triggering**: Cache plugins, search plugins (Typesense), and other integrations now update correctly
- **Product Save**: All discount operations properly trigger WordPress/WooCommerce save hooks

### Technical Improvements
- Added comprehensive error handling for Cloudflare purge operations
- Multiple Cloudflare plugin detection (flush_cloudflare_cache, wp_cloudflare_purge_cache, cloudflare_purge_everything)
- Silent failure for missing Cloudflare - doesn't break operations
- Debug logging for troubleshooting (when WP_DEBUG enabled)

## [1.1.0] - 2025-07-02

### Added
- **WPML Support**: Full multilingual compatibility
  - Automatic detection of WPML installation
  - Cross-language synchronization of discounts
  - Original product detection and translation linking
  - One-operation applies to all language versions
- **Batch Processing System**: Handle thousands of products
  - Automatic batch processing for 100+ products
  - Progress tracking and estimated time display
  - Processes in batches of 50 products
  - Prevents server timeouts and overload
  - Error recovery and reporting
- Complete WPML documentation (WPML-GUIDE.md)
- Batch processor class with intelligent throttling
- Enhanced success messages showing processed count

### Changed
- Product processing now uses batch system for better performance
- CSV and category operations use batch processor automatically
- Improved duplicate detection for WPML products
- Better memory management for large product sets

### Fixed
- WPML multilingual sites now sync prices correctly across languages
- Products with translations no longer show inconsistent pricing
- Large product sets (1000+) no longer cause timeouts
- Category operations now deduplicate WPML translations

## [1.0.0] - 2024-08-25

### Added
- Initial release
- Bulk discount management via CSV upload
- Bulk discount management by category selection
- Scheduled discounts with start and end dates
- Products with discounts list view with pagination
- Category filter for discounted products
- Bulk actions to remove discounts from selected products
- Option to skip products with existing discounts
- Optional Cloudflare cache purging integration
- SKU-based tag assignment
- Category-based tag assignment
- Translation-ready with Lithuanian translations
- Security: Nonce verification and capability checks
- Proper WordPress plugin structure

### Features
- Apply discounts to products via CSV (SKU-based)
- Apply discounts to entire product categories
- View all discounted products in a sortable table
- Filter discounted products by category
- Bulk remove discounts from multiple products
- Clear all discounts with one click
- Assign product tags based on SKU or category
- Display discount schedules (From/To dates)
- Show discount percentage and price comparison

### Security
- WordPress nonce verification on all forms
- User capability checks (manage_options required)
- Input sanitization and output escaping
- SQL prepared statements where applicable