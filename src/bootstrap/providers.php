<?php

use App\Providers\AiConnectServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CalendarServiceProvider;

return [
    AppServiceProvider::class,
    CalendarServiceProvider::class, // Google カレンダー連携（今日の予定の窓口を本物に差し替えるので AppServiceProvider のあと）
    AiConnectServiceProvider::class, // ChatGPT・Claude とつなぐ（読むだけの窓口 /mcp と、通行証を出す OAuth の設定）
];
