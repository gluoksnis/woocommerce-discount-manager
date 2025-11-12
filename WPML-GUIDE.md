# WPML Compatibility Guide

## Overview

The WooCommerce Discount Manager now includes **full WPML support** to handle multilingual e-commerce sites correctly.

## The Problem (Before)

When you applied discounts in one language (e.g., Lithuanian):
- ❌ Discounts only applied to products in that language
- ❌ English products showed no discount
- ❌ Lithuanian products showed incorrect prices (60€ ~~60€~~)
- ❌ Had to manually apply discounts in each language separately

## The Solution (Now)

✅ **Automatic Detection**: Plugin detects WPML and finds original language products  
✅ **Cross-Language Sync**: Discounts automatically apply to ALL language versions  
✅ **One Operation**: Apply once, works everywhere  
✅ **Price Consistency**: Correct pricing across all languages  

---

## How It Works

### 1. **Original Product Detection**
When you apply a discount in any language, the plugin:
```
Lithuanian Product (ID: 456) 
    ↓ [Finds Original]
English Product (ID: 123) ← Original
    ↓ [Applies to All]
- English (ID: 123) ✓
- Lithuanian (ID: 456) ✓
- German (ID: 789) ✓
```

### 2. **Translation Synchronization**
```php
Apply discount to SKU-001
    → Find original product (English)
    → Get all translations (EN, LT, DE, etc.)
    → Apply discount to each translation
    → Result: Consistent pricing everywhere
```

### 3. **Batch Processing**
For large catalogs with thousands of products:
```
1000 products × 3 languages = 3000 product updates
    → Processed in batches of 50
    → Progress tracking
    → No timeouts
    → Estimated time shown
```

---

## Usage Examples

### Example 1: CSV Upload (Any Language)

**Before WPML Support:**
```
1. Upload CSV in Lithuanian admin → Only LT products get discount
2. Switch to English admin → Manually upload same CSV again
3. Switch to German admin → Manually upload same CSV again
❌ Time-consuming and error-prone
```

**With WPML Support:**
```
1. Upload CSV in ANY language (LT, EN, DE - doesn't matter!)
2. Plugin automatically applies to ALL languages
✅ Done! One operation, all languages covered
```

### Example 2: Category Discounts

**Before:**
```
- Admin in Lithuanian → Select category → Apply 20%
- Result: Only Lithuanian products discounted
- Must repeat for each language
```

**After:**
```
- Admin in ANY language → Select category → Apply 20%
- Plugin finds original products
- Applies to all language versions automatically
✅ All languages updated simultaneously
```

---

## Visual Indicators

### WPML Status Message
When WPML is active, you'll see this message on discount pages:

```
ℹ️ WPML is active. Discounts will be applied to all language versions: en, lt, de
```

### Success Messages
After applying discounts:

```
✅ Nuolaidos pritaikytos sėkmingai! Processed 150 products.
   (Applied to all language versions)
```

---

## Technical Details

### How Plugin Handles WPML

#### 1. Product ID Resolution
```php
// User uploads SKU in Lithuanian site
SKU: ABC-123 → Lithuanian Product ID: 456

// Plugin automatically finds original
Original Product ID: 123 (English)

// Gets all translations
Translation IDs: [123, 456, 789] (EN, LT, DE)
```

#### 2. Discount Application
```php
foreach (translation_ids as $id) {
    Apply discount to product $id
    Set sale price
    Set schedule dates
    Save product
}
```

#### 3. Duplicate Prevention
```php
// Prevents processing same product multiple times
Input: [123, 456, 789, 123, 456] (duplicates)
After deduplication: [123, 456, 789] (unique)
```

---

## Batch Processing

### When It Activates
- **Automatically enabled** for more than 100 products
- Shows estimated processing time
- Processes in batches of 50 products

### Progress Indicators
```
ℹ️ Processing 1,500 products in batches of 50. Estimated time: 30 seconds
```

### What It Does
```
Batch 1:  Products 1-50    → Process → Wait 0.1s
Batch 2:  Products 51-100   → Process → Wait 0.1s
Batch 3:  Products 101-150  → Process → Wait 0.1s
...
Batch 30: Products 1451-1500 → Process → Done!
```

