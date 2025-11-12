# WooCommerce Discount Manager - Installation Guide

## Quick Installation

### Option 1: Upload via WordPress Admin (Recommended)

1. Download `woocommerce-discount-manager.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the zip file
5. Click "Install Now"
6. Click "Activate Plugin"
7. You'll see "Nuolaidos" menu item in WordPress admin

### Option 2: Manual FTP Upload

1. Extract `woocommerce-discount-manager.zip`
2. Upload the `woocommerce-discount-manager` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "WooCommerce Discount Manager" and click "Activate"

### Option 3: GitHub Clone

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/yourusername/woocommerce-discount-manager.git
```

Then activate via WordPress admin.

## Post-Installation

After activation, you'll see a new menu item "Nuolaidos" (Discounts) in WordPress admin with four submenu pages:

1. **Nuolaidos** - Main page to add discounts
2. **Ištrinti nuolaidas** - Remove discounts
3. **Prekės su nuolaida** - View all discounted products
4. **Priskirti TAG** - Assign tags to products

## Requirements Check

Before using the plugin, ensure:

✅ WordPress 5.8 or higher  
✅ WooCommerce 5.0 or higher is active  
✅ PHP 7.4 or higher  
✅ User has administrator role (`manage_options` capability)

## Optional: Cloudflare Integration

If you want to use the Cloudflare cache purging feature:

1. Install a Cloudflare plugin that provides `flush_cloudflare_cache()` function
2. The checkbox will work automatically when the function is available
3. If no Cloudflare plugin is installed, the checkbox does nothing (safe to check or uncheck)

## Uninstallation

To remove the plugin:

1. Go to WordPress Admin → Plugins
2. Deactivate "WooCommerce Discount Manager"
3. Click "Delete"

**Note:** Product discounts and meta fields will remain in your database even after uninstalling. To remove all discounts before uninstalling:

1. Go to Nuolaidos → Ištrinti nuolaidas
2. Check "Clear all products"
3. Click "Update Prices"
4. Then uninstall the plugin

## Troubleshooting

### Plugin not showing in menu

- Ensure you're logged in as administrator
- Check that WooCommerce is active
- Try deactivating and reactivating the plugin

### CSV upload not working

- Check file format (one SKU per line, no headers)
- Ensure SKUs exist in your WooCommerce products
- Check file upload permissions in WordPress

### Discounts not applying

- Verify products have regular prices set
- Check that discount percentage is between 1-100
- Ensure dates are in correct format (Y-m-d)

## Support

For issues or questions:
- Check the README.md file
- Visit GitHub Issues: https://github.com/yourusername/woocommerce-discount-manager/issues
