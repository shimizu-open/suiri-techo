<?php

namespace Tests\Feature\Legal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 公開ページ（トップ・このアプリについて・プライバシーポリシー・利用規約）と、守りの札（セキュリティのヘッダ） */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /** ログインしていなくても4つのページが開き、どのページにもフッターの注意書きとリンクがあることを確かめる */
    public function test_public_pages_open_for_guests(): void
    {
        // 4つのページを1つずつ開いて、ページごとの言葉とフッターを確かめる
        foreach (['home' => '推理手帳', 'about' => 'お約束できること・できないこと', 'privacy' => 'プライバシーポリシー', 'terms' => '利用規約'] as $route => $text) {
            $this->get(route($route))->assertOk()
                ->assertSee($text)
                ->assertSee('医療機器ではありません')
                ->assertSee(route('privacy'))->assertSee(route('terms'))->assertSee(route('about'));
        }
    }

    /** プライバシーポリシーと利用規約に、config/legal.php の運営者の情報（仮の【】）が出ることを確かめる */
    public function test_legal_pages_show_operator_placeholders(): void
    {
        $this->get(route('privacy'))->assertSee(config('legal.operator_name'))->assertSee(config('legal.contact_email'))
            ->assertSee('ご請求があれば、遅滞なくお知らせします');
        $this->get(route('terms'))->assertSee(config('legal.operator_name'));
    }

    /** 利用規約の「使える人」が、招待制の設定（suiri.invite_only）に合わせて変わることを確かめる */
    public function test_terms_follow_the_invite_only_setting(): void
    {
        config(['suiri.invite_only' => false]);
        $this->get(route('terms'))->assertSee('どなたでも登録できます')->assertDontSee('本アプリは招待制です');

        config(['suiri.invite_only' => true]);
        $this->get(route('terms'))->assertSee('本アプリは招待制です')->assertDontSee('どなたでも登録できます');
    }

    /** プライバシーポリシーに、カレンダーで読む・書く項目、Google の決まり（Limited Use）、記録の消え方が実態どおりに出ることを確かめる */
    public function test_privacy_policy_matches_what_the_app_does(): void
    {
        $this->get(route('privacy'))->assertOk()
            ->assertSee('日先までの予定の題名と時刻')
            ->assertSee('確かめる日」の候補')
            ->assertSee('5 分だけ一時的に覚えておき')
            ->assertSee(config('suiri.calendar.verify_summary'))
            ->assertSee('Limited Use')->assertSee(config('legal.google_user_data_policy'))
            ->assertSee('招待の記録（ある場合）')
            ->assertSee('最長 '.config('legal.backup_days').' 日')
            ->assertSee(config('legal.access_log.file_mb').'MB のファイル '.config('legal.access_log.files').' つ分')
            ->assertDontSee('アクセスの記録は 90 日');
    }

    /** どの画面にも守りの札が付き、ローカルの http では HSTS を付けず、ログイン前の画面は取り置きを禁じないことを確かめる */
    public function test_security_headers_on_guest_pages(): void
    {
        $response = $this->get(route('about'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
        $this->assertStringNotContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /** 本番では、アプリに http で届いても HSTS を付けることを確かめる（CloudFront の後ろで動くため） */
    public function test_hsts_is_sent_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get(route('about'))->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    /** ログイン中の人への応答は、公開ページでも取り置きを禁じる（private, no-store）ことを確かめる */
    public function test_signed_in_responses_are_not_cached(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertStringContainsString('no-store', $this->get(route('about'))->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $this->get(route('settings.show'))->headers->get('Cache-Control'));
    }
}
