<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 期限の過ぎたキャッシュの行を消す（php artisan cache:prune-expired）。
 * キャッシュは database の表に置くので、期限が過ぎても同じキーを読みにこない限り行が残る。
 * 今日の予定（Google カレンダーの予定の題名と時刻）を「数分だけ覚えて、保存しない」約束を守るため、
 * worker の箱の schedule:work が5分ごとに呼ぶ（routes/console.php）
 */
#[Signature('cache:prune-expired')]
#[Description('期限の過ぎたキャッシュの行を消す（今日の予定を残さないため）')]
class PruneExpiredCache extends Command
{
    /** キャッシュの表から、期限の過ぎた行をまとめて消し、消した件数を出す */
    public function handle(): int
    {
        // キャッシュを database の表に置いていないとき（file・array など）は、消すものがないので何もしない
        if (config('cache.default') !== 'database') {
            return self::SUCCESS;
        }

        $deleted = DB::table(config('cache.stores.database.table', 'cache'))
            ->where('expiration', '<=', now()->getTimestamp())
            ->delete();

        $this->info("期限の過ぎたキャッシュを {$deleted} 件消しました。");

        return self::SUCCESS;
    }
}
