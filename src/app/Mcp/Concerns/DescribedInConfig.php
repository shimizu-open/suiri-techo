<?php

namespace App\Mcp\Concerns;

/**
 * 道具の見出しと説明文（AI が読む文）を config/ai_connect.php の tools から引く部品。
 * 属性（#[Description]）には config を書けないので、メソッドを上書きする。
 * 説明文の最後には、どの道具にも「正誤を判定しない・予想の文と確かさは変えられない・読むだけ」の一文を足す
 */
trait DescribedInConfig
{
    /** AI に見せる道具の見出しを返す（例：事件の一覧） */
    public function title(): string
    {
        return config('ai_connect.tools.'.$this->name().'.title');
    }

    /** AI が読む道具の説明文を返す。最後に共通の一文を足す */
    public function description(): string
    {
        return config('ai_connect.tools.'.$this->name().'.description').config('ai_connect.tool_tail');
    }
}
