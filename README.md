# Rank212 SEO Client Package for Laravel (`rank212/laravel-seo`)

Zero-friction, headless AI SEO automation for Laravel e-commerce stores (WooCommerce, Shopify, custom Laravel stores).

---

## 📦 Features
- 🚀 **Zero Friction Setup**: Add the `HasDynamicSeo` trait to any Eloquent model (`Product`, `Post`, `Collection`, etc.).
- 🤖 **Automated AI Optimization**: Auto-generates high-converting Meta Titles, Meta Descriptions, Focus Keywords, OpenGraph Tags, and Schema.org JSON-LD via the Rank212 API.
- ⚡ **Asynchronous Background Sync**: Non-blocking queued jobs with transaction safety (`afterCommit`) when products are saved.
- 💾 **Local Zero-Latency Caching**: Caches metadata locally in the `seo_metadata` table to serve web requests instantaneously.
- 🛡️ **Hardened Security**: Built-in JSON-LD escaping against XSS and intelligent quota handling.
- 🎨 **Blade Component & Directive**: Inject SEO tags effortlessly using `<x-seo::tags :model="$product" />` or `@seoTags($product)`.
- 🛠️ **Artisan CLI**: Bulk sync existing store inventory with a single command: `php artisan seo:sync-all`.

---

## 🛠️ Installation

### 1. Require the Package
```bash
composer require rank212/laravel-seo
```

### 2. Publish Configuration & Migration
```bash
php artisan vendor:publish --tag=seo-config
php artisan vendor:publish --tag=seo-migrations
php artisan migrate
```

### 3. Configure Environment Variables
Add your credentials to `.env`:
```env
RANK212_API_KEY=seo_your_store_api_key_here
RANK212_API_URL=https://api.your-seo-saas.com
RANK212_AUTO_SYNC=true
RANK212_QUEUE=default
```

---

## 💡 Usage

### 1. Add Trait to Your Eloquent Model
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SeoSaas\LaravelSeo\Traits\HasDynamicSeo;

class Product extends Model
{
    use HasDynamicSeo;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'image_url',
    ];
}
```

### 2. (Optional) Customize Model SEO Getters
You can override any of the following methods to map custom attributes or logic:
```php
class Product extends Model
{
    use HasDynamicSeo;

    public function getSeoTitle(): string
    {
        return $this->name . ' - Handcrafted Luxury Fragrance';
    }

    public function getSeoCategory(): ?string
    {
        return $this->category?->name;
    }

    public function getSeoLanguage(): string
    {
        return 'ar'; // or 'fr', 'en'
    }
}
```

### 3. Inject SEO Tags in Blade Template
In your `resources/views/layouts/app.blade.php` or `product/show.blade.php`:

```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- Option A: Blade Component --}}
    <x-seo::tags :model="$product" />

    {{-- Option B: Blade Directive --}}
    {{-- @seoTags($product) --}}
</head>
<body>
    ...
</body>
</html>
```

### 4. Bulk Synchronize Store Products
To onboard and optimize existing store inventory:
```bash
# Synchronous with progress bar:
php artisan seo:sync-all --model="App\Models\Product"

# Background queue:
php artisan seo:sync-all --model="App\Models\Product" --queue

# Force refresh existing cached metadata:
php artisan seo:sync-all --model="App\Models\Product" --force
```

### 5. Manual Programmatic Sync
```php
// Synchronous:
$product->syncSeoNow(forceRefresh: true);

// Asynchronous background job:
$product->syncSeoAsync();

// Access metadata attributes:
$title = $product->getSeoMetaTitle();
$description = $product->getSeoMetaDescription();
$keywords = $product->getSeoFocusKeywords();
$schema = $product->getSeoSchemaJson();
```
