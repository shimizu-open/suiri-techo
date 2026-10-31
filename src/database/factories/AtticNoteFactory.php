<?php

namespace Database\Factories;

use App\Models\AtticNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * テスト用の屋根裏の棚の1枚を作る（事件とのつながりはなし）
 *
 * @extends Factory<AtticNote>
 */
class AtticNoteFactory extends Factory
{
    /** 屋根裏の知識の既定の値を返す */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'case_file_id' => null,
            'rule' => '準備は、使う直前より前の晩のほうが忘れにくい',
            'examples' => '遠足の水筒、会議の資料',
        ];
    }
}
