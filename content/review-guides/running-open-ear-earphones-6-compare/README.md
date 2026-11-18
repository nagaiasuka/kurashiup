# イヤホン6機種比較のX共有画像

ウォッチ8機種の記事と同じく、1200×630のJPEGをアイキャッチとXへの直接添付用に使用する。

- `share.cjs`: Git管理の `content/guide-updates/running-open-ear-earphones-6-compare/guide.json` から比較図を読み込み、商品画像を埋め込んで共有画像を生成する。
- `kurashiup-running-earphones-share.jpg`: Xに添付できる画像。
- `post.txt`: 画像添付用の投稿案。図のタップ操作は記事内の図を指すことを明記する。

`npm ci && npm run share` で再生成する。Google Chromeを使用し、6機種の写真読込・描画領域・文字と画像の切れを確認する。画像のハッシュ名でコピーした配信用画像と `guide.json` のアイキャッチ指定も更新する。その差分を通常の本番デプロイに含める。

サイトのOGPタグと画像の取得確認は、X上での実表示確認とは分けて記録する。HTTP 200や正しいOGPタグだけでは、既存ポストのカード表示まで保証しない。
