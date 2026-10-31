<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use App\Support\Landing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/** Google でのログインと、一般公開・招待制の切り替え（Google の代わりに、決まった返事をする偽物を使う） */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    /** テストごとに、Google でログインする設定（試し用の id と秘密の鍵）を入れておく */
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }

    /** Google の代わりに、決まった利用者（sub・メール・確認済みか）を返す偽物を用意する。戻りでも PKCE を使うことを確かめる */
    private function fakeGoogle(string $sub, string $email, bool $verified = true): void
    {
        $user = (new GoogleUser)->setRaw(['email_verified' => $verified])
            ->map(['id' => $sub, 'name' => 'Irene Adler', 'email' => $email, 'avatar' => null]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('enablePKCE')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /** 送り出しでは、PKCE を付け、アカウントを選ばせてから Google の画面へ送ることを確かめる */
    public function test_redirect_uses_pkce_and_asks_to_choose_an_account(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('enablePKCE')->once()->andReturnSelf();
        $provider->shouldReceive('with')->once()->with(['prompt' => 'select_account'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google'))->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    /** Google の鍵が未設定なら、トップに「準備中」と出て、Google への入口もトップへ戻されることを確かめる */
    public function test_login_shows_preparing_when_keys_are_missing(): void
    {
        config(['services.google.client_id' => null]);

        $this->get(route('home'))->assertOk()->assertSee('準備中')->assertDontSee(route('auth.google'));
        $this->get(route('auth.google'))->assertRedirect(route('home'))->assertSessionHas('error');
        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));
    }

    /** 招待制のとき、招待されたメールアドレスの初めての人は登録され、招待に used_at が入り、同意の画面へ進むことを確かめる */
    public function test_an_invited_new_user_is_registered_and_sent_to_the_agreement(): void
    {
        config(['suiri.invite_only' => true]);
        Invitation::create(['email' => 'Irene@Example.com']);
        $this->fakeGoogle('sub-1', 'irene@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('agree.show'));

        $user = User::firstWhere('email', 'irene@example.com');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('sub-1', $user->google_id);
        $this->assertNull($user->agreed_at);
        $this->assertNotNull(Invitation::findFor('irene@example.com')->used_at);
    }

    /** 招待制のとき、招待のないメールアドレスの人は登録されず、ログインもせず、トップに「招待制です」と出ることを確かめる */
    public function test_an_uninvited_email_is_refused(): void
    {
        config(['suiri.invite_only' => true]);
        $this->fakeGoogle('sub-2', 'moriarty@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertSame(0, User::count());
        $this->get(route('home'))->assertSee('招待制です');
    }

    /** 一般公開のとき（既定）、招待のないメールアドレスの初めての人も登録され、同意の画面へ進むことを確かめる */
    public function test_anyone_can_register_when_the_app_is_open(): void
    {
        config(['suiri.invite_only' => false]);
        $this->fakeGoogle('sub-7', 'lestrade@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('agree.show'));

        $user = User::firstWhere('email', 'lestrade@example.com');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('sub-7', $user->google_id);
        $this->assertSame(0, Invitation::count());
    }

    /** 一般公開のとき（既定）、トップに「招待制です」が出ず、はじめるときの一言だけが出ることを確かめる */
    public function test_home_does_not_say_invite_only_when_the_app_is_open(): void
    {
        config(['suiri.invite_only' => false]);

        $this->get(route('home'))->assertOk()->assertSee(config('suiri.home.open'))->assertDontSee('招待制');
    }

    /** 前から使っている人は、Google の sub で照合され、メールアドレスが変わっていても招待なしでログインできることを確かめる */
    public function test_a_returning_user_is_matched_by_google_sub(): void
    {
        $user = User::factory()->create(['google_id' => 'sub-3', 'email' => 'old@example.com']);
        $this->fakeGoogle('sub-3', 'new@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(Landing::url());

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    /** Google が確認していないメールアドレスでは、招待があっても登録しないことを確かめる（なりすましを防ぐ） */
    public function test_an_unverified_email_cannot_use_an_invitation(): void
    {
        Invitation::create(['email' => 'watson@example.com']);
        $this->fakeGoogle('sub-4', 'watson@example.com', verified: false);

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(Invitation::findFor('watson@example.com')->used_at);
    }

    /** 同じメールアドレスの利用者が別の Google アカウントですでにいるときは、結びつけずに断ることを確かめる */
    public function test_an_email_used_by_another_google_account_is_refused(): void
    {
        User::factory()->create(['google_id' => 'sub-5', 'email' => 'mycroft@example.com']);
        Invitation::create(['email' => 'mycroft@example.com']);
        $this->fakeGoogle('sub-6', 'mycroft@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    /** Google の画面でキャンセルしたなどで受け取りに失敗したら、トップへ戻して知らせることを確かめる */
    public function test_a_failed_google_response_returns_home_with_a_message(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('enablePKCE')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new RuntimeException('cancelled'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'))->assertSessionHas('error');
        $this->assertGuest();
    }

    /** ログイン済みの人は、トップと Google への入口から最初の画面へ送られることを確かめる */
    public function test_a_signed_in_user_skips_the_top_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))->assertRedirect(Landing::url());
        $this->get(route('auth.google'))->assertRedirect(Landing::url());
    }
}
