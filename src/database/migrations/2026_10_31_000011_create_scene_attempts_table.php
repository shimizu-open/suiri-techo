<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 名推理カードへの自分の推理の表を作る。1人1カードに1行（書き直すと上書き） */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('scene_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scene_key', 40); // database/data/guide/scenes.json のキー
            $table->string('answer', 500); // 自分の推理
            $table->timestamps();
            $table->unique(['user_id', 'scene_key']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('scene_attempts');
    }
};
