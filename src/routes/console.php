<?php

use App\Jobs\PullCalendarChanges;
use Illuminate\Support\Facades\Schedule;

// Google カレンダーの専用カレンダーで動かされた・消された予定を、15分ごと（config の pull_every_minutes）にタスクへ取り込む。
// worker の箱の schedule:work が時刻を見て仕事を積み、queue:work が片づける
Schedule::job(new PullCalendarChanges)->cron('*/'.config('suiri.calendar.pull_every_minutes').' * * * *');

// 期限の過ぎたキャッシュの行を5分ごとに消す（今日の予定の題名と時刻を、覚えておく5分を過ぎて表に残さないため）
Schedule::command('cache:prune-expired')->everyFiveMinutes();

// AI（ChatGPT・Claude）とつないだ許可の片づけ（毎日）。取り消した通行証・更新の札・引換券を消す
Schedule::command('passport:purge --revoked')->daily();
// 期限の過ぎた通行証は、更新の札の期限（30日）を1日過ぎてから消す（札が生きている間は、つないでいる AI の一覧と取り消しに要るので残す）
Schedule::command('passport:purge --expired --hours='.((config('ai_connect.tokens.refresh_days') + 1) * 24))->daily();
// だれにも使われないまま日がたった AI の自己登録を消す
Schedule::command('ai:prune-apps')->daily();
