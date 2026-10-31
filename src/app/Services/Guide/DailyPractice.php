<?php

namespace App\Services\Guide;

use App\Models\User;
use App\Support\Guide\GuideLibrary;
use Carbon\CarbonInterface;

/**
 * 今日の画面に出す「今日の練習」を1つ選ぶ係。日付と利用者で決まるので、同じ日に何度開いても同じものが出る。
 * やらなくても何も起きない（連続記録や罰はない）
 */
class DailyPractice
{
    /** 教材を読む係を受け取る */
    public function __construct(private GuideLibrary $library) {}

    /** その日の練習メニューを1つ返す。メニューが1つもなければ null */
    public function for(User $user, ?CarbonInterface $date = null): ?array
    {
        $practices = $this->library->practices();
        // メニューがないとき（教材のファイルが空）は何も勧めない
        if ($practices === []) {
            return null;
        }
        $day = ($date ?? now())->format('Y-m-d');

        return $practices[crc32($day.'#'.$user->id) % count($practices)];
    }
}
