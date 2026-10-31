<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithGoogle;
use App\Exceptions\LoginRefused;
use App\Http\Controllers\Controller;
use App\Support\Landing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google でログインする（Laravel Socialite）。もらうのは名前・メール・アイコン・ID だけ（openid / email / profile。Socialite の既定）。
 * 1. 「Google でログイン」を押すと Google の画面へ送る（redirect）。state と PKCE で、なりすましの戻りを防ぐ
 * 2. Google から戻ってきたら（callback）、LoginWithGoogle が利用者を探すか、新しく登録する（招待制のときは招待を確かめる）
 */
class GoogleLoginController extends Controller
{
    /** Google の画面へ送り出す（GET /auth/google）。鍵が未設定なら「準備中」と知らせてトップへ戻す */
    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        // Google の鍵がまだ設定されていなければ、トップへ戻して「準備中」と知らせる
        if (! self::enabled()) {
            return redirect()->route('home')->with('error', 'Google でのログインは準備中です。');
        }

        return Socialite::driver('google')
            ->enablePKCE() // 戻りでも enablePKCE() を呼ぶ（呼ばないと確かめの合言葉が Google に送られない）
            ->with(['prompt' => 'select_account']) // 家族で同じ端末を使うことがあるので、毎回アカウントを選ばせる
            ->redirect();
    }

    /**
     * Google から戻ってくる先（GET /auth/google/callback）。利用者を探すか登録してログインさせる。
     * 同意がまだの人は同意の画面へ、済んでいる人は元の画面か最初の画面へ送る
     */
    public function callback(Request $request, LoginWithGoogle $login): RedirectResponse
    {
        // Google の鍵がまだ設定されていなければ、トップへ戻して「準備中」と知らせる
        if (! self::enabled()) {
            return redirect()->route('home')->with('error', 'Google でのログインは準備中です。');
        }

        // Google から利用者の情報を受け取る。キャンセルや時間切れ・合言葉の食い違いで失敗したら、トップへ戻して知らせる
        try {
            $google = Socialite::driver('google')->enablePKCE()->user();
        } catch (Throwable $e) {
            Log::info('Google ログインを中断', ['error' => class_basename($e)]); // メールアドレスなどはログに書かない

            return redirect()->route('home')->with('error', 'Google でのログインを完了できませんでした。もう一度お試しください。');
        }

        // 利用者を探すか登録する。招待がないなどで断られたら、その理由をトップに出す
        try {
            $user = $login->handle($google);
        } catch (LoginRefused $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }

        Auth::login($user, remember: true); // Google ログインは「ログインしたまま」にする
        $request->session()->regenerate(); // セッション ID を作り直す（セッション固定攻撃の対策）

        // まだ同意していない人（初めての人）は、同意の画面へ。済んでいる人は元の画面か最初の画面へ
        if (! $user->hasAgreed()) {
            return redirect()->route('agree.show');
        }

        return redirect()->intended(Landing::url());
    }

    /** Google でのログインが使えるか（クライアント ID とシークレットが両方設定されているか）を返す */
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
