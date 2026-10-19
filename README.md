# Findle - Laravel Page View Analytics Package

Findle is a GDPR-friendly page view tracking and analytics package for Laravel applications. It provides comprehensive page view tracking with bot detection and a beautiful statistics dashboard.

## Features

- 🔒 **GDPR Friendly**: Uses hashed visitor identifiers instead of storing personal data
- 🤖 **Bot Detection**: Advanced bot detection to filter out non-human traffic
- 📊 **Statistics Dashboard**: Beautiful overview and detailed analytics pages
- ⚡ **Performance Optimized**: Efficient tracking with rate limiting
- 🛡️ **Security Focused**: Configurable authorization and excluded routes
- 🎨 **Responsive UI**: Mobile-friendly statistics interface

## Requirements

- PHP 8.0 or higher (tested up to PHP 8.5)
- Laravel 9, 10, 11, 12 or 13

## Installation

1. Install the package via Composer:

```bash
composer require dennenboom/findle
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=findle-config
```

3. Run the migration:

```bash
php artisan migrate
```

4. (Optional) Publish the views for customization:

```bash
php artisan vendor:publish --tag=findle-views
```

## Configuration

The package automatically registers its middleware and routes. You can customize the behavior by editing the `config/findle.php` file:

```php
return [
    // Enable or disable tracking
    'enabled' => env('FINDLE_ENABLED', true),
    
    // Statistics route (default: /admin/statistics)
    'route' => env('FINDLE_ROUTE', '/admin/statistics'),
    
    // Authorization check function
    'middleware_check' => function ($user) {
        return $user && method_exists($user, 'is_admin') ? $user->is_admin : false;
    },
    
    // Routes to exclude from tracking
    'exclude_routes' => [
        '/admin/*',
        '/findle/*',
        '/_ignition/*',
    ],
    
    // Bot detection settings
    'bot_detection' => env('FINDLE_BOT_DETECTION', true),
    'rate_limit' => env('FINDLE_RATE_LIMIT', 30),
    
    // Data retention (days, 0 = forever)
    'data_retention_days' => env('FINDLE_DATA_RETENTION_DAYS', 365),
    
    // CDN Assets
    'assets' => [
        'tailwind_css' => env('FINDLE_TAILWIND_CSS_URL', 'https://cdn.tailwindcss.com'),
        'chartjs' => env('FINDLE_CHARTJS_URL', 'https://cdn.jsdelivr.net/npm/chart.js'),
    ],
];
```

## Usage

### Automatic Tracking

Once installed, Findle automatically tracks page views for all GET requests that aren't excluded. The middleware is automatically registered with the `web` middleware group.

### Manual Middleware Registration

If you need more control, you can manually register the middleware:

```php
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Dennenboom\Findle\Middleware\TrackPageViews::class,
    ],
];
```

### Accessing Statistics

By default, statistics are available at `/admin/statistics`. You can customize this route in the configuration file.

The statistics page includes:
- Total pages tracked
- Unique visitors count
- Total visits
- Daily, weekly, and monthly visit counts
- Sortable and searchable page list
- Detailed page analytics with charts

### Authorization

By default, only users with an `is_admin` method that returns `true` can access the statistics. You can customize this by modifying the `middleware_check` function in the configuration.

## Environment Variables

```env
FINDLE_ENABLED=true
FINDLE_ROUTE=/admin/statistics
FINDLE_BOT_DETECTION=true
FINDLE_RATE_LIMIT=30
FINDLE_DATA_RETENTION_DAYS=365
FINDLE_TABLE_NAME=page_views

# CDN Assets (optional)
FINDLE_TAILWIND_CSS_URL=https://cdn.tailwindcss.com
FINDLE_CHARTJS_URL=https://cdn.jsdelivr.net/npm/chart.js
```

## Bot Detection

Findle includes comprehensive bot detection that filters out:
- Search engine crawlers (Google, Bing, etc.)
- Social media crawlers (Facebook, Twitter, etc.)
- SEO tools and scanners
- Monitoring and testing tools
- Security scanners
- Academic/research crawlers

## GDPR Compliance

Findle is designed with privacy in mind:
- Uses SHA-256 hashed visitor identifiers instead of storing IP addresses
- No personal data is stored
- Data retention settings allow automatic cleanup
- User agents are stored for analytics but can be disabled

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
