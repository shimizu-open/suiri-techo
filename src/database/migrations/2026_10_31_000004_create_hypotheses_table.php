<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 見立て（説明の案）の表を作る */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('hypotheses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('author', 10)->default('self'); // 書いた人（self・partner）
            $table->string('body', 300); // 本文
            $table->string('status', 12)->default('considering'); // 状態（considering・likely・eliminated・confirmed）
            $table->timestamps();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('hypotheses');
    }
};
