<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ○×（手がかりが見立てに合うか）の表を作る。見立てと手がかりの組ごとに1行 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('clue_hypothesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hypothesis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clue_id')->constrained()->cascadeOnDelete();
            $table->string('fit', 12); // fits（合う）・contradicts（合わない）
            $table->timestamps();
            $table->unique(['hypothesis_id', 'clue_id']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('clue_hypothesis');
    }
};
