<?php

namespace App\Services\Google\Exceptions;

use RuntimeException;

/**
 * Google への問い合わせがうまくいかなかったしるし（つながらない・500・回数制限など）。
 * 後回しの仕事はこれを受けて、あとで試し直す。文にはどの操作かと応答の番号だけを入れる（トークンや題名は入れない）
 */
class GoogleApiFailed extends RuntimeException
{
    /** どの操作で、どの番号（0 はつながらなかった）が返ったかを覚えて作る */
    public function __construct(public readonly string $operation, public readonly int $status = 0)
    {
        parent::__construct("Google への問い合わせに失敗しました（{$operation}・{$status}）");
    }
}
