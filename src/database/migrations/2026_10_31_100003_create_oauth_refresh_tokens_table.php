<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI とつなぐ（OAuth・Laravel Passport）の表：更新の札（リフレッシュトークン）の番号と期限。
 * 通行証が1時間で切れたあと、AI がつなぎ直さずに新しい通行証をもらうための札。使うたびに取り替える。
 * user_id は持たないので、退会のときはその人の通行証の番号（access_token_id）で消す。Passport の移行の形のまま
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
            $table->char('id', 80)->primary();
            $table->char('access_token_id', 80)->index(); // どの通行証の札か
            $table->boolean('revoked'); // 取り消したか
            $table->dateTime('expires_at')->nullable(); // 期限（30日）
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('oauth_refresh_tokens');
    }

    /** どの DB に作るかを返す（Passport の設定。ふだんはアプリと同じ DB） */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
