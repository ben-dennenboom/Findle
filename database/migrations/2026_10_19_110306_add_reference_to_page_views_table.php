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
            $table->string('reference')->nullable()->index();
        });

        DB::table($tableName)
            ->where('url', 'like', '%?%')
            ->select('id', 'url')
            ->chunkById(500, function ($pageViews) use ($tableName) {
                foreach ($pageViews as $pageView) {
                    $reference = PageView::extractReference($pageView->url);

                    if ($reference === null) {
                        continue;
                    }

                    DB::table($tableName)
                        ->where('id', $pageView->id)
                        ->update(['reference' => $reference]);
                }
            });
    }
};
