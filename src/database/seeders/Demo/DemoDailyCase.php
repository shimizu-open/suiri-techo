<?php

namespace Database\Seeders\Demo;

use App\Actions\Cases\AddClue;
use App\Actions\Cases\AddHypothesis;
use App\Actions\Cases\ChangeHypothesisStatus;
use App\Actions\Cases\CreateCase;
use App\Actions\Cases\MakePrediction;
use App\Actions\Cases\ResolvePrediction;
use App\Actions\Cases\SetFit;
use App\Enums\Fit;
use App\Enums\HypothesisStatus;
use App\Models\CaseFile;
use App\Models\User;

/**
 * 見本の事件（日常）：「朝の電車がいつも遅れるのはなぜ？」。捜査中。
 * 「工事のせい」の見立てで立てた予想は外れ（ノーベリ帳に載る：思い込みがあった）、
 * 「前の駅の乗り降り」の見立てには、今日が確かめる日の予想がある（今日の画面の答え合わせ待ちに出る）
 */
class DemoDailyCase
{
    use TravelsInTime;

    /** 用事（Action）を受け取る */
    public function __construct(
        private CreateCase $createCase,
        private AddClue $addClue,
        private AddHypothesis $addHypothesis,
        private SetFit $setFit,
        private ChangeHypothesisStatus $changeStatus,
        private MakePrediction $makePrediction,
        private ResolvePrediction $resolve,
    ) {}

    /** その人の見本の事件を作って返す */
    public function handle(User $user): CaseFile
    {
        $case = $this->at(8, '08:10', fn () => $this->createCase->handle($user, [
            'title' => '朝の電車がいつも遅れるのはなぜ？',
            'scene' => 'daily',
            'origin_kind' => 'task',
            'origin_label' => '7:42 の電車に乗る',
            'clue_kind' => 'seen',
            'clue_body' => '7:42 発の電車は、この1週間、毎朝3〜4分遅れて駅に着いた',
            'hypothesis_body' => '線路の工事で、ゆっくり走っている',
        ]));
        $late = $case->clues()->first();
        $works = $case->hypotheses()->first();

        [$early, $rain, $voice] = $this->at(7, '08:05', fn () => [
            $this->addClue->handle($case, 'seen', 'ひとつ前の 7:35 発は、ほぼ時刻どおりに着いた'),
            $this->addClue->handle($case, 'absent', '雨の日も晴れの日も、遅れ方は変わらなかった'),
            $this->addClue->handle($case, 'heard', '駅の放送「前の駅で、乗り降りに時間がかかっています」'),
        ]);
        $crowd = $this->at(7, '08:10', fn () => $this->addHypothesis->handle($case, '前の駅で乗りかえる人が多く、乗り降りに時間がかかる'));

        $this->at(7, '08:15', function () use ($works, $crowd, $late, $early, $rain, $voice) {
            $this->setFit->handle($works, $late, Fit::Fits);
            $this->setFit->handle($works, $early, Fit::Contradicts);
            $this->setFit->handle($crowd, $late, Fit::Fits);
            $this->setFit->handle($crowd, $rain, Fit::Fits);
            $this->setFit->handle($crowd, $voice, Fit::Fits);
        });

        // 「工事のせい」の予想：外れ（告知はなかった）。思い込みがあった、としてノーベリ帳へ
        $poster = $this->at(8, '08:20', fn () => $this->makePrediction->handle($works, [
            'statement' => '工事のせいなら、駅の掲示板に工事のお知らせが貼ってあるはず',
            'confidence' => 60,
            'check_on' => $this->day(-6),
        ]));
        $this->at(6, '08:00', function () use ($poster, $works, $crowd) {
            $this->resolve->handle($poster, [
                'result' => 'miss',
                'result_note' => 'お知らせはどこにもなかった。「遅れ＝工事」と決めつけていた。',
                'miss_reason' => 'assumption',
            ]);
            $this->changeStatus->handle($works->refresh(), HypothesisStatus::Eliminated);
            $this->changeStatus->handle($crowd->refresh(), HypothesisStatus::Likely);
        });

        // 「前の駅の乗り降り」の予想：今日が確かめる日（答え合わせ待ち）
        $this->at(1, '21:30', fn () => $this->makePrediction->handle($crowd->refresh(), [
            'statement' => '前の駅の乗り降りのせいなら、前の駅を出る時刻の表示も3分ほど遅れているはず',
            'confidence' => 75,
            'check_on' => $this->day(0),
            'check_at' => '07:40',
        ]));

        return $case;
    }
}
