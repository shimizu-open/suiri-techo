<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Passport\Passport;

/**
 * だれにも使われないまま日がたった AI の自己登録を消す（php artisan ai:prune-apps。routes/console.php で毎日）。
 * ChatGPT・Claude はつなぐたびに自分を登録する（POST /oauth/register）ので、途中でやめた登録が残る。
 * 消すのは、持ち主のいない登録のうち、作ってから config('ai_connect.prune_unused_apps_days') 日たち、
 * 通行証も引換券も1つもないもの（通行証が残っている登録は、つないでいる AI なので消さない）
 */
#[Signature('ai:prune-apps')]
#[Description('使われなかった AI の自己登録を消す')]
class PruneAiApps extends Command
{
    /** 使われなかった登録を消して、消した数を出す */
    public function handle(): int
    {
        $before = now()->subDays(config('ai_connect.prune_unused_apps_days'));
        $count = Passport::client()->newQuery()
            ->whereNull('owner_id')
            ->where('created_at', '<', $before)
            ->whereDoesntHave('tokens')
            ->whereDoesntHave('authCodes')
            ->delete();
        $this->info('使われなかった AI の登録を消しました：'.$count.' 件');

        return self::SUCCESS;
    }
}
