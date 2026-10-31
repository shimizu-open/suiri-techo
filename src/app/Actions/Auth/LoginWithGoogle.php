<?php

namespace App\Actions\Auth;

use App\Exceptions\LoginRefused;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;

/**
 * Google から戻ってきた人を、推理手帳の利用者として探すか、新しく登録する用事。
 * 照合は Google の sub（getId()）だけで行う（メールアドレスは変わることがあるので鍵にしない）。
 * 新しい人は、Google が確認済みのメールアドレスなら登録する（一般公開）。
 * 招待制（config('suiri.invite_only') が true）のときだけ、invitations にメールアドレスがなければ登録せず、LoginRefused を投げる。
 * 招待の行があれば、一般公開のときも used_at を記録する
 */
class LoginWithGoogle
{
    /** 利用者を返す。登録できないときは LoginRefused（理由は利用者に向けた文） */
    public function handle(GoogleUser $google): User
    {
        $user = User::where('google_id', $google->getId())->first();

        // 前から使っている人なら、アイコンだけ新しくしてそのまま返す（表示名は本人が設定で直すので上書きしない）
        if ($user !== null) {
            $user->forceFill(['avatar_url' => $this->avatar($google)])->save();

            return $user;
        }

        return $this->register($google);
    }

    /** 新しい利用者を登録する。招待制のときは招待を確かめる（招待の行があれば used_at も同時に記録する） */
    private function register(GoogleUser $google): User
    {
        $email = Invitation::normalize((string) $google->getEmail());

        // メールアドレスがないか、Google がそのアドレスを確認していなければ、本人のものか確かめられないので断る
        if ($email === '' || ! $this->verified($google)) {
            throw new LoginRefused('Google アカウントのメールアドレスを確かめられませんでした。確認済みのメールアドレスの Google アカウントでログインしてください。');
        }

        $invitation = Invitation::findFor($email);

        // 招待制で、招待のないメールアドレスなら、新しく登録しない（一般公開のときは招待がなくても進む）
        if (config('suiri.invite_only') && $invitation === null) {
            throw new LoginRefused('推理手帳はいま招待制です。招待を受けたメールアドレスの Google アカウントでログインしてください。');
        }

        // 同じメールアドレスで、別の Google アカウントの利用者がすでにいれば、乗っ取りを防ぐため結びつけない
        if (User::where('email', $email)->exists()) {
            throw new LoginRefused('このメールアドレスは、別の Google アカウントで登録されています。');
        }

        return DB::transaction(function () use ($google, $email, $invitation) {
            $user = new User([
                'name' => $this->displayName($google, $email),
                'email' => $email,
                'avatar_url' => $this->avatar($google),
            ]);
            $user->forceFill(['google_id' => $google->getId()])->save(); // google_id は書き込める列に入れていないので forceFill で入れる
            $invitation?->forceFill(['used_at' => now()])->save(); // 招待の行がない（一般公開で招待なし）ときは何もしない

            return $user;
        });
    }

    /** Google がメールアドレスを確認済みかを返す（項目名が email_verified と verified_email の2通りある） */
    private function verified(GoogleUser $google): bool
    {
        $raw = method_exists($google, 'getRaw') ? $google->getRaw() : [];

        return (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);
    }

    /** 表示名を決める：Google の名前（なければメールの @ の前）を、上限の文字数までにする */
    private function displayName(GoogleUser $google, string $email): string
    {
        $name = trim((string) ($google->getName() ?: Str::before($email, '@')));

        return Str::limit($name, config('suiri.limits.user_name'), '');
    }

    /** アイコンの URL を返す。長すぎて列に入らないときは持たない（表示には使っていない） */
    private function avatar(GoogleUser $google): ?string
    {
        $url = $google->getAvatar();

        return ($url !== null && strlen($url) <= 255) ? $url : null;
    }
}
