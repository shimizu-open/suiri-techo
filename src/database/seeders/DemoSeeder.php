<?php

namespace Database\Seeders;

use App\Enums\Scene;
use App\Models\User;
use Database\Seeders\Demo\DemoDailyCase;
use Database\Seeders\Demo\DemoFamilyCase;
use Database\Seeders\Demo\DemoNotebook;
use Database\Seeders\Demo\DemoWorkCase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 見本のデータを入れる（php artisan db:seed --class=DemoSeeder）。**手元（APP_ENV=local）だけで動き、本番では何もしない。**
 * 見本の利用者 watson@example.com（相棒「長男」）と、3つの場面の事件・タスク・屋根裏の棚・ノーベリ帳・週のふりかえり・
 * 名推理カードへの推理を、ふだんの画面と同じ用事（Action）を通して作る。画面の確認・スクリーンショット・説明書の図に使う。
 * もう一度動かすと、見本の利用者の記録だけを作り直す（ほかの利用者には触らない）
 */
class DemoSeeder extends Seeder
{
    /** 見本の利用者のメールアドレス（Google ではログインできない、見本だけの住所） */
    public const EMAIL = 'watson@example.com';

    /** 見本のデータを作る。手元でなければ、何もせずに知らせて終える */
    public function run(): void
    {
        // 本番・テストなど手元でない所では、見本のデータを入れない（本番の記録に混ぜない）
        if (! app()->environment('local')) {
            $this->command?->warn('見本のデータは手元（APP_ENV=local）でだけ入れられます。何もしませんでした。');

            return;
        }

        DB::transaction(function () {
            User::where('email', self::EMAIL)->first()?->delete();
            $user = $this->user();

            app(DemoFamilyCase::class)->handle($user);
            $train = app(DemoDailyCase::class)->handle($user);
            app(DemoWorkCase::class)->handle($user);
            app(DemoNotebook::class)->handle($user->refresh(), $train);
        });

        $this->command?->info('見本のデータを入れました（'.self::EMAIL.'）。');
    }

    /** 見本の利用者を作る（同意済み・相棒「長男」・ふだんの場面は日常・記録のきっかけつき） */
    private function user(): User
    {
        $user = User::forceCreate([
            'google_id' => 'demo-watson',
            'name' => 'ワトソン（見本）',
            'email' => self::EMAIL,
            'partner_name' => '長男',
            'default_scene' => Scene::Daily,
            'cue' => '夕食のあとに、気になったことを1つ',
            'agreed_at' => now()->subDays(14),
        ]);
        $user->forceFill(['created_at' => now()->subDays(14)])->save();

        return $user;
    }
}
