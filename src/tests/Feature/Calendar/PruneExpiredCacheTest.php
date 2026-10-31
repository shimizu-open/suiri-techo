<?php

namespace Tests\Feature\Calendar;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** 期限の過ぎたキャッシュ（今日の予定の題名と時刻を含む）を表に残さない仕組み */
class PruneExpiredCacheTest extends TestCase
{
    use RefreshDatabase;

    /** 期限の過ぎた行だけが消え、まだ期限内の行は残ることを確かめる */
    public function test_expired_rows_are_deleted(): void
    {
        config(['cache.default' => 'database']);
        DB::table('cache')->insert([
            ['key' => 'calendar-agenda:1:2026-10-30', 'value' => 'old', 'expiration' => now()->subMinute()->getTimestamp()],
            ['key' => 'calendar-agenda:1:2026-10-31', 'value' => 'new', 'expiration' => now()->addMinutes(3)->getTimestamp()],
        ]);

        $this->artisan('cache:prune-expired')->assertSuccessful();

        $this->assertSame(['calendar-agenda:1:2026-10-31'], DB::table('cache')->pluck('key')->all());
    }

    /** 5分ごとに消す予定が組まれていることを確かめる（worker の箱の schedule:work が動かす） */
    public function test_pruning_is_scheduled_every_five_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'cache:prune-expired'));

        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
    }
}
