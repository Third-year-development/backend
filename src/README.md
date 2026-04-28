# WhisperSystem - バックエンドAPI

X（旧Twitter）みたいなSNSアプリ「Whisper」のバックエンド（Laravel版）リポジトリです。
Androidアプリ（クライアント）と連携し、ユーザー認証、ささやき（投稿）機能、フォロー機能などのデータ処理を行うRESTful APIを提供します。

## 📖 概要 (Overview)
「Whisper」は、日常の何気ない「ささやき」を共有できるシンプルなSNSアプリケーションです。
本リポジトリでは、クライアントアプリからリクエストを受け取り、データベースと連携してJSON形式でレスポンスを返すAPIサーバーを構築しています。

## ✨ 主な機能 (Features)
- **ユーザー管理**: ユーザー登録、ログイン・ログアウト（Laravel SanctumによるAPIトークン認証）、プロフィール編集
- **投稿機能**: テキストの投稿（ささやき）、投稿の削除、タイムライン表示
- **リアクション機能**: 投稿へのいいね、いいね解除
- **フォロー機能**: 他ユーザーのフォロー、フォロワーの投稿一覧取得

## 🛠 技術スタック (Tech Stack)
- **言語**: PHP
- **フレームワーク**: Laravel
- **データベース**: MySQL

## 🚀 環境構築手順 (Setup Instructions)

開発環境をローカルに構築するための手順です。

1. **リポジトリのクローン**
   ```bash
   git clone <リポジトリのURL>
   cd backend-main/src
   ```

2. **依存パッケージのインストール**
   ```bash
   composer install
   ```

3. **環境変数の設定**
   `.env.example` をコピーして `.env` ファイルを作成します。
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   作成した `.env` ファイルを開き、自身のデータベース設定に合わせて以下の項目を編集してください。
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE= 　# 作成したデータベース名
   DB_USERNAME= 　# DBのユーザー名
   DB_PASSWORD= 　# DBのパスワード
   ```

## 📡 API仕様 (API Documentation)
主なAPIエンドポイントの例です。（ルートは `routes/api.php` を参照してください）

| メソッド | エンドポイント | 説明 | 要認証 |
| --- | --- | --- | --- |
| POST | `/api/register` | ユーザー新規登録 | - |
| POST | `/api/login` | ログイン（トークン発行） | - |
| POST | `/api/logout` | ログアウト（トークン破棄） | ◯ (Sanctum) |
| GET | `/api/user` | ログイン中のユーザー情報取得 | ◯ (Sanctum) |
| GET | `/api/whispers` | タイムライン（投稿一覧）取得 | ◯ |
| POST | `/api/whispers` | 新規投稿（ささやき） | ◯ |