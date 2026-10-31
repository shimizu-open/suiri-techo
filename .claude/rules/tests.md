---
paths:
  - "src/tests/**"
  - "src/phpunit.xml"
  - "src/database/factories/**"
  - "src/database/seeders/**"
---

# テスト

- テストは **sqlite in-memory**（`phpunit.xml`）。`orderByRaw` や日付の関数は MySQL と sqlite の両方で動く書き方にする。日付だけの列は `Y-m-d` の文字列で比べる。
- **`withoutVite()`**（`tests/TestCase.php`）なので、テストに `npm run build` は要らない。
- **外とのやりとりは偽物**：Google ログインは Socialite のドライバーを Mockery の偽物に差し替える（`Socialite::shouldReceive('driver')`。手本は `tests/Feature/Auth`）、カレンダーの API は `App\Contracts\CalendarApi` の偽物（メモリに書くだけ）を `CalendarServiceProvider` の結びつけで差し替える（テストのときは偽物の `FakeCalendarApi` が結ばれ、`Http::preventStrayRequests()` で外への通信も止まる）。HTTP を使う所は `Http::fake()`。テストから本物の Google に届く形にしない。キューに積むかは `Queue::fake()` で確かめる。
- **機能ごとに「他人の記録が見えない」テストを1つ以上**書く（ほかの利用者の事件・手がかり・見立て・予想・タスク・棚・ふりかえりを開く・直す・消すと 403 か 404）。
- 壊さない決まりのテストを置く：予想の文と確かさが保存後に変わらない、カレンダーに送る中身が題名・日時・リンク・タスクの番号だけ、トークンが暗号化されて JSON に出ない、退会でその人の行がすべて消える、ログイン後の応答に `Cache-Control: private, no-store`。
- **テストのメソッドの手前に、何を確かめるテストかを日本語のコメント**で書く（`bin/code_comments.py` が止める）。名前は英語のままでよい（`test_owner_only_can_view_case`）。
- 置き場所：`tests/Feature/{機能}/{動作}Test.php`、`tests/Unit/{相手}/`。1ファイル 300 行まで。共通の準備は、その機能のフォルダの trait に（例：`tests/Feature/Cases/Concerns/BuildsCases.php`・`tests/Feature/Calendar/CalendarHelpers.php`）。
- `#[Fillable]` に入っていない列をテストで入れるときは `forceCreate`。
- 「risky（出力の後片付けがない）」になったら、Blade の閉じていない `@section` や `@push` を疑う。
- 動かし方：`docker compose exec -T app php artisan test --compact`（1つだけなら `--filter=名前`）。
