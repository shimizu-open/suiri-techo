<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** 招待の一覧を表で出す（php artisan invite:list）。登録に使われたかどうかも見える */
#[Signature('invite:list')]
#[Description('招待したメールアドレスの一覧を出す')]
class InviteList extends Command
{
    /** 招待をメールアドレスの順に並べて表にする */
    public function handle(): int
    {
        $invitations = Invitation::orderBy('email')->get();

        // まだ1件もなければ、その旨だけ出して終える
        if ($invitations->isEmpty()) {
            $this->info('招待はまだありません。php artisan invite:add {email} で足せます。');

            return self::SUCCESS;
        }

        $this->table(
            ['メールアドレス', 'メモ', '登録に使われた日時', '足した日時'],
            $invitations->map(fn (Invitation $i) => [
                $i->email,
                $i->note ?? '',
                $i->used_at?->format('Y-m-d H:i') ?? '（まだ）',
                $i->created_at?->format('Y-m-d H:i'),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
