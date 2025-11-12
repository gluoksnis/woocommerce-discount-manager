# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-12

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
