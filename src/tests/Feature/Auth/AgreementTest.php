<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Landing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 同意の画面（利用規約・プライバシーポリシー・年齢の確認）と、ログアウト */
class AgreementTest extends TestCase
{
    use RefreshDatabase;

    /** 同意していない人がログインの要る画面を開くと、同意の画面へ送られることを確かめる */
    public function test_an_unagreed_user_is_sent_to_the_agreement(): void
    {
        $user = User::factory()->unagreed()->create();

        $this->actingAs($user)->get(route('settings.show'))->assertRedirect(route('agree.show'));
        $this->actingAs($user)->get(route('agree.show'))->assertOk()
            ->assertSee(config('suiri.agree.age'))
            ->assertSee(route('terms'))->assertSee(route('privacy'));
    }

    /** 2つのチェックのどちらかが欠けていると同意にならず、両方そろうと agreed_at が入って元の画面へ進むことを確かめる */
    public function test_both_checks_are_required_to_agree(): void
    {
        $user = User::factory()->unagreed()->create();
        $this->actingAs($user)->get(route('settings.show')); // 元の画面として覚えさせる

        $this->actingAs($user)->post(route('agree.store'), ['accept_terms' => '1'])
            ->assertSessionHasErrors('confirm_age');
        $this->assertNull($user->refresh()->agreed_at);

        $this->actingAs($user)->post(route('agree.store'), ['accept_terms' => '1', 'confirm_age' => '1'])
            ->assertRedirect(route('settings.show'));
        $this->assertNotNull($user->refresh()->agreed_at);
    }

    /** すでに同意した人が同意の画面を開くと、最初の画面へ送られることを確かめる */
    public function test_an_agreed_user_skips_the_agreement(): void
    {
        $this->actingAs(User::factory()->create())->get(route('agree.show'))->assertRedirect(Landing::url());
    }

    /** ログインしていない人は、設定や同意の画面を開けず、トップへ送られることを確かめる */
    public function test_guests_are_sent_to_the_top_page(): void
    {
        $this->get(route('settings.show'))->assertRedirect(route('home'));
        $this->get(route('agree.show'))->assertRedirect(route('home'));
    }

    /** ログアウトすると、ログインしていない状態になり、トップへ戻ることを確かめる */
    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
