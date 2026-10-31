<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI とつなぐ（OAuth・Laravel Passport）の表：デバイスの許可の番号。
 * 推理手帳ではデバイスの許可を使わない（AiConnectServiceProvider で切っている）が、Passport の掃除（passport:purge）が
 * この表も片づけるので、空のまま作っておく。退会のときは user_id で消す。Passport の移行の形のまま
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('oauth_device_codes', function (Blueprint $table) {
            $table->char('id', 80)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignUuid('client_id')->index();
            $table->char('user_code', 8)->unique();
            $table->text('scopes');
            $table->boolean('revoked');
            $table->dateTime('user_approved_at')->nullable();
            $table->dateTime('last_polled_at')->nullable();
            $table->dateTime('expires_at')->nullable();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('oauth_device_codes');
    }

    /** どの DB に作るかを返す（Passport の設定。ふだんはアプリと同じ DB） */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
