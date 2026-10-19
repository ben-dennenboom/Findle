<?php

namespace Dennenboom\Findle\Support;

use Illuminate\Http\Request;

class BotDetector
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
        'preview',
        'prerender',
        'sogou',
        'exabot',
        'ia_archiver',
        'wayback',
        'facebookexternalhit',
        'meta-externalagent',
        'vkshare',
        'google-inspectiontool',
        'googleother',
        'google-extended',
        'chatgpt',
        'perplexity',
        'anthropic',
        'claude-web',
        'cohere-ai',
        'screaming frog',
        'seobility',
        'sistrix',
        'opensiteexplorer',
        'backlinktest',
        'searchmetrics',
        'seoprofiler',
        'cognitiveseo',
        'deepcrawl',
        'oncrawl',
        'botify',
        'dataforseo',
        'serpstat',
        'embedly',
        'quora link preview',
        'outbrain',
        'taboola',
        'flipboard',
        'pocket',
        'instapaper',
        'readability',
        'pingdom',
        'uptime',
        'newrelic',
        'datadog',
        'nagios',
        'zabbix',
        'check_http',
        'curl',
        'wget',
        'httpclient',
        'python',
        'aiohttp',
        'httpx',
        'scrapy',
        'go-http-client',
        'okhttp',
        'java/',
        'libwww',
        'guzzle',
        'axios',
        'node-fetch',
        'undici',
        'headless',
        'lighthouse',
        'phantomjs',
        'selenium',
        'webdriver',
        'puppeteer',
        'playwright',
        'cypress',
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
        'masscan',
        'zmap',
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
    ];

    public static function isBot(?string $userAgent, ?Request $request = null): bool
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

        if (self::hasSuspiciousUserAgent($userAgent)) {
            return true;
        }

        if ($request && self::hasSuspiciousHeaders($request)) {
            return true;
        }

        return false;
    }

    protected static function hasSuspiciousUserAgent(string $userAgent): bool
    {
        if (strlen($userAgent) < 10) {
            return true;
        }

        if (strlen($userAgent) > 1000) {
            return true;
        }

        $userAgentLower = strtolower($userAgent);

        // Every mainstream browser has sent a "Mozilla/5.0" prefix for over a decade.
        if (! str_starts_with($userAgentLower, 'mozilla/5.0')) {
            return true;
        }

        foreach (self::$fakePatterns as $pattern) {
            if (str_starts_with($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected static function hasSuspiciousHeaders(Request $request): bool
    {
        if (! $request->hasHeader('Accept')) {
            return true;
        }

        if (! $request->hasHeader('Accept-Language')) {
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
            if (! $request->hasHeader($header)) {
                continue;
            }

            $value = strtolower($request->header($header));

            if (str_contains($value, 'bot') || str_contains($value, 'crawler')) {
                return true;
            }
        }

        return false;
    }
}
