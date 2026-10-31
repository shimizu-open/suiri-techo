<?php

namespace App\Actions\Cases;

use App\Models\Hypothesis;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * 見立てを消す用事。その見立ての予想・○×・状態の記録は表のつながりで一緒に消える。
 * 予想から作った確かめるタスクは、ここで1件ずつ消す（カレンダーの予定も見張り役が消す）
 */
class DeleteHypothesis
{
    /** 見立てと、その予想の確かめるタスクを消す */
    public function handle(Hypothesis $hypothesis): void
    {
        DB::transaction(function () use ($hypothesis) {
            Task::whereIn('prediction_id', $hypothesis->predictions()->select('id'))->get()->each->delete();
            $hypothesis->delete();
        });
    }
}
