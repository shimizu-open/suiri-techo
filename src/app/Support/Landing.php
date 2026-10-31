<?php

namespace App\Support;

/**
 * ログインしている人の「最初の画面」を決める係。今日の画面（/today）にする
 * （ログインのあと・同意のあと・トップを開いたときに使う）
 */
class Landing
{
    /** 最初の画面の URL を返す */
    public static function url(): string
    {
        return route('today');
    }
}
