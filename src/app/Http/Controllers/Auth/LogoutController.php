<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** ログアウト（POST /logout）。ログインの記録を消し、トップへ戻す */
class LogoutController extends Controller
{
    /** ログアウトして、セッションを作り直し、トップへ戻す */
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate(); // セッションの中身を捨てる
        $request->session()->regenerateToken(); // フォームの合言葉（CSRF トークン）も作り直す

        return redirect()->route('home')->with('status', 'ログアウトしました。');
    }
}
