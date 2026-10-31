<?php

namespace Tests\Feature\Ai\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * 「ChatGPT・Claude とつなぐ」のテストの共通の準備。外の AI には届かない（すべて手元の HTTP テスト）。
 * テスト用の鍵・AI の自己登録・PKCE の合言葉・同意の画面の住所・窓口への要求・通行証の行を作る
 */
trait ConnectsAi
{
    /** ChatGPT の戻る先の形（https://chatgpt.com/connector/oauth/{番号}） */
    protected const CHATGPT_CALLBACK = 'https://chatgpt.com/connector/oauth/test-callback';

    /** 1回だけ作ったテスト用の鍵の組（秘密の鍵・公開の鍵） */
    private static ?array $testKeys = null;

    /** テスト用の鍵の組（RSA）を1回だけ作り、Passport の設定に入れる（本物の鍵は使わない。通行証を出す・確かめるのに要る） */
    protected function usePassportKeys(): void
    {
        // まだ作っていなければ作る（鍵を作るのは少し時間がかかるので、テスト全体で1回だけ）
        if (self::$testKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            self::$testKeys = [$private, openssl_pkey_get_details($key)['key']];
        }
        config(['passport.private_key' => self::$testKeys[0], 'passport.public_key' => self::$testKeys[1]]);
    }

    /** AI の自己登録（POST /oauth/register）をして、登録の番号を返す */
    protected function registerApp(string $redirect = self::CHATGPT_CALLBACK, string $name = 'ChatGPT'): string
    {
        return $this->postJson('/oauth/register', ['name' => $name, 'redirect_uris' => [$redirect]])->assertCreated()->json('client_id');
    }

    /** PKCE の合言葉（verifier）と、その S256 のしるし（challenge）を返す */
    protected function pkce(): array
    {
        $verifier = Str::random(64);

        return [$verifier, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
    }

    /** 同意の画面の住所を返す（AI が利用者のブラウザで開く形。resource も付ける） */
    protected function authorizeUrl(string $appId, string $challenge, string $redirect = self::CHATGPT_CALLBACK): string
    {
        return '/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $appId,
            'redirect_uri' => $redirect,
            'scope' => 'mcp:use',
            'state' => 'state-123',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'resource' => url('/mcp'),
        ]);
    }

    /** 窓口（POST /mcp）に JSON-RPC の要求を送る（古い版の形。AI のサービスのサーバーと同じく Origin は付けない） */
    protected function callMcp(string $method, array $params = [], array $headers = []): TestResponse
    {
        return $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], [
            'Accept' => 'application/json, text/event-stream',
            ...$headers,
        ]);
    }

    /** その人のその AI への通行証の行を1つ作り、番号を返す（$refreshDays を渡すと更新の札も作る） */
    protected function grantToken(int $userId, string $appId, int $expiresInMinutes = 60, ?int $refreshDays = 30): string
    {
        $id = Str::random(80);
        DB::table('oauth_access_tokens')->insert([
            'id' => $id, 'user_id' => $userId, 'client_id' => $appId, 'scopes' => '["mcp:use"]', 'revoked' => false,
            'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
        // 更新の札の日数が渡されたときだけ、札も作る
        if ($refreshDays !== null) {
            DB::table('oauth_refresh_tokens')->insert([
                'id' => Str::random(80), 'access_token_id' => $id, 'revoked' => false, 'expires_at' => now()->addDays($refreshDays),
            ]);
        }

        return $id;
    }
}
