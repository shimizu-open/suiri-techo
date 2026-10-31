<?php

namespace App\Providers;

use App\Contracts\CalendarAgenda;
use App\Contracts\CalendarApi;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Services\Google\CalendarApi as GoogleCalendarApi;
use App\Services\Google\FakeCalendarApi;
use App\Services\Google\GoogleCalendarAgenda;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

/**
 * Google カレンダー連携の準備をする係（bootstrap/providers.php で AppServiceProvider のあとに読む）。
 * - 今日の予定の窓口（CalendarAgenda）を、AppServiceProvider の「いつも空」から本物（GoogleCalendarAgenda）に差し替える
 * - Google カレンダーの窓口（CalendarApi）を結ぶ。テストのときは偽物（メモリに書くだけ）にし、本物の Google に届かないようにする
 * - タスクを見張る係（TaskObserver）を結び、タスクの保存で書き出しの仕事が出るようにする
 */
class CalendarServiceProvider extends ServiceProvider
{
    /** 窓口と実装を結ぶ */
    public function register(): void
    {
        $this->app->bind(CalendarAgenda::class, GoogleCalendarAgenda::class);

        // テストのときは偽物を1つだけ作って使い回す（テストが中身を見られる）。ふだんは Google の REST を呼ぶ本物
        if ($this->app->runningUnitTests()) {
            $this->app->singleton(CalendarApi::class, FakeCalendarApi::class);
        } else {
            $this->app->bind(CalendarApi::class, GoogleCalendarApi::class);
        }
    }

    /** タスクを見張る係を結ぶ。テストのときは、偽物にしていない外への HTTP を止める（本物の Google に一度もつながない） */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);

        // テストのときは、Http::fake() で用意していない外への問い合わせを、すべて失敗にする
        if ($this->app->runningUnitTests()) {
            Http::preventStrayRequests();
        }
    }
}
