<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 週のふりかえり（週の問いの答え）の表を作る。1人1週に1行 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('weekly_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start'); // 週の始まり（月曜日）
            $table->foreignId('changed_case_file_id')->nullable()->constrained('case_files')->nullOnDelete(); // 今週いちばん考えが変わった事件
            $table->string('abstraction', 500)->nullable(); // つまり？
            $table->string('concrete', 500)->nullable(); // たとえば？
            $table->timestamps();
            $table->unique(['user_id', 'week_start']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reflections');
    }
};
