<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI とつなぐ（OAuth・Laravel Passport）の表：つないでくる AI のサービスの登録（ChatGPT・Claude など）。
 * AI が自分で登録する（動的クライアント登録。laravel/mcp の POST /oauth/register）たびに1行できる。名前は AI の自己申告。
 * 利用者の記録ではない（user_id を持たない）。だれにも使われないまま日がたった登録は ai:prune-apps が消す。Passport の移行の形のまま
 */
return new class extends Migration
{
    /** 表を作る */
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableMorphs('owner'); // 持ち主（AI の自己登録では空）
            $table->string('name'); // AI が名乗った名前（同意の画面に出す）
            $table->string('secret')->nullable(); // 合言葉（AI の自己登録では使わない。PKCE で守る）
            $table->string('provider')->nullable();
            $table->text('redirect_uris'); // 許可のあとに戻る先（chatgpt.com・claude.ai・claude.com だけ通す）
            $table->text('grant_types'); // 使える許可の種類（認可コード・更新の札）
            $table->boolean('revoked'); // 登録を無効にしたか
            $table->timestamps();
        });
    }

    /** 表を消す（作る前に戻す） */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }

    /** どの DB に作るかを返す（Passport の設定。ふだんはアプリと同じ DB） */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
