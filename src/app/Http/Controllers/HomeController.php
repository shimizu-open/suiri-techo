<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\GoogleLoginController;
use App\Support\Landing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** トップ（GET /）。ログイン前の人には「何をするアプリか」とログインのボタンを見せ、ログイン済みの人は最初の画面へ送る */
class HomeController extends Controller
{
    /** トップを返す。ログイン済みなら最初の画面（いつも今日の画面。行き先は App\Support\Landing が決める）へ送る */
    public function __invoke(Request $request): View|RedirectResponse
    {
        // ログイン済みなら、トップは見せずに今日の画面へ送る
        if ($request->user() !== null) {
            return redirect(Landing::url());
        }

        return view('home', ['googleReady' => GoogleLoginController::enabled()]);
    }
}
