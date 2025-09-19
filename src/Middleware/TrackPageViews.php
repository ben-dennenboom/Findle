<?php

namespace Dennenboom\Findle\Middleware;

use Closure;
use Dennenboom\Findle\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public static array $fakePatterns = [
        'mozilla/4.0 (compatible;)',
        'mozilla/5.0 (x11; linux x86_64)',
        'mozilla/5.0 (windows nt 10.0)',
        'chrome/1.0',
        'safari/1.0',
        'firefox/1.0',
    ];

    public static array $botPatterns = [
        'bot',
        'spider',
        'crawl',
        'slurp',
        'scraper',
        'scanner',
        'checker',
        'monitor',
        'validator',
        'fetcher',
        'collector',
        'gatherer',
        'extractor',
        'indexer',
        'parser',
        'googlebot',
        'bingbot',
        'yandexbot',
        'baiduspider',
        'duckduckbot',
        'sogou',
        'exabot',
        'ia_archiver',
        'archive.org_bot',
        'wayback',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'pinterest',
        'whatsapp',
        'telegram',
        'slack',
        'discord',
        'instagram',
        'snapchat',
        'vkshare',
        'skype',
        'ahrefsbot',
        'semrushbot',
        'mj12bot',
        'dotbot',
        'rogerbot',
        'screaming frog',
        'seobility',
        'sistrix',
        'linkdexbot',
        'opensiteexplorer',
        'backlinktest',
        'searchmetrics',
        'seoprofiler',
        'cognitiveseo',
        'deepcrawl',
        'oncrawl',
        'botify',
        'embedly',
        'quora link preview',
        'showyoubot',
        'outbrain',
        'taboola',
        'flipboard',
        'pocket',
        'instapaper',
        'readability',
        'pingdom',
        'uptime',
        'uptimerobot',
        'monitor',
        'newrelic',
        'datadog',
        'nagios',
        'zabbix',
        'check_http',
        'curl',
        'wget',
        'httpclient',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'okhttp',
        'apache-httpclient',
        'headlesschrome',
        'chrome-lighthouse',
        'phantomjs',
        'selenium',
        'webdriver',
        'puppeteer',
        'playwright',
        'browserless',
        'nikto',
        'nessus',
        'acunetix',
        'netsparker',
        'burp',
        'owasp',
        'sqlmap',
        'wpscan',
        'dirb',
        'gobuster',
        'dirbuster',
        'masscan',
        'nmap',
        'semanticscholar',
        'researchgate',
        'academia',
        'citeseerx',
        'dblp',
        'arxiv',
        'pubmed',
        'postman',
        'insomnia',
        'httpie',
        'charles',
        'fiddler',
        'wireshark',
        'tcpdump',
        'netcat',
        'telnet',
        'masscan',
        'zmap',
    ];

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

        $fullUrl = rtrim($request->fullUrl(), '/');
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

    protected function isBot(string $userAgent, Request $request = null): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $userAgentLower = strtolower($userAgent);

        foreach (self::$botPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        if ($request) {
            if ($this->hasSuspiciousHeaders($request)) {
                return true;
            }

            if ($this->hasSuspiciousUserAgent($userAgent)) {
                return true;
            }

            if ($this->hasAutomatedRequestPattern($request)) {
                return true;
            }
        }

        return false;
    }

    protected function hasSuspiciousHeaders(Request $request): bool
    {
        if (!$request->hasHeader('Accept')) {
            return true;
        }

        $browserHeaders = ['Accept-Language', 'Accept-Encoding'];
        $missingHeaders = 0;
        foreach ($browserHeaders as $header) {
            if (!$request->hasHeader($header)) {
                $missingHeaders++;
            }
        }

        if ($missingHeaders >= 2) {
            return true;
        }

        $botHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Crawler',
            'X-Bot',
            'X-Scanner',
        ];

        foreach ($botHeaders as $header) {
            if ($request->hasHeader($header)) {
                $value = strtolower($request->header($header));
                if (str_contains($value, 'bot') || str_contains($value, 'crawler')) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function hasSuspiciousUserAgent(string $userAgent): bool
    {
        if (strlen($userAgent) < 10) {
            return true;
        }

        if (strlen($userAgent) > 1000) {
            return true;
        }

        if (preg_match('/^[a-zA-Z0-9]+$/', $userAgent)) {
            return true;
        }

        if (preg_match('/^\d+(\.\d+)*$/', $userAgent)) {
            return true;
        }

        $userAgentLower = strtolower($userAgent);
        foreach (self::$fakePatterns as $pattern) {
            if (str_starts_with($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
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

    protected function generateVisitorHash(Request $request): string
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        $salt = config('app.key');

        return hash('sha256', $ip . $userAgent . $salt);
    }
}
