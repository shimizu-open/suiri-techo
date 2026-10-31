<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI とつなぐ（OAuth・Laravel Passport）の表：一度きりの引換券（認可コード）。
 * 利用者が同意の画面で「許可する」を押したときに1行でき、AI が数分のうちに通行証と引き換える。
 * 外部キーの連動削除はないので、退会のとき（Actions/Account/DeleteAccount）に user_id で消す。Passport の移行の形のまま
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->char('id', 80)->primary();
            $table->foreignId('user_id')->index(); // 許可した利用者
            $table->foreignUuid('client_id'); // 許可を求めた AI のサービスの登録番号
            $table->text('scopes')->nullable(); // 許可した範囲（mcp:use だけ）
            $table->boolean('revoked'); // 取り消したか
            $table->dateTime('expires_at')->nullable(); // 期限（10分）
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('oauth_auth_codes');
    }

    /** どの DB に作るかを返す（Passport の設定。ふだんはアプリと同じ DB） */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
