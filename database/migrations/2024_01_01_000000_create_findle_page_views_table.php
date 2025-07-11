<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableName = config('findle.table_name', 'page_views');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('url')->index();
            $table->string('visitor_hash')->index();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->json('query_data')->nullable();
            $table->timestamps();

            $table->unique(['url', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        $tableName = config('findle.table_name', 'page_views');
        Schema::dropIfExists($tableName);
    }
};
