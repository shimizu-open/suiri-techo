<?php

namespace App\Actions\Review;

use App\Models\User;
use App\Models\WeeklyReflection;
use App\Services\Review\ReviewWeek;

/** 週の問い（今週いちばん考えが変わった事件・つまり？・たとえば？）を保存する用事。同じ週は1行だけで、書き直すと上書きする */
class SaveWeeklyReflection
{
    /** 検証済みの値をその週の行に保存する（なければ作る）。空の欄は null にする */
    public function handle(User $user, ReviewWeek $week, array $data): WeeklyReflection
    {
        return $user->weeklyReflections()->updateOrCreate(
            ['week_start' => $week->param()],
            [
                'changed_case_file_id' => $data['changed_case_file_id'] ?? null,
                'abstraction' => filled($data['abstraction'] ?? null) ? $data['abstraction'] : null,
                'concrete' => filled($data['concrete'] ?? null) ? $data['concrete'] : null,
            ],
        );
    }
}
