# Thermal Hub

ネットワーク経由でESC/POS対応サーマルプリンターへ印字するCakePHPアプリケーションです。アカウントごとに複数のプリンターと印字原稿を保存し、編集して任意のタイミングで送信できます。

## 必要環境

- 64bit版 PHP 8.2以上（`pdo_mysql`, `intl`, `mbstring`, `iconv`、宛先ラベル機能では `imagick`）
- Composer
- MySQL / MariaDB
- TCP/IP接続可能なESC/POS対応プリンター
- 宛先ラベル機能を使う場合は、PHP Imagick拡張、ImageMagick、日本語フォント（Noto Sans CJK JP）

## セットアップ

```bash
composer install
cp config/app_local.example.php config/app_local.php
bin/cake migrations migrate
```

`config/app_local.php` の `Datasources.default` と `Security.salt` を環境に合わせて設定してください。Webサーバーのドキュメントルートは `webroot/` です。

## Linux + MariaDBでのセットアップ

以下はDebian / Ubuntu系Linuxで、MariaDBとアプリケーションを同じサーバーに配置する例です。ほかのディストリビューションではパッケージ名とWebサーバーの設定方法を読み替えてください。

### 1. 必要なパッケージをインストールする

```bash
sudo apt update
sudo apt install mariadb-server mariadb-client php-cli php-mysql php-intl php-mbstring php-xml php-curl php-zip php-imagick fonts-noto-cjk unzip composer
sudo systemctl enable --now mariadb
```

PHP 8.2以上であることと、必要な拡張が読み込まれていることを確認します。

```bash
php -v
php -m | grep -E 'PDO|pdo_mysql|intl|mbstring|iconv|imagick'
```

### 2. データベースと専用DBユーザーを作成する

MariaDBの管理者として接続します。

```bash
sudo mariadb
```

MariaDB上で次のSQLを実行します。`十分に長いランダムなパスワード`は実際のパスワードに置き換えてください。このユーザーはローカル接続専用で、`thermal_hub`データベースだけを操作できます。

```sql
CREATE DATABASE thermal_hub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'thermal_hub'@'localhost'
  IDENTIFIED BY '十分に長いランダムなパスワード';

GRANT ALL PRIVILEGES ON thermal_hub.*
  TO 'thermal_hub'@'localhost';

EXIT;
```

DBを別サーバーに置く場合は、`localhost`の代わりにアプリケーションサーバーのIPアドレスまたは限定したネットワークを指定します。`'thermal_hub'@'%'`のような無制限の接続元は避け、MariaDBの待受アドレスとファイアウォールも必要な接続元だけに制限してください。

作成したDBユーザーで接続できることを確認します。パスワードをコマンドラインに直接書かないため、`-p`の後は空けたまま実行します。

```bash
mariadb -u thermal_hub -p thermal_hub
```

接続できたら`EXIT;`で終了します。

### 3. アプリケーションを設定する

プロジェクトのルートで依存パッケージとローカル設定ファイルを用意します。

```bash
composer install --no-dev --optimize-autoloader
cp config/app_local.example.php config/app_local.php
```

`config/app_local.php`の該当箇所を次のように変更します。DBパスワードと生成したsaltはリポジトリへコミットしないでください。

```php
'debug' => false,

'Security' => [
    'salt' => 'ここにランダムな文字列を設定',
],

'Datasources' => [
    'default' => [
        'host' => 'localhost',
        'username' => 'thermal_hub',
        'password' => 'DB作成時に設定したパスワード',
        'database' => 'thermal_hub',
        'url' => null,
    ],
],
```

saltは、例えば次のコマンドで生成できます。

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Webサーバーを実行するユーザーが一時ファイルとログを書き込めるようにします。Debian / UbuntuのApacheでは通常`www-data`です。

```bash
sudo chown -R www-data:www-data tmp logs
sudo chmod -R u+rwX,g+rwX tmp logs
sudo chgrp www-data config/app_local.php
sudo chmod 640 config/app_local.php
```

`www-data`以外のユーザーでWebサーバーを実行する場合は、上記のユーザー名とグループ名をその実行ユーザーに読み替えてください。

### 4. テーブルとアプリ内アカウントを作成する

マイグレーションは、設定した`thermal_hub`ユーザーで実行されます。

```bash
bin/cake migrations migrate
bin/cake user create user@example.com "表示名"
```

ここで作成するアプリ内アカウントは、手順2のMariaDBユーザーとは別物です。コマンドのプロンプトで8文字以上のログインパスワードを設定します。

マイグレーションの状態とDB内のテーブルを確認します。

```bash
bin/cake migrations status
mariadb -u thermal_hub -p thermal_hub -e 'SHOW TABLES;'
```

`users`、`printers`、`print_jobs`、`print_logs`、`phinxlog`が表示されればDBの初期化は完了です。Webサーバーのドキュメントルートをこのプロジェクトの`webroot/`に設定し、`/users/login`からログインしてください。

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

## 宛先ラベル

1. プリンター設定で画像印字を有効にし、実機の印字可能幅、用紙幅、ラベル長を登録する。
2. 「宛先ラベル」から郵便番号、住所、宛名、敬称を登録する。
3. プレビューで横書きレイアウトを確認する。
4. 一覧でプリンターを選び、「90度回転して印字」を実行する。

宛先ラベルはサーバー上で画像化され、時計回りに90度回転してESC/POSラスター画像として送信されます。日本語描画には `Noto Sans CJK JP` フォントを使用するため、Webサーバーの実行ユーザーとImageMagickからフォントを参照できるようにしてください。

## カレンダー

1. ナビゲーションの「カレンダー」を開く。
2. 印刷したい年と月を指定して「表示する」を押す。
3. 画像印字が有効な登録済みプリンターを選択し、「サーマルプリンターで印字」を押す。

プリンター設定の印字可能幅に合わせ、ナローサイズの長さ170mmでカレンダーを横長の白黒画像にしてESC/POS送信します。画像印字にはPHP Imagick拡張と日本語フォントが必要です。

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
