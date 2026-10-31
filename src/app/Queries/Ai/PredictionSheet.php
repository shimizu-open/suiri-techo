<?php

namespace App\Queries\Ai;

use App\Models\Prediction;

/**
 * 予想1つを、AI に渡す形の配列にする係（get_case と list_due_predictions で同じ形にする）。
 * 文と確かさは書いたあと変わらないので、書いた日時（written_at）も一緒に渡す。状態は言葉にする（答え合わせ待ち・当たり・外れ…・取り下げ）
 */
class PredictionSheet
{
    /** 予想1つを配列にして返す */
    public static function of(Prediction $prediction): array
    {
        return [
            'id' => $prediction->id,
            'statement' => $prediction->statement,
            'confidence' => $prediction->confidence,
            'check_on' => $prediction->check_on,
            'check_at' => $prediction->check_at,
            'written_at' => $prediction->created_at?->toIso8601String(),
            'state' => self::state($prediction),
            'result_note' => $prediction->result_note,
            'miss_reason' => $prediction->miss_reason?->label(),
            'resolved_at' => $prediction->resolved_at?->toIso8601String(),
            'url' => route('predictions.show', $prediction),
        ];
    }

    /** 予想の状態を言葉で返す：取り下げ・答え合わせの結果（当たり・外れ・どちらとも言えない・前提が崩れた）・答え合わせ待ち */
    public static function state(Prediction $prediction): string
    {
        // 取り下げた予想なら「取り下げ」、答え合わせ済みならその結果、どちらでもなければ「答え合わせ待ち」
        if ($prediction->isWithdrawn()) {
            return config('ai_connect.messages.withdrawn');
        }

        return $prediction->result?->label() ?? config('ai_connect.messages.pending');
    }
}
