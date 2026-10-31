<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 事件（気になる出来事と、その「なぞ」）の表を作る。記録の単位 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('case_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120); // なぞ
            $table->string('scene', 10); // 場面（daily・family・work）
            $table->string('status', 10)->default('open'); // 状態（open・solved・cold）
            $table->string('origin_kind', 10)->nullable(); // きっかけ（task・event・practice）
            $table->string('origin_label', 150)->nullable(); // きっかけの写し（予定の題名と時刻など）。Google の予定 ID は持たない
            $table->string('practice_key', 40)->nullable(); // 練習メニューから作ったときのキー
            $table->boolean('partner_mode')->default(false); // 相棒モードか
            $table->timestamp('revealed_at')->nullable(); // 見せ合った日時。相棒モードで null の間は互いの見立てを隠す
            $table->text('conclusion')->nullable(); // 閉じたときの結論
            $table->timestamp('closed_at')->nullable(); // 閉じた日時
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('case_files');
    }
};
