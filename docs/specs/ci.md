# CI互換性仕様

## 要件

1. `composer.lock` はプロジェクトの最低対応環境であるPHP 8.2でインストール可能であること。
2. PHPUnit、PHPStan、PHP CodeSnifferはComposerで固定したプロジェクト依存を実行すること。
3. Composerによる依存インストールが失敗した場合、依存を必要とする後続処理を実行しないこと。
4. 未認証で保護対象ページへアクセスした場合、テストはログイン画面へのリダイレクトを期待すること。

## 受入条件

- PHP 8.2およびサポート対象の最新PHPで `composer install` が成功する。
- PHPUnit、PHP CodeSniffer、PHPStanがインストール済みの `vendor/` を使用して実行される。
- 全GitHub Actionsジョブが成功する。
