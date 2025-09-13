<?php

namespace Dennenboom\Findle\Http\Controllers;

use Dennenboom\Findle\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrackingApiController extends Controller
{
    public function track(Request $request)
    {
        try {
            if (!config('findle.js_tracking.enabled', false)) {
                return response()->json(['error' => 'JavaScript tracking not enabled'], 403);
            }

            $token = $request->input('token');
            if (!$token || !$this->validateToken($token)) {
                return response()->json(['error' => 'Invalid or expired token'], 403);
            }

            $url = $request->input('url');
            $referrer = $request->input('referrer');

            if (!$url) {
                return response()->json(['error' => 'URL is required'], 400);
            }

            if ($this->shouldExcludePath($url)) {
                return response()->json(['success' => true, 'message' => 'Path excluded']);
            }

            if ($this->isRapidRequest($request)) {
                return response()->json(['success' => true, 'message' => 'Rate limited']);
            }

            $hash = $this->generateVisitorHash($request);

            PageView::recordView(
                $url,
                $hash,
                $request->userAgent(),
                $referrer,
                $request->query->all()
            );

            $this->invalidateToken($token);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to track page view via API: ' . $e->getMessage());

            return response()->json(['error' => 'Tracking failed'], 500);
        }
    }

    protected function validateToken(string $token): bool
    {
        $cacheKey = "findle_js_token_{$token}";

        return Cache::has($cacheKey);
    }

    protected function shouldExcludePath(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';
        $excludedPaths = config('findle.exclude_routes', []);

        foreach ($excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isRapidRequest(Request $request): bool
    {
        $ip = $request->ip();
        $cacheKey = "findle_api_rate_limit_{$ip}";

        $requestCount = Cache::get($cacheKey, 0);
        $rateLimit = config('findle.js_tracking.rate_limit', 60);

        if ($requestCount > $rateLimit) {
            return true;
        }

        Cache::put($cacheKey, $requestCount + 1, 60);

        return false;
    }

    protected function generateVisitorHash(Request $request): string
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $salt = config('app.key');

        return hash('sha256', $ip . $userAgent . $salt);
    }

    protected function invalidateToken(string $token): void
    {
        $cacheKey = "findle_js_token_{$token}";
        Cache::forget($cacheKey);
    }

    public function generateToken()
    {
        if (!config('findle.js_tracking.enabled', false)) {
            return response()->json(['error' => 'JavaScript tracking not enabled'], 403);
        }

        $token = Str::random(32);
        $cacheKey = "findle_js_token_{$token}";

        Cache::put($cacheKey, true, config('findle.js_tracking.token_lifetime', 300));

        return response()->json(['token' => $token]);
    }
}
