<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 見立ての状態を変えた記録（考えを変えた記録）の表を作る。ふりかえりで使う */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('hypothesis_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hypothesis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 12);
            $table->string('to_status', 12);
            $table->timestamp('created_at')->nullable(); // 変えた日時
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('hypothesis_status_logs');
    }
};
