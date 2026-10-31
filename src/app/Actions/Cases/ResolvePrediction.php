<?php

namespace App\Actions\Cases;

use App\Enums\PredictionResult;
use App\Models\Prediction;
use Illuminate\Support\Facades\DB;

/**
 * 予想の答え合わせを記録する用事（C-7・C-8）。結果（当たり・外れ・どちらとも言えない・前提が崩れた）と一言、
 * 外れなら「何を見落とした？」を残し（ノーベリ帳に載る）、確かめるタスクを完了にする。
 * 記録できるのは答え合わせ待ちの予想だけ。答え合わせ済み・取り下げ済みなら何もしない
 * （受付の ResolvePredictionRequest が先に断る。ここは同時に取り下げ・答え合わせされたときの最後の守り。WithdrawPrediction と同じ形）
 */
class ResolvePrediction
{
    /** 答え合わせ待ちの予想なら記録する。$data は result・result_note・miss_reason。予想の文と確かさには触らない。記録したら true、何もしなければ false */
    public function handle(Prediction $prediction, array $data): bool
    {
        $result = PredictionResult::from($data['result']);

        return DB::transaction(function () use ($prediction, $data, $result) {
            // 保存されている今の状態を、ほかの書き込みを待たせて読み直す（同時の取り下げや二重の送信と食い違わないように）
            $current = Prediction::whereKey($prediction->getKey())->lockForUpdate()->first();

            // もう答え合わせ済み・取り下げ済み（または消えた）なら、何もしない（先に書いた結果を上書きしない）
            if ($current === null || ! $current->isPending()) {
                return false;
            }

            $prediction->update([
                'result' => $result,
                'result_note' => $data['result_note'] ?? null,
                'miss_reason' => $result->isMiss() ? $data['miss_reason'] : null, // 外れのときだけ原因を残す
                'resolved_at' => now(),
            ]);

            $task = $prediction->task;
            // 確かめるタスクがまだ済んでいなければ、完了にする（カレンダーにも見張り役が知らせる）
            if ($task !== null && ! $task->isDone()) {
                $task->update(['done_at' => now()]);
            }
            $prediction->caseFile->touch();

            return true;
        });
    }
}
