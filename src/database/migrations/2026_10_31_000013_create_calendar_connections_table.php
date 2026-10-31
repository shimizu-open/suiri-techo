<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Google カレンダー連携の表を作る。1人1行。トークンはモデルの encrypted キャストで暗号化して入れる */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // 1人1行
            $table->text('access_token'); // 暗号化して保存する
            $table->text('refresh_token'); // 暗号化して保存する
            $table->timestamp('expires_at'); // access_token の期限
            $table->json('scopes'); // 許可されたスコープ
            $table->string('app_calendar_id')->nullable(); // アプリが作った専用カレンダーの ID
            $table->text('sync_token')->nullable(); // 差分取り込みの印
            $table->string('status', 10)->default('active'); // active・broken
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('calendar_connections');
    }
};
