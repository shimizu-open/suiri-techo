<?php

namespace App\Services\Google\Exceptions;

use RuntimeException;

/**
 * Google の許可が取り消された・切れたしるし（トークンの取り直しで invalid_grant が返った）。
 * 連携はすでに status = broken にしてあるので、受け取った側は試し直さずに止める（利用者がつなぎ直すまで待つ）
 */
class GoogleAuthBroken extends RuntimeException
{
    /** 決まった文で作る */
    public function __construct()
    {
        parent::__construct('Google カレンダーの許可が取り消されたか、期限が切れました');
    }
}
