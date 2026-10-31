<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 招待（新しく登録してよいメールアドレスの許可リスト）の表を作る。運営者が php artisan invite:add で足す */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // 新しく登録してよいメールアドレス（小文字にそろえる）
            $table->string('note', 100)->nullable(); // 運営者のメモ（例「依頼主」）
            $table->timestamp('used_at')->nullable(); // 登録に使われた日時
            $table->timestamps();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
