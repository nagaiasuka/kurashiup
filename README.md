# kurashiup

## 記事別アクセス分析（PR #35）

WordPress管理画面の「分析」で公開中の通常記事・ガイド記事を日別／月別に表示します。記事名から編集画面へ移動でき、記事検索と20件ごとのページ送りに対応しています。Amazonクリックは従来どおり商品別の累計です。

### 指標・運用

- PVはPHPで処理した公開記事のGETリクエスト数です。ユーザー／セッション数ではありません。ログイン中・パスワード保護記事・プレビュー・フィード・一般的なbotを除外します。キャッシュ/CDN配信・検出できないbotなどによる欠測／過大計測があるため、監査用の厳密なアクセスログではありません。
- PR #35の `{$wpdb->prefix}kurashiup_article_daily` をそのまま利用します。1閲覧につき原子的なupsert 1回、日付は `current_time('Y-m-d')`（設定 → 一般 → タイムゾーン）です。テーマ切替／管理者の `admin_init` でのみ `dbDelta` を実行し、テーブルの存在を確認してスキーマ2を記録します。通常の閲覧でDDLを発行しません。
- 初期化時のローカル日付を計測開始日として保存します。PR #35の既存行があれば最も古い日付を引き継ぎます。導入前のPVは復元できません。初期化以前、将来、記録なし、読取DBエラー、書込エラーを区別します。書込エラーは一度でも発生したら欠測警告を維持します。DB修復と欠測調査後、運用者が `wp option delete kurashiup_article_analytics_write_error` で警告を解除できます（失われたPVは復元しません）。
- 流入元は参照元のホスト名のみ。参照元なし／不正／IPホストは「直接／参照元不明」、同一ホスト・www違い・サブドメインは「サイト内」、その他は外部ドメインとして表示します。参照元のパス・クエリ・検索語・IP・User-Agent・ユーザーIDは保存しません。参照元ヘッダーは欠落・偽装可能であり、広告やセッションの流入元とは一致しません。
- 一覧のSQLは表示中の20記事と対象期間に限定し、値はprepared statement、画面はWordPressのエスケープ関数を使用します。高アクセスでは単一記事／日／参照元への行ロックが発生し得ます。CDNでの計測やキュー化、外部ドメインの保存上限・保持期間は今後の運用課題です。タイムゾーン変更以前の保存済み日付は再変換できません。

### Google Search Consoleの接続

調査時点で、リポジトリ内と確認できたローカルWordPress（active_pluginsが空）にSite Kit／Search Console連携・OAuth設定はありません。本番のプラグイン・Googleプロパティにはアクセスしていないため、本番の導入状況は未確認です。

この実装はGoogle公式 **Site Kit by Google** の既存OAuthとREST権限チェックを再利用します。テーマ内に独自OAuthや秘密情報の入力欄は設けません。Site Kitが未導入／無効なら検索列は「未連携」であり、ゼロを出しません。

1. 本番運用者がWordPressの「プラグイン → インストール済みプラグイン」で既存のSite Kitを確認します。導入済みならその設定を利用してください。なければ公式プラグイン `google-site-kit` を導入します。
2. 「Site Kit → 設定 → 接続済みサービス → Search Console」で、実際の公開URLに対応する既存プロパティを選択します。デプロイ先設定からの候補は `https://amocreo.com/`（URLプレフィックス）または `sc-domain:amocreo.com`（ドメイン）ですが、正しい公開URLと既存プロパティを必ず運用者が確認してください。localhostプロパティや別ドメインを本番指標として利用しないでください。
3. 接続するGoogleアカウントには、対象Search Consoleプロパティの所有者またはフルユーザー権限を付与します。所有権が未確認ならGoogle側の手順で確認します。WordPressでは管理者としてSite KitのGoogleログイン・必要な権限の許可を完了してください。各管理者の閲覧可否はSite Kit側の認証／共有設定にも従います。
4. 通常のSite Kitセットアップでは、独自Google Cloudプロジェクト・クライアントID・クライアントシークレット・リダイレクトURIの新設は不要です。テーマにトークンやJSON鍵を追加しないでください。
5. 「分析」を再読み込みして、接続済みプロパティと記事別の結果を確認します。401/403は権限・再認証、その他の失敗はAPI取得エラーとして区別されます。エラーは再読み込みで再試行できます。

