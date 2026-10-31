<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/** 招待を足す（php artisan invite:add {email} {--note=}）。足したメールアドレスの Google アカウントだけが新しく登録できる */
#[Signature('invite:add {email : 招待するメールアドレス} {--note= : 運営者のメモ（例「依頼主」）}')]
#[Description('招待するメールアドレスを足す（招待制）')]
class InviteAdd extends Command
{
    /** メールアドレスを確かめて招待に足す。すでにあればメモだけ直す */
    public function handle(): int
    {
        $email = Invitation::normalize($this->argument('email'));
        $note = $this->option('note');
        $check = Validator::make(
            ['email' => $email, 'note' => $note],
            ['email' => ['required', 'email'], 'note' => ['nullable', 'string', 'max:'.config('suiri.limits.invitation_note')]],
        );

        // メールアドレスの形がおかしい、またはメモが長すぎるときは、足さずに理由を出して終える
        if ($check->fails()) {
            $this->error(implode(' ', $check->errors()->all()));

            return self::FAILURE;
        }

        $invitation = Invitation::updateOrCreate(['email' => $email], ['note' => $note]);
        $this->info(($invitation->wasRecentlyCreated ? '招待を足しました：' : 'すでに招待されています（メモを直しました）：').$email);

        return self::SUCCESS;
    }
}
