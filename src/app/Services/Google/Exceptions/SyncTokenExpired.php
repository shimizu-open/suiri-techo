<?php

namespace App\Services\Google\Exceptions;

use RuntimeException;

/**
 * 差分の取り込みの印（syncToken）が古くなったしるし（Google が 410 を返した）。
 * 受け取った側は印を捨てて、全件を取り直す
 */
class SyncTokenExpired extends RuntimeException
{
    /** 決まった文で作る */
    public function __construct()
    {
        parent::__construct('差分の取り込みの印が古くなりました。全件を取り直します');
    }
}
