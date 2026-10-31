<?php

namespace Tests\Feature\Ai;

use App\Actions\Account\DeleteAccount;
use App\Actions\Account\ExportUserData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** 退会と書き出し：AI とつないだ許可の行（Passport の表）も消え、書き出しには通行証の番号を出さない */
class AiAccountDataTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** 退会すると、その人の通行証・更新の札・引換券が消える。ほかの人の分と AI の登録は残る */
    public function test_delete_account_removes_passport_rows(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $appId = $this->registerApp();
        $tokenId = $this->grantToken($user->id, $appId);
        $othersToken = $this->grantToken($other->id, $appId);
        DB::table('oauth_auth_codes')->insert(['id' => 'code-1', 'user_id' => $user->id, 'client_id' => $appId, 'revoked' => false]);

        app(DeleteAccount::class)->handle($user);

        $this->assertDatabaseMissing('oauth_access_tokens', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('oauth_refresh_tokens', ['access_token_id' => $tokenId]);
        $this->assertDatabaseMissing('oauth_auth_codes', ['user_id' => $user->id]);
        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $othersToken]);
        $this->assertDatabaseHas('oauth_refresh_tokens', ['access_token_id' => $othersToken]);
    }

    /** 書き出しに、つないでいる AI の名前・戻る先・日時が入り、通行証の番号は入らない */
    public function test_export_includes_connections_without_token_ids(): void
    {
        $user = User::factory()->create();
        $tokenId = $this->grantToken($user->id, $this->registerApp());

        $data = app(ExportUserData::class)->handle($user);

        $this->assertSame('ChatGPT', $data['ai_connections'][0]['name']);
        $this->assertSame('chatgpt.com', $data['ai_connections'][0]['redirect_host']);
        $this->assertStringNotContainsString($tokenId, json_encode($data));
    }
}
