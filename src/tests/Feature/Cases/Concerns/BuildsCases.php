<?php

namespace Tests\Feature\Cases\Concerns;

use App\Actions\Cases\MakePrediction;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\User;

/** 事件のテストの共通の準備：利用者と、手がかり・見立て・予想のそろった事件を作る */
trait BuildsCases
{
    /** 同意済みの利用者を作る（相棒の名前などを渡せる） */
    protected function member(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /** その人の事件を作る（形を渡せる） */
    protected function caseOf(User $user, array $attributes = []): CaseFile
    {
        return CaseFile::factory()->for($user)->create($attributes);
    }

    /**
     * 手がかり1つ・見立て1つ・（頼めば）予想1つ と確かめるタスクのそろった事件を作る。
     * 予想は MakePrediction を通して作るので、確かめるタスクもできる
     *
     * @return array{0: User, 1: CaseFile, 2: Clue, 3: Hypothesis, 4: ?Prediction}
     */
    protected function board(bool $withPrediction = false, array $caseAttributes = []): array
    {
        $user = $this->member();
        $case = $this->caseOf($user, $caseAttributes);
        $clue = Clue::factory()->create(['case_file_id' => $case->id, 'body' => '玄関に体操着の袋がなかった']);
        $hypothesis = Hypothesis::factory()->create(['case_file_id' => $case->id, 'body' => '前の晩に時間割を見ていない']);
        $prediction = null;
        // 頼まれたら、予想（と確かめるタスク）も作る
        if ($withPrediction) {
            $prediction = app(MakePrediction::class)->handle($hypothesis, [
                'statement' => '前の晩に時間割を見れば、袋が玄関にあるはず',
                'confidence' => 70,
                'check_on' => today()->toDateString(),
            ]);
        }

        return [$user, $case, $clue, $hypothesis, $prediction];
    }
}
