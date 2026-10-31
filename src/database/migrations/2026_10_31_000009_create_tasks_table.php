<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** タスク（やること）の表を作る。確かめるタスクは予想から作られる */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100); // 題名
            $table->string('memo', 500)->nullable(); // メモ
            $table->date('due_on')->nullable(); // 期日。null は「いつか」
            $table->string('starts_at', 5)->nullable(); // 開始時刻 HH:MM。null は終日
            $table->unsignedSmallInteger('duration_minutes')->nullable(); // 予想時間（長さ・分）。カレンダーの予定の長さにもなる
            $table->unsignedSmallInteger('actual_minutes')->nullable(); // かかった時間（分）。完了のときに任意で入れる
            $table->string('kind', 10)->default('normal'); // 種類（normal・verify）
            $table->foreignId('prediction_id')->nullable()->constrained()->nullOnDelete(); // 確かめるタスクのもとの予想
            $table->timestamp('done_at')->nullable(); // 完了した日時
            $table->string('calendar_event_id', 64)->nullable(); // Google の予定の ID（ハイフンを抜いた UUID）
            $table->timestamp('calendar_synced_at')->nullable(); // 最後に書き出した日時
            $table->timestamps();
            $table->index(['user_id', 'due_on']);
            $table->index(['user_id', 'done_at']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
