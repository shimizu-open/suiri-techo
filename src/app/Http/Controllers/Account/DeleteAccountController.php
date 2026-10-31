<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\DeleteAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DeleteAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** 退会（DELETE /account）。確認の言葉を確かめ、ログアウトしてから、その人のデータをすべて消してトップへ戻す */
class DeleteAccountController extends Controller
{
    /**
     * 退会する。先にログアウトする（消したあとの利用者をログアウトの処理が保存し直さないように）。
     * 確認の言葉の確かめは DeleteAccountRequest、消すのは DeleteAccount
     */
    public function __invoke(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $user = $request->user();
        Auth::logout();
        $deleteAccount->handle($user);
        $request->session()->invalidate(); // セッションの中身を捨てる
        $request->session()->regenerateToken(); // フォームの合言葉（CSRF トークン）も作り直す

        return redirect()->route('home')->with('status', '退会しました。記録はすべて消えました。');
    }
}
