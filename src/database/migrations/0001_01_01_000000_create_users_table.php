<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 利用者（users）とログインの記録（sessions）の表を作る。
 * ログインは Google だけなので、パスワードの列とパスワード再設定の表は作らない。Google の利用者 ID（sub）で照合する
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('google_id')->unique(); // Google の利用者 ID（sub）。ログインの照合に使う
            $table->string('name', 100); // 表示名
            $table->string('email')->unique(); // Google のメールアドレス
            $table->string('avatar_url')->nullable(); // アイコンの URL
            $table->string('partner_name', 30)->nullable(); // 相棒の呼び名（例「長男」）。入っていれば相棒モードを使える
            $table->string('default_scene', 10)->default('daily'); // ふだんの場面（daily・family・work）
            $table->string('cue', 100)->nullable(); // 記録のきっかけ（例「夕食のあとに、気になったことを1つ」）
            $table->timestamp('agreed_at')->nullable(); // 利用規約・プライバシーポリシー・年齢の確認に同意した日時
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
