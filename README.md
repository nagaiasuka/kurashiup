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

### 既存のガイド記事を本番に反映する

1. ローカルの WordPress 管理画面で対象ガイドを編集し、公開状態で保存します。本文に載せる画像はローカルのメディアにアップロードしてから挿入します。アイキャッチ、公開日、調査情報もここで編集できます。
2. リポジトリのルートで対象記事を書き出します。引数は記事 URL の `/guide/` に続くスラッグです。

   ```sh
   docker compose up -d
   python3 scripts/export-guide.py running-watch-8-compare
   git add content/guide-updates/
   git diff --cached --stat
   ```

3. `content/guide-updates/<スラッグ>/` の差分を確認し、作業ブランチにコミットしてプッシュします。通常の開発手順に従って PR をレビューし、`main` へマージします。
4. GitHub Actions の **Deploy theme to XREA** を確認し、本番のガイドを開いて本文、画像、公開日、調査情報を確認します。Actions は配信後に記事 URL にアクセスし、WordPress は配信ファイルのチェックサムを確認してから同じスラッグの公開済みガイドを更新します。

書き出しにはタイトル、本文、抜粋、公開日、調査情報、ローカルの本文画像とアイキャッチが含まれます。既存記事の投稿 ID、スラッグ、公開状態は維持されます。新規ガイドはローカルで公開してから、書き出し時に `--create` を付けます。配信先に記事がまだない場合、WordPress は画像・本文・調査情報を揃えて公開します。以後の改訂もローカルで編集し、再エクスポートしてからマージしてください。
