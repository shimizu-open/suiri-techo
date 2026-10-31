/*
  画面の動きの入口。ここは読み込むだけにして、動きは resources/js/parts/{部品}.js に1部品1ファイルで書く。
  土台の部品（消す前の確認など）は JavaScript なしで動く（<details> を使う）。
*/
import './parts/suggest-chips.js';
import './parts/minute-chips.js';
import './parts/stepper.js';
import './parts/ai-copy.js';
