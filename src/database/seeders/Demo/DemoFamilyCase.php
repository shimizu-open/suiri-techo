<?php

namespace Database\Seeders\Demo;

use App\Actions\Cases\AddClue;
use App\Actions\Cases\AddHypothesis;
use App\Actions\Cases\ChangeHypothesisStatus;
use App\Actions\Cases\CloseCase;
use App\Actions\Cases\CreateCase;
use App\Actions\Cases\MakePrediction;
use App\Actions\Cases\ResolvePrediction;
use App\Actions\Cases\RevealHypotheses;
use App\Actions\Cases\SetFit;
use App\Enums\Fit;
use App\Enums\HypothesisStatus;
use App\Models\CaseFile;
use App\Models\User;

/**
 * 見本の事件（親子）：「体操着をなぜ忘れる？」。相棒（長男）と隠して見立てを書き、見せ合い、
 * 予想を立てて答え合わせ（当たり）まで済ませ、「つまり？・たとえば？」を屋根裏の棚に入れて解決にした形
 */
class DemoFamilyCase
{
    use TravelsInTime;

    /** 用事（Action）を受け取る。見本も、ふだんの画面と同じ道を通って作る */
    public function __construct(
        private CreateCase $createCase,
        private AddClue $addClue,
        private AddHypothesis $addHypothesis,
        private SetFit $setFit,
        private RevealHypotheses $reveal,
        private ChangeHypothesisStatus $changeStatus,
        private MakePrediction $makePrediction,
        private ResolvePrediction $resolve,
        private CloseCase $closeCase,
    ) {}

    /** その人の見本の事件を作って返す */
    public function handle(User $user): CaseFile
    {
        $case = $this->at(9, '20:30', fn () => $this->createCase->handle($user, [
            'title' => '体操着をなぜ忘れる？',
            'scene' => 'family',
            'partner_mode' => true,
        ]));

        [$room, $said, $noPlace] = $this->at(9, '20:35', fn () => [
            $this->addClue->handle($case, 'seen', '忘れた日、体操着の袋は自分の部屋の机の横にあった'),
            $this->addClue->handle($case, 'heard', '長男「朝は覚えていたけど、玄関で靴をはくときには忘れていた」'),
            $this->addClue->handle($case, 'absent', '玄関に、次の日の持ち物を置く場所がない'),
        ]);

        // 相棒（長男）が先に、自分があとに、互いに隠して見立てを書く
        $timetable = $this->at(9, '20:40', fn () => $this->addHypothesis->handle($case, '前の晩に時間割を見ていない', 'partner'));
        $place = $this->at(9, '20:45', fn () => $this->addHypothesis->handle($case, '出がけに目に入る場所に、袋がない', 'self'));

        $this->at(9, '20:50', function () use ($case, $timetable, $place, $room, $said, $noPlace) {
            $this->setFit->handle($timetable, $room, Fit::Fits);
            $this->setFit->handle($timetable, $said, Fit::Contradicts);
            $this->setFit->handle($place, $room, Fit::Fits);
            $this->setFit->handle($place, $said, Fit::Fits);
            $this->setFit->handle($place, $noPlace, Fit::Fits);
            $this->reveal->handle($case);
            $this->changeStatus->handle($timetable->refresh(), HypothesisStatus::Eliminated);
            $this->changeStatus->handle($place->refresh(), HypothesisStatus::Likely);
        });

        $prediction = $this->at(9, '21:00', fn () => $this->makePrediction->handle($place->refresh(), [
            'statement' => '前の晩に袋を玄関のドアノブにかけておけば、この5日間は一度も忘れないはず',
            'confidence' => 70,
            'check_on' => $this->day(-2),
            'check_at' => '19:00',
        ]));

        $this->at(2, '19:10', function () use ($prediction, $place) {
            $this->resolve->handle($prediction, ['result' => 'hit', 'result_note' => '月曜から金曜まで、忘れ物はなかった。']);
            $this->changeStatus->handle($place->refresh(), HypothesisStatus::Confirmed);
        });

        $this->at(2, '19:20', fn () => $this->closeCase->handle($case->refresh(), [
            'status' => 'solved',
            'conclusion' => '覚えていても、出がけに目に入らないと忘れる。置き場所を決めたら忘れなくなった。',
            'rule' => '忘れ物は、覚えることより「出がけに目に入る場所」で防げる',
            'examples' => '鍵・傘・図書館の本・提出するプリントも、玄関の近くに置き場所を作る',
        ]));

        return $case;
    }
}