検索列は `google-site-kit/v1/modules/search-console/data/settings` と `.../searchanalytics` をWordPress REST nonce付きで読み取ります。Site Kitが記事のpermalinkを正規化し、Search Console APIのページURL等価フィルターに渡します。`dimensions=date`, `rowLimit=32` で最大31日の行を取得し、日別はその日、月別は返却された日次行の合算を表示します。表示記事だけを逐次取得し、同時リクエストは1、1件のタイムアウトは30秒です。取得データやGoogleの認証情報をテーマ側で永続化しません。

**制約：検索の日付は太平洋時間（America/Los_Angeles）固定です。** PVはWordPress時間ですが、日次APIだけから検索指標をWordPress時間へ正確に変換することはできません。この要件差分は未解消です。Googleの時間単位データは保持期間が短く、過去月全体の再集計には使えないため、推定変換を行いません。画面にも時間境界の違いを表示します。

検索表示回数はGoogle検索結果の表示、検索クリックはその結果からのクリック（ウェブ検索）です。通常2〜3日遅れ、直近は暫定値を含み、保持期間は最大約16か月です。当月はPTの当日までを取得し、取得対象期間を接続状態に表示します。保持期間をまたぐ選択は合計を表示しません。返却日数を各値に併記し、欠落日はゼロ補完しません。データなしは実績ゼロの保証ではなく、反映待ち・非表示のデータ・canonical URL不一致なども含みます。URL変更前やGoogleの選択canonicalが異なる記事の履歴は自動統合しません。導入前の検索データもGoogle側に残っている範囲でのみ取得できます。

参照： [Search Console API](https://developers.google.com/webmaster-tools/v1/searchanalytics/query)、[Site Kitの接続・設定](https://sitekit.withgoogle.com/documentation/supported-services/search-console/)、[Site Kitの実装](https://github.com/google/site-kit-wp/blob/develop/includes/Modules/Search_Console/Datapoints/Search_Analytics_Trait.php)。Site KitのREST互換性はプラグイン更新時に再確認してください。

### 検証

```sh
node --test tests/search-analytics.test.cjs
cd wp-content/themes/kurashiup-theme
npm ci
npm run build
```

PHP 8.3でテーマ全PHPと `tests/*.php` の構文を確認してください。`tests/article-analytics.php` は**専用の破棄可能なlocalhost WordPress**で、テーマ有効化・管理者ID=1・WP-CLIが利用可能な状態で実行します。

```sh
wp eval-file /absolute/path/to/tests/article-analytics.php --allow-root
```

このテストは専用DBに記事・ガイド・商品とPVを作成し、日次／月次、翌月除外、編集リンク、Amazon累計、未記録／開始前、流入元の個人情報除外、Tokyo月境界、実トラッカーのWP日付、bot／ログイン除外、DB／書込エラー、管理画面／DDL権限を確認します。オプションを書き換えるため共有・本番環境で実行しないでください。

Google未接続での画面検証には `tests/sitekit-fixture.php` を専用localhostの `wp-content/mu-plugins/` に一時コピーできます。これは実データではなく、プロパティ欄に `LOCAL MOCK — NOT GOOGLE DATA` と表示されます。`wp option update kurashiup_test_search_state success` で日別12表示/3クリック、月別30表示/5クリック、`empty` でデータなし、`forbidden` で403、`error` で502を再現します。確認後にコピーを削除してください。実Site Kitと同時に使わず、本番配布対象にも含めないでください。

GitHub Actions `Validate theme` はPHP構文・JSモックテスト・既存ビルドを実行します。既存の本番デプロイworkflowはmain/masterへのpush時のみで、今回の作業では実行しません。Google実接続・本番環境・高負荷試験は未確認です。
