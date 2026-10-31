<?php

/*
| 画面の住所（ルート）の入口。中身は書かず、routes/web/ にある機能ごとのファイルを読み込むだけ。
| auth（ログイン）・legal（公開ページ）・settings（設定）・today・tasks・cases・guide・review・calendar
*/

// routes/web/ の .php を名前の順に1つずつ読み込む（新しい機能はファイルを置けば読み込まれる）
foreach (glob(__DIR__.'/web/*.php') as $file) {
    require $file;
}
