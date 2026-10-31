<?php

namespace App\Services\Google\Exceptions;

use RuntimeException;

/**
 * カレンダーとつなぐのを断ったしるし（違う Google アカウント・許可が足りない・鍵がない・専用カレンダーを作れない）。
 * 文は利用者に向けたもので、受付が設定の画面にそのまま出す
 */
class CalendarConnectRefused extends RuntimeException {}
