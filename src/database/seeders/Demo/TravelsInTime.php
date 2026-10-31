<?php

namespace Database\Seeders\Demo;

use Closure;
use Illuminate\Support\Carbon;

/**
 * 見本のデータを「何日か前に書いたもの」として作るための道具。
 * 時計を一時的にその日時へ合わせてから用事（Action）を呼ぶので、作った日時・答え合わせの日時・考えを変えた記録の日時が自然に並ぶ
 */
trait TravelsInTime
{
    /** 本当の今日（時計を合わせる前に覚えておく。day() はいつもこの日から数える） */
    private ?Carbon $realToday = null;

    /** 時計を「$daysAgo 日前の $time」に合わせて $work を動かし、終わったら時計を戻す。$work の戻り値を返す */
    protected function at(int $daysAgo, string $time, Closure $work): mixed
    {
        $this->realToday ??= Carbon::today();
        Carbon::setTestNow($this->realToday->copy()->subDays($daysAgo)->setTimeFromTimeString($time));

        // 用事を動かす。途中で失敗しても、時計は必ず元に戻す
        try {
            return $work();
        } finally {
            Carbon::setTestNow();
        }
    }

    /** 本当の今日から $days 日あとの日付を 'Y-m-d' で返す（前なら負の数。時計を合わせている最中でも本当の今日から数える） */
    protected function day(int $days): string
    {
        return ($this->realToday ?? Carbon::today())->copy()->addDays($days)->toDateString();
    }
}
