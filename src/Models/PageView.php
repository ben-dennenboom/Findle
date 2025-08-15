<?php

namespace Dennenboom\Findle\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'visitor_hash',
        'user_agent',
        'referrer',
        'query_data',
        'visits_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'query_data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('findle.table_name', 'page_views'));
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
            
            $existingRecord = static::where('url', $url)
                ->where('visitor_hash', $visitorHash)
                ->first();

            if ($existingRecord) {
                $existingRecord->increment('visits_count');
                
                $mergedQueryData = static::mergeQueryData($existingRecord->query_data ?? [], $queryData);
                
                $existingRecord->update([
                    'user_agent' => $userAgent,
                    'referrer'   => $referrer,
                    'query_data' => $mergedQueryData,
                    'updated_at' => now(),
                ]);
            } else {
                static::create([
                    'url'          => $url,
                    'visitor_hash' => $visitorHash,
                    'user_agent'   => $userAgent,
                    'referrer'     => $referrer,
                    'query_data'   => $queryData,
                    'visits_count' => 1,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record page view: ' . $e->getMessage());

            return false;
        }
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
