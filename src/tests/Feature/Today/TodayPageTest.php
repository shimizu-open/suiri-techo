<?php

namespace Tests\Feature\Today;

use App\Contracts\CalendarAgenda;
use App\Models\CaseFile;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use App\Services\Google\FakeCalendarAgenda;
use App\Services\Guide\DailyPractice;
use App\Support\Landing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 今日の画面（D-1〜D-4）：いまやることのカード（答え合わせか、記録のきっかけと書くボタン）・きょうの予定とやること・すばやく書く欄・練習・捜査中の事件 */
class TodayPageTest extends TestCase
{
    use RefreshDatabase;

    /** 今日の予定の窓口を、決まった予定を返す偽物に差し替える（カレンダーとつないでいる人の代わり） */
    private function fakeCalendar(array $events): void
    {
        $this->app->instance(CalendarAgenda::class, new FakeCalendarAgenda($events));
    }

    /** ログインした人の最初の画面が今日の画面であり、ログインしていない人は開けないことを確かめる */
    public function test_today_is_the_landing_page(): void
    {
        $this->assertSame(route('today'), Landing::url());
        $this->get(route('today'))->assertRedirect(route('home'));
        $this->actingAs(User::factory()->create())->get(route('today'))->assertOk()->assertSee('きょうの予定とやること');
    }

    /** 答え合わせがない日は「気になったことを書こう」のカードが出て、記録のきっかけがあればその一文が、なければ設定で決めるさそいが出ることを確かめる */
    public function test_the_cue_or_an_invitation_to_set_it(): void
    {
        $this->actingAs(User::factory()->create(['cue' => '夕食のあとに、気になったことを1つ']))
            ->get(route('today'))->assertOk()->assertSee('夕食のあとに、気になったことを1つ')
            ->assertSee(config('suiri.today.record.button'))->assertSee(route('cases.create'), false);

        $this->actingAs(User::factory()->create(['cue' => null]))
            ->get(route('today'))->assertOk()->assertSee(config('suiri.today.record.no_cue_link'))->assertSee(route('settings.show'), false);
    }

    /**
     * 答え合わせのカードには、確かめる日が今日までに来た結果のない予想だけが出て、先の日・済み・取り下げ・他人の予想は出ないことを確かめる。
     * いちばん古い予想を大きく1件だけ出し、残りは「ほかにも◯件あるよ」にたたむ
     */
    public function test_due_predictions_are_listed(): void
    {
        $user = User::factory()->create();
        $case = CaseFile::factory()->for($user)->create(['title' => '体操着をなぜ忘れる？']);
        $make = fn (array $attributes) => Prediction::factory()->create([
            'hypothesis_id' => Hypothesis::factory()->for($case)->create()->id, ...$attributes,
        ]);
        $make(['statement' => '今日確かめる予想', 'check_on' => today()->toDateString(), 'confidence' => 80]);
        $oldest = $make(['statement' => 'おととい確かめるはずだった予想', 'check_on' => today()->subDays(2)->toDateString()]);
        $make(['statement' => '明日確かめる予想', 'check_on' => today()->addDay()->toDateString()]);
        $make(['statement' => '答え合わせ済みの予想', 'check_on' => today()->toDateString(), 'result' => 'hit', 'resolved_at' => now()]);
        $make(['statement' => '取り下げた予想', 'check_on' => today()->toDateString(), 'withdrawn_at' => now()]);
        Prediction::factory()->due()->create(['statement' => '人の予想']);

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSee(config('suiri.today.answer.title'))->assertSee('ほかにも1件あるよ')
            ->assertSee(route('predictions.show', $oldest), false)
            ->assertSeeInOrder(['体操着をなぜ忘れる？', 'おととい確かめるはずだった予想', config('suiri.today.answer.button'), '今日確かめる予想', '80％'])
            ->assertDontSee('明日確かめる予想')->assertDontSee('答え合わせ済みの予想')->assertDontSee('取り下げた予想')->assertDontSee('人の予想');
    }

    /** 答え合わせ待ちがなければ、答え合わせのカードは出さず、気になったことを書くカードが出ることを確かめる */
    public function test_no_due_predictions_means_no_box(): void
    {
        $this->actingAs(User::factory()->create())->get(route('today'))->assertOk()
            ->assertDontSee(config('suiri.today.answer.title'))->assertSee(config('suiri.today.record.title'));
    }

