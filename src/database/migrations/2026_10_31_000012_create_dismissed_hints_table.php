<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 閉じたワトソンのひとことの表を作る。事件と規則の組ごとに1行 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('dismissed_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_file_id')->constrained()->cascadeOnDelete();
            $table->string('rule', 30); // config/watson.php の規則のキー
            $table->timestamp('created_at')->nullable();
            $table->unique(['case_file_id', 'rule']);
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('dismissed_hints');
    }
};
