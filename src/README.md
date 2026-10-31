# src（推理手帳の Laravel アプリ本体）

このフォルダは Laravel のアプリ本体です。コンテナの中では `/data` に見えます。説明はリポジトリの直下にあります。

- 何のアプリか・動かし方：[../README.md](../README.md)
- 解説書（初心者向け）：[../docs/README.md](../docs/README.md)
- AI 向けの道案内：[../CLAUDE.md](../CLAUDE.md)

コマンドはホストではなくコンテナの中で動かします（ホストに PHP はありません）：`docker compose exec -T app php artisan ...`
