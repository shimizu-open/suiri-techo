<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * 事件を消す用事。手がかり・見立て・予想・○× は表のつながりで一緒に消える。
 * 予想から作った確かめるタスクは、予想がなくなると意味を失うので、ここで1件ずつ消す（カレンダーの予定も見張り役が消す）
 */
class DeleteCase
{
    /** 事件と、その予想の確かめるタスクを消す */
    public function handle(CaseFile $case): void
    {
        DB::transaction(function () use ($case) {
            Task::whereIn('prediction_id', $case->predictions()->select('id'))->get()->each->delete();
            $case->delete();
        });
    }
}
