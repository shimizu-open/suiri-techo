<?php

namespace App\Actions\Cases;

use App\Enums\TaskKind;
use App\Models\Hypothesis;
use App\Models\Prediction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 見立てから予想（もしそうなら〜が見えるはず）を立てる用事（C-6）。
 * 予想と同時に「確かめるタスク」（kind=verify・期日＝確かめる日・時刻＝確かめる時刻）を作る。カレンダーへの書き出しはタスクの見張り役がする。
 * 予想の文と確かさは、保存したあとは書き換えない（直す Action は作らない）
 */
class MakePrediction
{
    /** 予想と確かめるタスクを作って、予想を返す。$data は statement・confidence・check_on・check_at・attic_note_id */
    public function handle(Hypothesis $hypothesis, array $data): Prediction
    {
        return DB::transaction(function () use ($hypothesis, $data) {
            $prediction = $hypothesis->predictions()->create([
                'case_file_id' => $hypothesis->case_file_id,
                'user_id' => $hypothesis->user_id,
                'statement' => $data['statement'],
                'confidence' => (int) $data['confidence'],
                'check_on' => $data['check_on'],
                'check_at' => $data['check_at'] ?? null,
                'attic_note_id' => $data['attic_note_id'] ?? null,
            ]);

            $hypothesis->user->tasks()->create([
                'title' => Str::limit(
                    str_replace(':statement', $prediction->statement, config('suiri.cases.verify_task_title')),
                    config('suiri.limits.task_title') - 1,
                    '…',
                ), // 長さは見た目の幅で数えるので、日本語ならおよそ半分の字数で切れる
                'kind' => TaskKind::Verify,
                'due_on' => $prediction->check_on,
                'starts_at' => $prediction->check_at,
                'prediction_id' => $prediction->id,
            ]);
            $hypothesis->caseFile->touch();

            return $prediction;
        });
    }
}