    /** 予定（偽物のカレンダー）と今日が期日のタスクが、終日を上に、あとは時刻順に混ざって並ぶことを確かめる */
    public function test_events_and_tasks_are_merged_in_time_order(): void
    {
        $this->fakeCalendar([
            ['title' => '歯医者', 'starts_at' => '09:00', 'ends_at' => '10:00', 'all_day' => false],
            ['title' => '祖母の誕生日', 'starts_at' => null, 'ends_at' => null, 'all_day' => true],
            ['title' => '夕方の会議', 'starts_at' => '17:00', 'ends_at' => '18:00', 'all_day' => false],
        ]);
        $user = User::factory()->create();
        Task::factory()->for($user)->at('12:30')->create(['title' => '昼に銀行へ行く']);
        Task::factory()->for($user)->create(['title' => '牛乳を買う']);
        Task::factory()->for($user)->at('08:15')->create(['title' => '朝に植木へ水をやる']);
        Task::factory()->for($user)->create(['title' => '明日のタスク', 'due_on' => today()->addDay()->toDateString()]);

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSeeInOrder(['祖母の誕生日', '牛乳を買う', '08:15', '朝に植木へ水をやる', '09:00', '歯医者', '〜10:00', '12:30', '昼に銀行へ行く', '17:00', '夕方の会議'])
            ->assertDontSee('明日のタスク')
            ->assertViewHas('items', fn ($items) => $items->pluck('type')->all() === ['event', 'task', 'task', 'event', 'task', 'event']);
    }

    /** 今日済ませたタスクも、消えずに済みの印つきで並び、そこから未完了に戻せて、「気になる」で事件にできる（まだのタスクには出ない）ことを確かめる */
    public function test_done_tasks_stay_in_the_agenda(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->done()->create(['title' => '済んだ買い物']);
        Task::factory()->for($user)->create(['title' => 'まだの買い物']);

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSee('済んだ買い物')->assertSee('「済んだ買い物」をまだに戻す')
            ->assertSee('name="label" value="済んだ買い物"', false)->assertDontSee('name="label" value="まだの買い物"', false);
    }

    /** 答え合わせ待ちに出ている予想の確かめるタスクは、予定とタスクの並びに二重に出さないことを確かめる */
    public function test_verify_tasks_of_due_predictions_are_not_listed_twice(): void
    {
        $prediction = Prediction::factory()->due()->create(['statement' => '時間割を見れば忘れないはず']);
        Task::factory()->verify($prediction)->create();

        $this->actingAs(User::find($prediction->user_id))->get(route('today'))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->isEmpty())
            ->assertSee('時間割を見れば忘れないはず');
    }

    /** 期日を過ぎた済んでいないタスクがあれば、その数と一覧へのリンクが出ることを確かめる */
    public function test_overdue_tasks_are_pointed_out(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->count(2)->create(['due_on' => today()->subDay()->toDateString()]);
        Task::factory()->for($user)->done()->create(['due_on' => today()->subDay()->toDateString()]);

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertSee('前からのやり残しが2件あるよ')
            ->assertSee(route('tasks.index', ['view' => 'today']), false);
    }

    /** 見出しの横の「＋ 書く」で開くすばやく書く欄があり、期日が今日で、書いたあと今日の画面へ戻る印（back=today）を送ることを確かめる */
    public function test_the_quick_add_form(): void
    {
        $this->actingAs(User::factory()->create())->get(route('today'))->assertOk()
            ->assertSee(config('suiri.today.agenda.add_label'))
            ->assertSee('action="'.route('tasks.store').'"', false)
            ->assertSee('name="due_on" value="'.today()->toDateString().'"', false)
            ->assertSee('name="back" value="today"', false);
    }

    /** 今日の練習（DailyPractice）と、捜査中の事件の最近の数件が部品に渡されることを確かめる */
    public function test_practice_and_open_cases_are_passed_to_the_parts(): void
    {
        $user = User::factory()->create();
        CaseFile::factory()->for($user)->count(4)->create(['status' => 'open']);
        CaseFile::factory()->for($user)->create(['status' => 'solved', 'title' => '解決した事件']);
        CaseFile::factory()->create(['status' => 'open']);

        $this->actingAs($user)->get(route('today'))->assertOk()
            ->assertViewHas('practice', app(DailyPractice::class)->for($user))
            ->assertViewHas('cases', fn ($cases) => $cases->count() === config('suiri.today.open_cases')
                && $cases->every(fn ($case) => $case->user_id === $user->id && $case->status->value === 'open'));
    }
}
