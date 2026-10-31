<?php

namespace App\Actions\Cases;

use App\Models\CaseFile;
use Illuminate\Support\Facades\DB;

/**
 * 事件を閉じる用事（C-9）。解決か迷宮入りにし、結論と閉じた日時を残す。
 * 「つまり？」（一般則）が書いてあれば、「たとえば？」と一緒に屋根裏の棚の知識として残す（どの事件からわかったかも結ぶ）
 */
class CloseCase
{
    /** 事件を閉じる。$data は status・conclusion・rule・examples */
    public function handle(CaseFile $case, array $data): void
    {
        DB::transaction(function () use ($case, $data) {
            $case->update([
                'status' => $data['status'],
                'conclusion' => $data['conclusion'] ?? null,
                'closed_at' => now(),
            ]);

            // 「つまり？」が書いてあれば、屋根裏の棚に入れる
            if (filled($data['rule'] ?? null)) {
                $case->user->atticNotes()->create([
                    'case_file_id' => $case->id,
                    'rule' => $data['rule'],
                    'examples' => $data['examples'] ?? null,
                ]);
            }
        });
    }
}
