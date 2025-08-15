<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(config('findle.table_name', 'page_views'), function (Blueprint $table) {
            $table->unsignedInteger('visits_count')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table(config('findle.table_name', 'page_views'), function (Blueprint $table) {
            $table->dropColumn('visits_count');
        });
    }
};
