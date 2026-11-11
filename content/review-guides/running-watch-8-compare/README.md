# ランニング向けスマートウォッチ8機種比較

確認日：2026-11-11。公開状態：公開済み（投稿ID 49、アイキャッチID 50）。

公開URL：https://amocreo.com/guide/running-watch-8-compare/

## 保存物

- `build.py`：記事本文・比較軸・各機種の公表値・出典・ユーザー指定のアフィリエイトリンクの生成元。
- `assets/` と `assets.json`：商品画像と画像の出典。パスはこのディレクトリからの相対パス。
- `ランニングウォッチ8機種比較.html`：ブラウザ確認用の記事。
- `WordPress公開用本文.html`：テーマが表示するh1を除いた本文。掲載前に画像パスをWordPressメディアのURLに置き換える。
- `WordPress掲載用.html`：h1を含む生成済みフラグメント。
- `比較データ.json`：8機種の重量、GPSモードと公称時間、特徴、出典、アフィリエイトリンク。
- `比較図.svg` と `比較図.png`：比較図。黒い点が公称値の座標、写真は引き出し線で表示。
- `kurashiup-running-watches-share.jpg`：X共有用の1200×630pxアイキャッチ。
- `Xアイキャッチ確認用.html` と `share.cjs`：共有画像の確認・生成用。
- `verify.cjs`：PC・スマートフォン幅での画像読込、記事内アンカー、8機種の描画確認。
- `公開準備.json`：公開用タイトル・スラッグ・概要と未完了の公開作業。

## 更新と表示確認

本文やデータは `build.py` を更新して再生成する。生成物だけの編集は次の生成で上書きされる。

```sh
python3 build.py
npm ci
npm run share
npm run verify
```

ブラウザで `ランニングウォッチ8機種比較.html` を開いて確認する。画像を相対パスで参照するため、記事ディレクトリごと保持する。共有画像と表示確認には、macOSのGoogle Chromeを使用する。

## 公開時の確認

WordPressの `review_guide` として登録し、画像をメディアへアップロードして本文中の相対パスを置き換える。アイキャッチに `kurashiup-running-watches-share.jpg` を指定する。

本番テーマには、公開済みの比較・解説記事とアイキャッチからOGPと `twitter:card=summary_large_image` を出力する処理がある。公開後はログインなしのページで共有タグ、画像URLの応答、PC・スマートフォンの表示、Amazonボタンを確認する。X側の表示確認結果は、サイト側のタグ確認結果と分けて記録する。

Race 2の写真はCoral Orange、ユーザー指定のAmazonリンクはAll Blackのステンレス仕様。その違いは記事に記載している。

今回のリリースでは、GitHub Actionsが記事・画像を梱包し、本番テーマが一度だけ投稿とアイキャッチを登録・公開する。実際の公開結果は `公開準備.json` に記録する。配信処理の詳細は `content/README.md` を参照。
