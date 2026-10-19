<?php

use Dennenboom\Findle\Models\PageView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableName = config('findle.table_name', 'page_views');

        Schema::table($tableName, function (Blueprint $table) {
            $table->string('base_url')->nullable()->after('url')->index();
        });

        DB::table($tableName)
            ->select('id', 'url')
            ->chunkById(500, function ($pageViews) use ($tableName) {
                foreach ($pageViews as $pageView) {
                    DB::table($tableName)
                        ->where('id', $pageView->id)
                        ->update(['base_url' => PageView::normalizeBaseUrl($pageView->url)]);
                }
            });
    }
};
