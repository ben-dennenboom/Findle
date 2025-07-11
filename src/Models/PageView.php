<?php

namespace Dennenboom\Findle\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'visitor_hash',
        'user_agent',
        'referrer',
        'query_data',
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
            static::updateOrCreate(
                [
                    'url' => $url,
                    'visitor_hash' => $visitorHash,
                ],
                [
                    'user_agent' => $userAgent,
                    'referrer'   => $referrer,
                    'query_data' => $queryData,
                    'updated_at' => now(),
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to record page view: ' . $e->getMessage());

            return false;
        }
    }
}
