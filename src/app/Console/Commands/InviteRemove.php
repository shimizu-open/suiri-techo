<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 招待を外す（php artisan invite:remove {email}）。これから新しく登録できなくなる。
 * すでに登録した人のアカウントは消さない（ログインは Google の ID で照合するので、そのまま使える）
 */
#[Signature('invite:remove {email : 外すメールアドレス}')]
#[Description('招待したメールアドレスを外す（登録済みのアカウントは消さない）')]
class InviteRemove extends Command
{
    /** 招待を探して消す。見つからなければ失敗として知らせる */
    public function handle(): int
    {
        $email = Invitation::normalize($this->argument('email'));
        $deleted = Invitation::where('email', $email)->delete();

        // 1件も消えなければ、そのメールアドレスは招待されていなかった
        if ($deleted === 0) {
            $this->error('招待にないメールアドレスです：'.$email);

            return self::FAILURE;
        }

        $this->info('招待を外しました：'.$email.'（登録済みのアカウントはそのままです）');

        return self::SUCCESS;
    }
}
