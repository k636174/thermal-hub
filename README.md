# Thermal Hub

ネットワーク経由でESC/POS対応サーマルプリンターへ印字するCakePHPアプリケーションです。アカウントごとに複数のプリンターと印字原稿を保存し、編集して任意のタイミングで送信できます。

## 必要環境

- 64bit版 PHP 8.2以上（`pdo_mysql`, `mbstring`, `iconv`）
- Composer
- MySQL / MariaDB
- TCP/IP接続可能なESC/POS対応プリンター

## セットアップ

```bash
composer install
cp config/app_local.example.php config/app_local.php
bin/cake migrations migrate
```

`config/app_local.php` の `Datasources.default` と `Security.salt` を環境に合わせて設定してください。Webサーバーのドキュメントルートは `webroot/` です。

## アカウント作成

Web画面からのアカウント登録は行いません。CLIで作成します。

```bash
bin/cake user create user@example.com "表示名"
```

表示されるプロンプトで8文字以上のパスワードを入力します。その後 `/users/login` からログインしてください。

パスワードをリセットする場合は、対象ユーザーのメールアドレスを指定します。

```bash
bin/cake user reset-password user@example.com
```

## 基本操作

1. ログインする。
2. 「プリンター」でホスト、ポート（一般的には9100）、文字コード等を登録する。
3. 「印字データ」でタイトルと本文を保存する。
4. 印字データ一覧からプリンターを選んで印字する。

プリンター接続はサーバーから行われます。ホストの到達性、ファイアウォール、プリンターのRAW TCP印刷設定を確認してください。

## 開発

仕様は [`docs/specification.md`](docs/specification.md)、エージェント向け規約は [`AGENT.md`](AGENT.md) を参照してください。変更時は仕様を先に更新します。

```bash
composer check
```

<!-- The original CakePHP skeleton documentation follows. -->

![Build Status](https://github.com/cakephp/app/actions/workflows/ci.yml/badge.svg?branch=5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

A skeleton for creating applications with [CakePHP](https://cakephp.org) 5.x.

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist cakephp/app [app_name]`.

If Composer is installed globally, run

```bash
composer create-project --prefer-dist cakephp/app
```

In case you want to use a custom app dir name (e.g. `/myapp/`):

```bash
composer create-project --prefer-dist cakephp/app myapp
```

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Demo app

Check out the [5.x-demo branch](https://github.com/cakephp/app/tree/5.x-demo), which contains demo migrations and a seeder.
See the [README](https://github.com/cakephp/app/blob/5.x-demo/README.md) on how to get it running.

## Update

Since this skeleton is a starting point for your application and various files
would have been modified as per your needs, there isn't a way to provide
automated upgrades, so you have to do any updates manually.

## Configuration

Read and edit the environment specific `config/app_local.php` and set up the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.

## Layout

The app skeleton uses [Milligram](https://milligram.io/) (v1.3) minimalist CSS
framework by default. You can, however, replace it with any other library or
custom styles.
