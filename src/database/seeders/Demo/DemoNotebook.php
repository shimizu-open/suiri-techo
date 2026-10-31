<?php

namespace Database\Seeders\Demo;

use App\Actions\Attic\SaveAtticNote;
use App\Actions\Guide\SaveSceneAttempt;
use App\Actions\Review\SaveWeeklyReflection;
use App\Actions\Tasks\CompleteTask;
use App\Actions\Tasks\CreateTask;
use App\Models\CaseFile;
use App\Models\User;
use App\Services\Review\ReviewWeek;

/**
 * 見本の手帳の残り：タスク（予想時間とかかった時間つき・期限を過ぎたもの・いつか）、手で足した屋根裏の棚、
 * 今週のふりかえり、名推理カードへの自分の推理
 */
class DemoNotebook
{
    use TravelsInTime;

    /** 用事（Action）を受け取る */
    public function __construct(
        private CreateTask $createTask,
        private CompleteTask $completeTask,
        private SaveAtticNote $saveAtticNote,
        private SaveWeeklyReflection $saveReflection,
        private SaveSceneAttempt $saveSceneAttempt,
    ) {}

    /** その人の見本のタスク・棚・ふりかえり・名推理カードの推理を作る。$train は日常の事件（ふりかえりと棚が指す） */
    public function handle(User $user, CaseFile $train): void
    {
        $this->tasks($user);

        $this->at(5, '21:00', fn () => $this->saveAtticNote->handle($user, [
            'rule' => '原因を1つに決める前に、比べる相手（ほかの時間・ほかの日）を作る',
            'examples' => 'バスの遅れは別の便と、レジの待ち時間は別の曜日と比べてみる',
            'case_file_id' => $train->id,
        ]));

        $this->at(0, '07:00', fn () => $this->saveReflection->handle($user, ReviewWeek::current(), [
            'changed_case_file_id' => $train->id,
            'abstraction' => 'いつもそうだ、と思ったことほど、比べる相手を作ると別の説明が見えてくる。',
            'concrete' => '子どもの忘れ物も、忘れた日と忘れなかった日を比べると、置き場所の違いが見えた。',
        ]));

        $this->at(4, '22:00', fn () => $this->saveSceneAttempt->handle($user, 'scarlet-watson',
            '顔は日焼けしているのに手首は白い。暑い土地で、袖の長い服を着て働いていた人かもしれない。左腕をかばうので、そこでけがをして戻ってきたのでは。'));
    }

    /** タスクを作る。済んだものは、その日に済ませた形で予想時間とかかった時間を残す（見積もりの答え合わせ） */
    private function tasks(User $user): void
    {
        $shopping = $this->at(6, '09:00', fn () => $this->createTask->handle($user, [
            'title' => 'スーパーで買い物', 'due_on' => $this->day(-6), 'duration_minutes' => 30,
        ]));
        $this->at(6, '18:40', fn () => $this->completeTask->handle($shopping, 40));

        $cleaning = $this->at(2, '09:00', fn () => $this->createTask->handle($user, [
            'title' => '部屋の掃除', 'due_on' => $this->day(-1), 'duration_minutes' => 30,
        ]));
        $this->at(1, '11:00', fn () => $this->completeTask->handle($cleaning, 45));

        $print = $this->at(1, '21:00', fn () => $this->createTask->handle($user, [
            'title' => '学校のプリントに記入', 'due_on' => $this->day(0), 'duration_minutes' => 15,
        ]));
        $this->at(0, '07:30', fn () => $this->completeTask->handle($print, 25));

        $this->at(1, '21:05', function () use ($user) {
            // 今日・これから・期限を過ぎた・いつか のタスクを1つずつ並べる
            foreach ([
                ['title' => '夕食の買い物', 'due_on' => $this->day(0), 'starts_at' => '17:30', 'duration_minutes' => 30],
                ['title' => '歯医者に予約の電話', 'due_on' => $this->day(-1), 'duration_minutes' => 5],
                ['title' => '次回、確かめることを読み返す', 'due_on' => $this->day(1), 'starts_at' => '20:00', 'duration_minutes' => 10],
                ['title' => '図書館に本を返す', 'due_on' => $this->day(3)],
                ['title' => '本棚の整理', 'memo' => '読み終えた本を1段にまとめる'],
            ] as $task) {
                $this->createTask->handle($user, $task);
            }
        });
    }
}
