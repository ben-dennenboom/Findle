<?php

namespace Dennenboom\Findle\Middleware;

use Closure;
use Dennenboom\Findle\Models\PageView;
use Dennenboom\Findle\Support\BotDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!config('findle.enabled', true)) {
            return $response;
        }

        if (config('findle.tracking_method') === 'javascript') {
            return $response;
        }

        if (!$request->isMethod('GET')) {
            return $response;
        }

        if ($this->shouldExcludePath($request)) {
            return $response;
        }

        $fullUrl = $this->normalizeUrl($request->fullUrl());
        $userAgent = $request->userAgent() ?? '';
        $referrer = $request->headers->get('referer');

        if (config('findle.bot_detection', true) && $this->isBot($userAgent, $request)) {
            return $response;
        }

        if ($this->isRapidRequests($request)) {
            return $response;
        }

        $queryData = $request->query->all();
        $hash = $this->generateVisitorHash($request);

        PageView::recordView(
            $fullUrl,
            $hash,
            $userAgent,
            $referrer,
            $queryData,
        );

        return $response;
    }

    protected function shouldExcludePath(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');
        $excludedPaths = config('findle.exclude_routes', []);

        foreach ($excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isBot(string $userAgent, Request $request): bool
    {
        if (BotDetector::isBot($userAgent, $request)) {
            return true;
        }

        return $this->hasAutomatedRequestPattern($request);
    }

    protected function hasAutomatedRequestPattern(Request $request): bool
    {
        $path = $request->path();
        if (!$request->headers->get('referer') &&
            substr_count($path, '/') > 2 &&
            !in_array($path, ['/', '/blog', '/contact', '/about'])) {
            return true;
        }

        $suspiciousParams = ['test', 'scan', 'check', 'probe', 'admin', 'wp-admin', 'phpmyadmin'];
        foreach ($suspiciousParams as $param) {
            if ($request->has($param)) {
                return true;
            }
        }

        return false;
    }

    protected function isRapidRequests(Request $request): bool
    {
        $ip = $request->ip();
        $cacheKey = "findle_rate_limit_{$ip}";

        $requestCount = Cache::get($cacheKey, 0);
        $rateLimit = config('findle.rate_limit', 30);

        if ($requestCount > $rateLimit) {
            return true;
        }

        Cache::put($cacheKey, $requestCount + 1, 60);

        return false;
    }

    protected function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return rtrim($url, '/');
        }
        
        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        $path = preg_replace('#/+#', '/', $path);
        
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }
        
        $normalizedUrl = $parsed['scheme'] . '://' . $parsed['host'];
        
        if (isset($parsed['port'])) {
            $normalizedUrl .= ':' . $parsed['port'];
        }
        
        $normalizedUrl .= $path;
        
        if (isset($parsed['query'])) {
            $normalizedUrl .= '?' . $parsed['query'];
        }
        
        if (isset($parsed['fragment'])) {
            $normalizedUrl .= '#' . $parsed['fragment'];
        }
        
        return $normalizedUrl;
    }

    protected function generateVisitorHash(Request $request): string
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        $salt = config('app.key');

        return hash('sha256', $ip . $userAgent . $salt);
    }
}
