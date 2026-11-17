# KurashiUp

KurashiUp の WordPress サイトと、専用テーマのソースを管理するリポジトリです。テーマは `wp-content/themes/kurashiup-theme/` にあります。

## ローカルで起動する

Docker と Docker Compose が必要です。リポジトリのルートで実行します。

```sh
docker compose up -d
docker compose ps
```

- WordPress は [localhost:8080](http://localhost:8080) で開きます。初回は画面の案内に沿ってセットアップし、管理画面の「外観 → テーマ」で **KurashiUp Theme** を有効化します。
- データベースの確認には [phpMyAdmin](http://localhost:8081) を使えます。
- 作業後は `docker compose down` で停止します。ローカルのデータベースは Docker の名前付きボリューム `db_data` に保存されます。

## テーマのアセットをビルドする

Node.js 22.12 以上の 22 系と npm を使用します。初回や依存関係の変更後は `npm ci` を実行します。

```sh
cd wp-content/themes/kurashiup-theme
npm ci
npm run dev
```

`npm run dev` は Vite の監視ビルドです。ブラウザーでは WordPress の URL を開いて確認します。単発のビルドは `npm run build` です。テーマの CSS・JavaScript は `src/` のソースを編集し、生成された `assets/css/app.css` と `assets/js/app.js` の差分を確認します。

## サイトとテーマの仕様

- 商品はカスタム投稿タイプ `product` で管理します。商品カテゴリ、ブランド、商品タグを付けられます。商品一覧と詳細ページのテンプレートはテーマ直下、商品カードなどの共通部品は `template-parts/` にあります。
- 比較ガイドはカスタム投稿タイプ `review_guide` で管理します。Git で管理する記事の本文、比較データ、画像、公開手順は [記事コンテンツの管理](content/README.md) を参照してください。
- 商品取り込みは認証付き WordPress REST API の `POST /wp-json/kurashiup/v1/import-product` を使用します。投稿タイプや API の実装はテーマの `inc/` にあります。

## 開発と本番デプロイ

1. `main` の変更が反映された `develop` から作業ブランチを作成します。テーマを変更したらローカルでビルドし、変更したページを確認します。
2. 作業ブランチから `develop` にプルリクエストを作成します。本番に反映する変更を確認したら、`main` にマージします。本番に先に入れた修正は `develop` にも反映します。
3. `main` へのプッシュで GitHub Actions の [Deploy theme to XREA](.github/workflows/deploy.yml) が起動します。手動実行する場合は GitHub の **Actions** で同ワークフローを開き、**Run workflow** で `main` を選びます。
4. ワークフローは Node.js 22 でテーマをビルドし、対象の承認済み記事を梱包して、XREA のテーマディレクトリに FTPS で同期します。接続情報は GitHub Actions の Secrets `XREA_HOST`、`XREA_USER`、`XREA_PASSWORD` で設定します。
5. Actions の実行結果を確認し、本番で変更対象のページを開いて表示と動作を確認します。記事公開を含む場合は、公開 URL とアイキャッチも確認します。
