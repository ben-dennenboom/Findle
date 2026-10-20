<?php

namespace Dennenboom\Findle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'base_url',
        'visitor_hash',
        'user_agent',
        'referrer',
        'query_data',
        'visits_count',
        'reference',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'query_data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(static::tableName());
    }

    public static function tableName(): string
    {
        return config('findle.table_name', 'page_views');
    }

    public static function normalizeBaseUrl(string $url): string
    {
        $baseUrl = preg_split('/[?#]/', $url, 2)[0];

        return str_ends_with($baseUrl, '/') ?
            substr($baseUrl, 0, -1) :
            $baseUrl;
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PageViewVisit::class);
    }

    public function scopeForBaseUrl(Builder $query, string $url): Builder
    {
        return $query->where('base_url', static::normalizeBaseUrl($url));
    }

    public static function recordView(
        string $url,
        string $visitorHash,
        ?string $userAgent = null,
        ?string $referrer = null,
        array $queryData = [],
    ): bool {
        try {
            $rateLimitKey = "findle_view_rate_limit_{$visitorHash}_" . md5($url);
            
            if (Cache::has($rateLimitKey)) {
                return true;
            }
            
            Cache::put($rateLimitKey, true, 10);
            
            DB::transaction(function () use ($url, $visitorHash, $userAgent, $referrer, $queryData) {
                $pageView = static::where('url', $url)
                    ->where('visitor_hash', $visitorHash)
                    ->first();

                if ($pageView) {
                    $pageView->increment('visits_count');

                    $pageView->update([
                        'user_agent' => $userAgent,
                        'referrer'   => $referrer,
                        'query_data' => static::mergeQueryData($pageView->query_data ?? [], $queryData),
                        'updated_at' => now(),
                    ]);
                } else {
                    $pageView = static::create([
                        'url'          => $url,
                        'base_url'     => static::normalizeBaseUrl($url),
                        'visitor_hash' => $visitorHash,
                        'user_agent'   => $userAgent,
                        'referrer'     => $referrer,
                        'query_data'   => $queryData,
                        'visits_count' => 1,
                        'reference'    => static::extractReference($url),
                    ]);
                }

                $pageView->visits()->create(['visited_at' => now()]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record page view: ' . $e->getMessage());

            return false;
        }
    }

    public static function extractReference(string $url): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $queryParameters);

        foreach (config('findle.reference_parameters', ['ref', 'utm_ref']) as $parameter) {
            $reference = $queryParameters[$parameter] ?? null;

            if (! is_string($reference)) {
                continue;
            }

            $reference = trim($reference);

            if ($reference === '') {
                continue;
            }

            return mb_substr($reference, 0, 255);
        }

        return null;
    }

    private static function mergeQueryData(array $existing, array $new): array
    {
        $merged = $existing;
        
        foreach ($new as $key => $value) {
            if (!isset($merged[$key])) {
                $merged[$key] = [];
            }
            
            if (!is_array($merged[$key])) {
                $merged[$key] = [$merged[$key]];
            }
            
            if (!in_array($value, $merged[$key])) {
                $merged[$key][] = $value;
            }
        }
        
        return $merged;
    }
}