### Benefits
✅ **No Timeouts**: Handles thousands of products  
✅ **Server Friendly**: Small pauses prevent overload  
✅ **Progress Tracking**: See how many processed  
✅ **Error Recovery**: Continues even if some products fail  

---

## Troubleshooting

### Q: I'm on Lithuanian admin, will discounts work in English?
**A:** Yes! The plugin automatically applies discounts to all language versions regardless of which admin language you're using.

### Q: Do I still need to switch languages?
**A:** No! Apply once from any language admin, and all translations are updated.

### Q: What if I have 10,000 products?
**A:** Batch processor handles this automatically. Processing will take ~3-4 minutes with progress shown.

### Q: Can I still apply discounts to specific language only?
**A:** No, the plugin now always syncs across languages to maintain price consistency. This is a better practice for multilingual sites.

### Q: What if WPML is not installed?
**A:** Plugin works normally as before. WPML features are optional and automatically detected.

---

## Best Practices

### ✅ DO:
- Apply discounts from any language admin you prefer
- Use batch processing for large product sets
- Check the WPML status message to confirm it's active
- Test on a few products first before bulk operations

### ❌ DON'T:
- Manually apply same discounts in each language (not needed!)
- Process extremely large CSV files (split into smaller batches)
- Skip the Cloudflare cache purge if you use Cloudflare
- Ignore batch processing warnings for large catalogs

---

## Performance Considerations

### Small Catalogs (<100 products)
- ⚡ Instant processing
- No batch processing needed
- Direct application

### Medium Catalogs (100-1000 products)
- 🔄 Automatic batch processing
- ~10-30 seconds processing time
- Progress indicators shown

### Large Catalogs (1000+ products)
- 🔄 Automatic batch processing
- Estimated time displayed
- Processes in background
- ~1-5 minutes typical

### Very Large Catalogs (10,000+ products)
- 🔄 Advanced batch processing
- May take several minutes
- Consider splitting into categories
- Monitor server resources

---

## Migration Guide

### From Old Plugin Version

If you previously applied discounts language by language:

1. **Clear existing discounts** from all languages
2. **Re-apply discounts** once (from any language)
3. **Verify** all language versions have correct prices
4. **Done!** Future discounts apply automatically

### From Manual WPML Management

If you were manually managing prices per language:

1. Set **original language prices** correctly
2. Use the **discount plugin** for promotions
3. Let **WPML sync** handle the rest
4. **Prices stay consistent** across languages

---

## FAQ

**Q: Does this work with WPML String Translation?**  
A: Yes, the plugin works with full WPML suite.

**Q: What about product variations?**  
A: Variations are handled separately but synced correctly.

**Q: Can I exclude certain languages?**  
A: Not currently. All languages are synced for consistency.

**Q: Does it work with WPML currency switcher?**  
A: Yes, discounts are percentage-based so they work with any currency.

**Q: What if I add a new language later?**  
A: Re-apply discounts and new language products will be included.

---

## Technical Support

### Check WPML Status
Look for this message on discount pages:
```
ℹ️ WPML is active. Discounts will be applied to all language versions: en, lt, de
```

### Debug Information
Enable WordPress debug mode to see batch processing logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `wp-content/debug.log` for entries like:
```
WCDM Batch 1: Processed 50/1500 products in 0.85 seconds
WCDM Batch 2: Processed 100/1500 products in 0.82 seconds
```

### Common Issues

**Issue: "Discounts not showing in all languages"**
- Check if WPML status message is displayed
- Verify products are properly linked in WPML
- Re-apply discounts

**Issue: "Processing takes too long"**
- Normal for large catalogs
- Check batch processing message for estimate
- Don't refresh page during processing

**Issue: "Some products missing discounts"**
- Check if products have translations set up
- Verify SKUs exist in original language
- Check WP debug log for errors

---

## Version Compatibility

- ✅ WPML 4.0+
- ✅ WooCommerce Multilingual 5.0+
- ✅ WordPress 5.8+
- ✅ WooCommerce 5.0+
- ✅ PHP 7.4+

---

**🎉 You can now manage multilingual discounts with confidence!**

The plugin automatically handles all the complexity of WPML synchronization, so you can focus on your sales and marketing.
