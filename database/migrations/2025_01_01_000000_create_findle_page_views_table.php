<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('findle.table_name', 'page_views'), function (Blueprint $table) {
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
        Schema::dropIfExists(config('findle.table_name', 'page_views'));
    }
};
