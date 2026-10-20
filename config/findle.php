<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable page view tracking globally.
    |
    */
    'enabled' => env('FINDLE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Statistics Route
    |--------------------------------------------------------------------------
    |
    | The route where the statistics overview will be available.
    |
    */
    'route' => env('FINDLE_ROUTE', '/admin/statistics'),

    /*
    |--------------------------------------------------------------------------
    | Middleware Authorization Check
    |--------------------------------------------------------------------------
    |
    | Function to check if user is authorized to view statistics.
    | By default, checks if user->is_admin is true.
    |
    */
    'middleware_check' => function ($user) {
        return $user && method_exists($user, 'is_admin') ? $user->is_admin : false;
    },

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should be excluded from tracking.
    | By default excludes admin routes.
    |
    */
    'exclude_routes' => [
        '/admin/*',
        '/findle/*',
        '/_ignition/*',
        '/telescope/*',
        '/horizon/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table to store page views.
    |
    */
    'table_name' => env('FINDLE_TABLE_NAME', 'page_views'),

    /*
    |--------------------------------------------------------------------------
    | Visits Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table that stores every single visit of a page view.
    |
    */
    'visits_table_name' => env('FINDLE_VISITS_TABLE_NAME', 'page_view_visits'),

    /*
    |--------------------------------------------------------------------------
    | Bot Detection
    |--------------------------------------------------------------------------
    |
    | Enable or disable bot detection to filter out non-human traffic.
    |
    */
    'bot_detection' => env('FINDLE_BOT_DETECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum requests per minute per IP to prevent spam.
    |
    */
    'rate_limit' => env('FINDLE_RATE_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep page view data. Set to 0 to keep forever.
    |
    */
    'data_retention_days' => env('FINDLE_DATA_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | CDN Assets
    |--------------------------------------------------------------------------
    |
    | CDN URLs for external assets. If not provided, will use default CDN URLs.
    |
    */
    'assets' => [
        'tailwind_css' => env('FINDLE_TAILWIND_CSS_URL', 'https://cdn.tailwindcss.com'),
        'chartjs' => env('FINDLE_CHARTJS_URL', 'https://cdn.jsdelivr.net/npm/chart.js'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JavaScript Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for client-side JavaScript tracking to avoid bot traffic.
    |
    */
    'js_tracking' => [
        'enabled' => env('FINDLE_JS_TRACKING_ENABLED', false),
        'token_lifetime' => env('FINDLE_JS_TOKEN_LIFETIME', 300), // 5 minutes
        'rate_limit' => env('FINDLE_JS_RATE_LIMIT', 60), // requests per minute per IP
        'async_load' => env('FINDLE_JS_ASYNC_LOAD', true),
        'require_interaction' => env('FINDLE_JS_REQUIRE_INTERACTION', true), // only count visitors that use mouse, touch or keyboard
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Method
    |--------------------------------------------------------------------------
    |
    | Choose between 'middleware' (server-side) or 'javascript' (client-side).
    | JavaScript tracking is more accurate as it avoids bot traffic.
    |
    */
    'tracking_method' => env('FINDLE_TRACKING_METHOD', 'middleware'),

    /*
    |--------------------------------------------------------------------------
    | Reference Parameters
    |--------------------------------------------------------------------------
    |
    | Query parameters that identify where a visitor came from, e.g. ?ref=newsletter.
    | The first parameter found in the URL is stored as the reference.
    |
    */
    'reference_parameters' => [
        'ref',
        'referral',
        'utm_ref',
        'utm_source',
        'utm_medium',
        'referral',
        'source',
        'campaign',
    ],
];
