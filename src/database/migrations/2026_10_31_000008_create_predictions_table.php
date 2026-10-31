<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 予想（もしそうなら〜が見えるはず）の表を作る。文と確かさは保存後に書き換えない */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hypothesis_id')->constrained()->cascadeOnDelete(); // どの見立てから導いたか
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attic_note_id')->nullable()->constrained()->nullOnDelete(); // 使った屋根裏の知識（任意）
            $table->string('statement', 300); // 「〜が見えるはず」。保存後は書き換えない
            $table->unsignedTinyInteger('confidence'); // 確かさ 50〜95（5刻み）。保存後は書き換えない
            $table->date('check_on'); // 確かめる日
            $table->string('check_at', 5)->nullable(); // 確かめる時刻 HH:MM（任意）
            $table->string('result', 10)->nullable(); // hit・miss・unclear・void。null は答え合わせ待ち
            $table->string('result_note', 300)->nullable(); // 一言
            $table->string('miss_reason', 20)->nullable(); // 外れたときの原因
            $table->timestamp('resolved_at')->nullable(); // 答え合わせした日時
            $table->timestamp('withdrawn_at')->nullable(); // 取り下げた日時
            $table->timestamps(); // created_at が「書いた日時」
            $table->index(['user_id', 'result', 'check_on']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
