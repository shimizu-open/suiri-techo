<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** 公開ページ（このアプリについて・プライバシーポリシー・利用規約）。ログインしていなくても見られる */
class LegalController extends Controller
{
    /** このアプリについて（GET /about）：何をするアプリか・言えること言わないこと・注意書き */
    public function about(): View
    {
        return view('legal.about');
    }

    /** プライバシーポリシー（GET /privacy）。今日の予定を覚えておく分数は、実際に覚える秒数（config/suiri.php）から出す */
    public function privacy(): View
    {
        return view('legal.privacy', [
            'legal' => config('legal'),
            'agendaMinutes' => intdiv((int) config('suiri.calendar.agenda_cache_seconds'), 60),
        ]);
    }

    /** 利用規約（GET /terms） */
    public function terms(): View
    {
        return view('legal.terms', ['legal' => config('legal')]);
    }
}
