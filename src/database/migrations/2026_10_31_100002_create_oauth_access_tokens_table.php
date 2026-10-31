<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI とつなぐ（OAuth・Laravel Passport）の表：通行証（アクセストークン）の番号と期限。
 * 通行証の中身（文字列）は保存せず、番号（JWT の id）・持ち主・AI・期限・取り消したかだけを持つ。記録の本文は持たない。
 * 外部キーの連動削除はないので、退会のとき（Actions/Account/DeleteAccount）に user_id で消す。Passport の移行の形のまま
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->char('id', 80)->primary();
            $table->foreignId('user_id')->nullable()->index(); // 通行証の持ち主（読ませてよいと許可した利用者）
            $table->foreignUuid('client_id'); // 通行証を受け取った AI のサービスの登録番号
            $table->string('name')->nullable();
            $table->text('scopes')->nullable(); // 許可した範囲（mcp:use だけ）
            $table->boolean('revoked'); // 取り消したか（設定の「つなぐのをやめる」で true）
            $table->timestamps();
            $table->dateTime('expires_at')->nullable(); // 期限（1時間）
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('oauth_access_tokens');
    }

    /** どの DB に作るかを返す（Passport の設定。ふだんはアプリと同じ DB） */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
