<?php

namespace App\Actions\Cases;

use App\Models\Prediction;
use Illuminate\Support\Facades\DB;

/**
 * 予想を取り下げる用事（C-6）。予想の文と確かさは残したまま、取り下げた日時だけを入れる。
 * まだ済んでいない確かめるタスクは、もう確かめる必要がないので消す（カレンダーの予定も見張り役が消す）。
 * 取り下げられるのは答え合わせ待ちの予想だけ。答え合わせ済み・取り下げ済みなら何もしない
 * （受付の WithdrawPredictionRequest が先に断る。ここは同時に答え合わせされたときの最後の守り）
 */
class WithdrawPrediction
{
    /** 答え合わせ待ちの予想なら取り下げ、済んでいない確かめるタスクを消す。取り下げたら true、何もしなければ false */
    public function handle(Prediction $prediction): bool
    {
        return DB::transaction(function () use ($prediction) {
            // 保存されている今の状態を、ほかの書き込みを待たせて読み直す（同時の答え合わせと食い違わないように）
            $current = Prediction::whereKey($prediction->getKey())->lockForUpdate()->first();

            // もう答え合わせ済み・取り下げ済み（または消えた）なら、何もしない
            if ($current === null || ! $current->isPending()) {
                return false;
            }

            $prediction->update(['withdrawn_at' => now()]);

            $task = $prediction->task;
            // 確かめるタスクがまだ済んでいなければ消す（済んだものは記録として残す）
            if ($task !== null && ! $task->isDone()) {
                $task->delete();
            }

            return true;
        });
    }
}
