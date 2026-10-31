<?php

namespace App\Http\Controllers\Today;

use App\Http\Controllers\Controller;
use App\Queries\Cases\OpenCasesQuery;
use App\Queries\Tasks\TaskListQuery;
use App\Queries\Today\AgendaQuery;
use App\Queries\Today\DuePredictionsQuery;
use App\Services\Guide\DailyPractice;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 今日の画面の受付（GET /today）。ログインした人の最初の画面。
 * 上から：記録のきっかけ → 答え合わせ待ち → 今日の予定とタスク（すばやく足す欄つき）→ 今日の練習 → 捜査中の事件。
 * 各部品のデータは Query と DailyPractice から集めて渡すだけ（部品の見た目はそれぞれの担当）
 */
class TodayController extends Controller
{
    /** 今日の画面を返す */
    public function __invoke(
        Request $request,
        DuePredictionsQuery $duePredictions,
        AgendaQuery $agenda,
        OpenCasesQuery $openCases,
        DailyPractice $dailyPractice,
        TaskListQuery $tasks,
    ): View {
        $user = $request->user();
        $today = today()->toDateString();
        $predictions = $duePredictions->for($user, $today);
        $shown = $predictions->pluck('id')->all(); // 答え合わせ待ちに出す予想（その確かめるタスクは下で二重に出さない）

        return view('today.index', [
            'user' => $user,
            'today' => $today,
            'predictions' => $predictions,
            'items' => $agenda->for($user, $today, $shown),
            'overdue' => $agenda->overdueCount($user, $today, $shown),
            'practice' => $dailyPractice->for($user),
            'cases' => $openCases->get($user), // 手がかり・見立て・答え合わせ待ちの数つき（事件の担当の Query）
            'justDone' => $tasks->justDone($user, session('task_done')),
        ]);
    }
}
