<?php

namespace Tests\Feature\Ai;

use Tests\TestCase;

/** プライバシーポリシー・利用規約・このアプリについて に、ChatGPT・Claude とつなぐときの約束が載っている（実際の作りと同じ中身） */
class AiLegalTextTest extends TestCase
{
    /** プライバシーポリシーの「ほかの人に渡すこと」に、本人がつないだ AI には仕事の場面を除く記録が渡ること・写すボタンはアプリから送らないことが載る */
    public function test_privacy_policy_explains_ai_connection(): void
    {
        $this->get(route('privacy'))->assertOk()
            ->assertSee('「あなたが自分の AI とつないだとき」を除き')
            ->assertSee('あなたの記録のうち仕事の場面の事件を除くもの')
            ->assertSee('これはあなたの操作によるもので、運営者が選んで送るものではありません。')
            ->assertSee('通行証は '.config('ai_connect.tokens.access_minutes').' 分、更新の札は '.config('ai_connect.tokens.refresh_days').' 日で切れます')
            ->assertSee('「'.config('ai_connect.copy.button').'」は、文をあなたの端末に写すだけです。本アプリから AI のサービスへは何も送りません。', false);
    }

    /** 利用規約に、つなぐのは本人の判断であること・AI の提案は候補で正しさを約束しないことが載る */
    public function test_terms_say_ai_answers_are_only_candidates(): void
    {
        $this->get(route('terms'))->assertOk()
            ->assertSee('利用者が自分で決めてください')
            ->assertSee('AI の答えや提案は候補であり、正しいことを運営者は約束しません。');
    }

    /** このアプリについて は「アプリの中では AI を使っていない」と、つなぐ窓口があることを書く */
    public function test_about_page_says_no_ai_inside_the_app(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('アプリの中では AI を使っていません');
    }
}
