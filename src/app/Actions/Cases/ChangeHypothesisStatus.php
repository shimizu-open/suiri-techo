<?php

namespace App\Actions\Cases;

use App\Enums\HypothesisStatus;
use App\Models\Hypothesis;
use Illuminate\Support\Facades\DB;

/**
 * 見立ての状態（検討中・有力・消えた・当たり）を変える用事（C-5）。
 * 変えたら「考えを変えた記録」（hypothesis_status_logs）に、前の状態と新しい状態を残す。同じ状態なら何もしない
 */
class ChangeHypothesisStatus
{
    /** 状態を変える。変わったときだけ記録を残し、true を返す */
    public function handle(Hypothesis $hypothesis, HypothesisStatus $to): bool
    {
        $from = $hypothesis->status;
        // 同じ状態を選んだだけなら、記録を増やさない
        if ($from === $to) {
            return false;
        }

        DB::transaction(function () use ($hypothesis, $from, $to) {
            $hypothesis->update(['status' => $to]);
            $hypothesis->statusLogs()->create([
                'user_id' => $hypothesis->user_id,
                'from_status' => $from,
                'to_status' => $to,
            ]);
            $hypothesis->caseFile->touch();
        });

        return true;
    }
}
