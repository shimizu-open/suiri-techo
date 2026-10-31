<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AcceptTerms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptTermsRequest;
use App\Support\Landing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 同意の画面（GET/POST /agree）。初めてログインした人に、利用規約・プライバシーポリシーへの同意と、
 * 「13歳以上。18歳未満は保護者の同意を得ています」の確認を求める。済んだら agreed_at が入る
 */
class AgreementController extends Controller
{
    /** 同意の画面を返す。すでに同意済みなら最初の画面へ送る */
    public function show(Request $request): View|RedirectResponse
    {
        // もう同意している人には、この画面は要らない
        if ($request->user()->hasAgreed()) {
            return redirect(Landing::url());
        }

        return view('auth.agree');
    }

    /** 同意を記録して、元の画面か最初の画面へ送る（チェックの確かめは AcceptTermsRequest） */
    public function store(AcceptTermsRequest $request, AcceptTerms $acceptTerms): RedirectResponse
    {
        $acceptTerms->handle($request->user());

        return redirect()->intended(Landing::url())->with('status', 'ようこそ、推理手帳へ。');
    }
}
