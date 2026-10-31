<?php

namespace Database\Seeders\Demo;

use App\Actions\Cases\AddClue;
use App\Actions\Cases\AddHypothesis;
use App\Actions\Cases\CreateCase;
use App\Actions\Cases\MakePrediction;
use App\Actions\Cases\SetFit;
use App\Enums\Fit;
use App\Models\CaseFile;
use App\Models\User;

/**
 * 見本の事件（仕事の場面）：「前回より右肩が上がって見えた件」。捜査中。
 * 施術者が自分の見たこと・ご本人の言葉・自分の見立てを分けて書き、次回確かめることを残した形。
 * お客さまを特定できること（名前・イニシャル・病名・通院）は書かない。効果を言う書き方もしない
 */
class DemoWorkCase
{
    use TravelsInTime;

    /** 用事（Action）を受け取る */
    public function __construct(
        private CreateCase $createCase,
        private AddClue $addClue,
        private AddHypothesis $addHypothesis,
        private SetFit $setFit,
        private MakePrediction $makePrediction,
    ) {}

    /** その人の見本の事件を作って返す */
    public function handle(User $user): CaseFile
    {
        $case = $this->at(3, '18:40', fn () => $this->createCase->handle($user, [
            'title' => '前回より右肩が上がって見えた件',
            'scene' => 'work',
            'clue_kind' => 'seen',
            'clue_body' => '立った姿勢で、右肩が左より指1本ぶん高く見えた（前回は差が見えなかった）',
        ]));
        $height = $case->clues()->first();

        [$bag, $dressing] = $this->at(3, '18:45', fn () => [
            $this->addClue->handle($case, 'heard', '「この2週間、右手でかばんを持つことが多かった」'),
            $this->addClue->handle($case, 'absent', '上着を脱ぐとき、右肩をかばうような動きは見えなかった'),
        ]);

        [$habit, $mirror] = $this->at(3, '18:50', fn () => [
            $this->addHypothesis->handle($case, 'かばんを右手で持つ時間が増え、右肩を上げる癖がついている'),
            $this->addHypothesis->handle($case, '前回と立つ位置・鏡の角度が違い、見え方が変わっただけ'),
        ]);

        $this->at(3, '18:55', function () use ($habit, $mirror, $height, $bag, $dressing) {
            $this->setFit->handle($habit, $height, Fit::Fits);
            $this->setFit->handle($habit, $bag, Fit::Fits);
            $this->setFit->handle($mirror, $height, Fit::Fits);
            $this->setFit->handle($mirror, $dressing, Fit::Fits);
        });

        $this->at(3, '19:00', fn () => $this->makePrediction->handle($mirror, [
            'statement' => '見え方の違いなら、前回と同じ位置・同じ鏡で見れば、左右の差はほとんど見えないはず',
            'confidence' => 55,
            'check_on' => $this->day(5),
        ]));

        return $case;
    }
}
