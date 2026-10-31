<?php

namespace Tests\Feature\Demo;

use App\Enums\CaseStatus;
use App\Enums\PredictionResult;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 見本のデータ（DemoSeeder）：手元だけで動き、3つの場面の事件と手帳の記録がそろい、もう一度動かしても増えない */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    /** 手元（local）でなければ、何も作らないことを確かめる（本番の記録に見本を混ぜない） */
    public function test_it_does_nothing_outside_local(): void
    {
        $this->app['env'] = 'production';

        app(DemoSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }

    /** 手元では、見本の利用者と3つの場面の事件・予想・タスク・棚・ふりかえり・名推理カードの推理がそろうことを確かめる */
    public function test_it_builds_the_demo_notebook_in_local(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DemoSeeder::class);

        $user = User::where('email', DemoSeeder::EMAIL)->firstOrFail();
        $this->assertSame('長男', $user->partner_name);
        $this->assertSame(
            ['体操着をなぜ忘れる？', '朝の電車がいつも遅れるのはなぜ？', '前回より右肩が上がって見えた件'],
            $user->caseFiles()->orderBy('id')->pluck('title')->all(),
        );
        $this->assertSame(CaseStatus::Solved, $user->caseFiles()->where('scene', 'family')->first()->status);
        $this->assertSame(1, $user->predictions()->where('result', PredictionResult::Miss)->count());
        $this->assertSame(1, $user->predictions()->whereNull('result')->where('check_on', today()->toDateString())->count());
        $this->assertSame(2, $user->atticNotes()->count());
        $this->assertSame(1, $user->weeklyReflections()->count());
        $this->assertSame(1, $user->sceneAttempts()->count());
        $this->assertGreaterThanOrEqual(3, $user->tasks()->whereNotNull('actual_minutes')->count());

        // 見本の画面が開けること（今日の画面に答え合わせ待ちが出る）
        $this->actingAs($user)->get(route('today'))->assertOk()->assertSee('前の駅を出る時刻の表示');
    }

    /** もう一度動かしても、見本の利用者と記録が二重にならないことを確かめる */
    public function test_running_twice_rebuilds_without_duplicates(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(1, User::where('email', DemoSeeder::EMAIL)->count());
        $this->assertSame(3, User::where('email', DemoSeeder::EMAIL)->first()->caseFiles()->count());
    }
}
