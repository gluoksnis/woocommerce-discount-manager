# WooCommerce Discount Manager

A powerful WordPress plugin for managing WooCommerce product discounts via CSV upload or category selection, with bulk operations support.

## Features

- **Bulk Discount Management**: Apply discounts to multiple products via CSV upload or by category
- **Scheduled Discounts**: Set start and end dates for discount campaigns
- **Bulk Operations**: Remove discounts from multiple products at once
- **Category Filtering**: Filter discounted products by category
- **SKU Tag Assignment**: Assign product tags based on SKU or category
- **Cloudflare Cache Integration**: Optional cache purging after discount operations
- **Safe Operations**: Skip products with existing discounts option

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

### From GitHub

1. Download the plugin from GitHub
2. Upload the `woocommerce-discount-manager` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to the "Nuolaidos" menu in WordPress admin

### Manual Installation

1. Clone this repository: `git clone https://github.com/yourusername/woocommerce-discount-manager.git`
2. Upload to `/wp-content/plugins/`
3. Activate in WordPress

## Usage

### Adding Discounts

1. Navigate to **Nuolaidos** in WordPress admin
2. Choose one of two methods:
   - **CSV Upload**: Upload a CSV file with SKU codes (one per line)
   - **Category Selection**: Select a product category from dropdown
3. Enter discount percentage (1-100%)
4. Set start and end dates (optional)
5. Check "Skip products with existing discounts" if needed
6. Check "Clear Cloudflare cache" if you have Cloudflare integration
7. Click "Update Prices"

### Viewing Discounted Products

1. Navigate to **Nuolaidos → Prekės su nuolaida**
2. View all products with active discounts
3. Filter by category using the dropdown
4. Use bulk actions to remove discounts from multiple products
5. See discount schedules for each product

### Removing Discounts

1. Navigate to **Nuolaidos → Ištrinti nuolaidas**
2. Choose one of three methods:
   - **CSV Upload**: Upload a CSV with SKUs to clear
   - **Category Selection**: Clear discounts from specific category
   - **Clear All**: Remove all discounts from all products
3. Check "Clear Cloudflare cache" if needed
4. Click "Update Prices"

### SKU Tag Assignment

1. Navigate to **Nuolaidos → Priskirti TAG**
2. Choose method (CSV or Category)
3. Select the tag to assign
4. Click "Assign Product Tags"

## CSV Format

CSV files should contain one SKU per line:

```
SKU001
SKU002
SKU003
```

## Cloudflare Integration

If you have a Cloudflare plugin installed that provides a `flush_cloudflare_cache()` function, you can optionally purge the cache after discount operations by checking the "Clear Cloudflare cache" checkbox.

## Filters and Hooks

The plugin uses standard WordPress and WooCommerce hooks. No custom filters are currently exposed, but you can extend the classes as needed.

## Product Meta Fields

The plugin uses the following custom meta fields:

- `_nuolaida_pr`: Stores the discount percentage
- Standard WooCommerce fields: `_sale_price`, `_sale_price_dates_from`, `_sale_price_dates_to`

## Security

- All forms use WordPress nonces for CSRF protection
- User capabilities are checked (requires `manage_options`)
- All inputs are sanitized and escaped
- SQL queries use prepared statements

## Translation

The plugin is translation-ready with the text domain `wc-discount-manager`. Lithuanian translations are included by default.

## Support

For issues, questions, or contributions, please visit:
- GitHub Issues: [https://github.com/yourusername/woocommerce-discount-manager/issues](https://github.com/yourusername/woocommerce-discount-manager/issues)

## Changelog

### 1.0.0
- Initial release
- Bulk discount management via CSV or category
- Scheduled discounts
- Product list with filtering
- Bulk discount removal
- SKU tag assignment
- Cloudflare cache integration

## License

GPL v2 or later - [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Credits

Developed for managing WooCommerce discounts efficiently across large product catalogs.

## Screenshots

_(Add screenshots here when available)_

1. Main discount management page
2. Products with discounts list view
3. Delete discounts interface
4. SKU tag assignment page
