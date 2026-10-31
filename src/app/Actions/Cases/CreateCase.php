<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 事件を作る用事（C-1・C-10）。なぞ・場面・きっかけに加えて、最初の手がかりと最初の見立てを1回で保存できる（一番軽い記録）。
 * 相棒モードの事件では、見立ては隠して書く流れにするので、最初の見立ては受け取らない
 */
class CreateCase
{
    /**
     * 事件を作って返す。$data は StoreCaseRequest で確かめた値
     * （title・scene・partner_mode・origin_kind・origin_label・practice_key・clue_kind・clue_body・hypothesis_body）
     */
    public function handle(User $user, array $data): CaseFile
    {
        return DB::transaction(function () use ($user, $data) {
            $case = $user->caseFiles()->create([
                'title' => $data['title'],
                'scene' => $data['scene'],
                'partner_mode' => (bool) ($data['partner_mode'] ?? false),
                'origin_kind' => $data['origin_kind'] ?? null,
                'origin_label' => $data['origin_label'] ?? null,
                'practice_key' => $data['practice_key'] ?? null,
            ]);

            // 最初の手がかりが書いてあれば、一緒に保存する
            if (filled($data['clue_body'] ?? null)) {
                $case->clues()->create(['kind' => $data['clue_kind'], 'body' => $data['clue_body']]);
            }
            // 最初の見立てが書いてあり、相棒モードでなければ、一緒に保存する（相棒モードは隠して書く流れで足す）
            if (filled($data['hypothesis_body'] ?? null) && ! $case->partner_mode) {
                $case->hypotheses()->create(['body' => $data['hypothesis_body']]);
            }

            return $case;
        });
    }
}
