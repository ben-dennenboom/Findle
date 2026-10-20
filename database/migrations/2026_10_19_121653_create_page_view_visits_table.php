<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $pageViewsTableName = config('findle.table_name', 'page_views');
        $visitsTableName = config('findle.visits_table_name', 'page_view_visits');

        Schema::create($visitsTableName, function (Blueprint $table) use ($pageViewsTableName) {
            $table->id();
            $table->foreignId('page_view_id')->constrained($pageViewsTableName)->cascadeOnDelete();
            $table->timestamp('visited_at')->index();

            $table->index(['page_view_id', 'visited_at']);
        });

        DB::table($pageViewsTableName)
            ->select('id', 'visits_count', 'created_at', 'updated_at')
            ->chunkById(500, function ($pageViews) use ($visitsTableName) {
                $visits = $pageViews->flatMap(fn ($pageView) => $this->backfilledVisits($pageView));

                foreach ($visits->chunk(1000) as $visitsChunk) {
                    DB::table($visitsTableName)->insert($visitsChunk->values()->all());
                }
            });
    }

    /**
     * Only the first and last visit times are known for existing page views,
     * so the visits in between are spread evenly over that period.
     */
    private function backfilledVisits(object $pageView): array
    {
        $firstVisitAt = Carbon::parse($pageView->created_at ?? $pageView->updated_at ?? now());
        $lastVisitAt = Carbon::parse($pageView->updated_at ?? $firstVisitAt);
        $visitsCount = max(1, (int) $pageView->visits_count);

        $secondsBetweenVisits = $visitsCount > 1 ?
            $firstVisitAt->diffInSeconds($lastVisitAt, true) / ($visitsCount - 1) :
            0;

        return array_map(fn (int $visitIndex) => [
            'page_view_id' => $pageView->id,
            'visited_at'   => $firstVisitAt->copy()->addSeconds((int) round($secondsBetweenVisits * $visitIndex)),
        ], range(0, $visitsCount - 1));
    }
};
