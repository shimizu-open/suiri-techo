<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 屋根裏の棚（自分で選んでためる一般則と具体例）の表を作る */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('attic_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_file_id')->nullable()->constrained()->nullOnDelete(); // どの事件からわかったか（任意）
            $table->string('rule', 300); // つまり？（一般則）
            $table->string('examples', 500)->nullable(); // たとえば？（具体例）
            $table->timestamps();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('attic_notes');
    }
};
