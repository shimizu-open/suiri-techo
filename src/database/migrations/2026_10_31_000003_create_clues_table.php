<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 手がかり（事件について確かめた事実：見た・聞いた・なかった）の表を作る */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('clues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 持ち主で絞るための写し
            $table->string('kind', 10); // 種類（seen・heard・absent）
            $table->string('body', 500); // 本文
            $table->timestamps(); // created_at が「足した日時」
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('clues');
    }
};
