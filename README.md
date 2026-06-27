# クリプトカード診断ガイド

海外在住の日本人向けに、仮想通貨カード/取引所/送金サービスを比較・診断するアフィリエイトサイト。
WordPress.com Business 上で、Git 管理のブロックテーマ + ACF（無料版）+ 中核プラグインで構築する。

設計: `docs/superpowers/specs/2026-06-24-kaigai-crypto-card-navi-design.md`

## 構成

```
wp-content/
  themes/kcc-navi/        ブロックテーマ（FSE）
  plugins/kcc-core/       service CPT / タクソノミー / ACF / 比較表・スペックブロック
data/
  services.seed.json      カードのシード（競合解析由来 / 公開前に公式で再確認）
  content.seed.json       ランキング記事・法務ページのシード
scripts/
  seed.sh                 wp-env にシード投入
  import-seed.php         service 投入（wp eval-file）
  import-content.php      記事・ページ投入（wp eval-file）
```

## ローカル開発（wp-env / Docker 必須）

> Docker Desktop が必要。未インストールの場合は先に導入する。

ACF は**無料版**（wp.org の `advanced-custom-fields`）で動く。比較表・スペックは WordPress 標準の動的ブロック（`render.php`）で実装しており、ACF Pro は不要。`.wp-env.json` が起動時に ACF 無料版を自動取得する。

> ACF Pro は Phase 2 以降の管理画面 UX（リピーター/リレーション UI 等）で任意。導入する場合は `wp-content/plugins/advanced-custom-fields-pro/` に配置（ライセンス品のため Git 管理外）し、`.wp-env.json` の plugins を差し替える。

1. `npm install`
2. `npm run wp:start` → <http://localhost:8888> （管理は /wp-admin、admin/password）
3. `npm run seed` → service 12件 + 記事/法務ページを投入
4. テーマ「KCC Navi」を有効化し、ホーム/記事/カード個別/法務ページを確認

## 検証チェックリスト

- ホーム・ランキング記事で比較表が描画され、並び替え（おすすめ/還元率/作成費/年会費）とフィルタ（日本可/物理）が動作する
- カード個別ページで ACF スペックが表示される
- 法務ページ5種（運営者情報/免責事項/PR表記/プライバシー/お問い合わせ）が存在し、フッターからリンクされる
- 全ページに PR表記（広告・アフィリエイトを含む）が表示される
- `view-source` で GA4 gtag が `<head>` に出力される（テストID）
- `GET /wp-json/wp/v2/service` が ACF フィールド込みの JSON を返す

## アナリティクス（GA4）

`functions.php` が `wp_head` で gtag を注入する。測定IDは定数 `KCC_GA4_MEASUREMENT_ID`。

- ローカル: `.wp-env.json` の `config.KCC_GA4_MEASUREMENT_ID`（テストIDを設定済み）
- 本番（WP.com）: `wp-config.php` に `define( 'KCC_GA4_MEASUREMENT_ID', 'G-XXXXXXXXXX' );` を追加、または環境変数経由で注入

定数が空の場合は何も出力しない（誤計測防止）。

## Google Search Console（公開後）

1. <https://search.google.com/search-console> で「URL プレフィックス」を本番ドメインで追加
2. 所有権確認は次のいずれか:
   - **HTMLタグ**: 発行された `<meta name="google-site-verification" ...>` を `parts/header.html` または `functions.php` の `wp_head` に追加
   - **DNS**: WP.com のドメイン設定で TXT レコードを追加
3. サイトマップ `https://<domain>/wp-sitemap.xml`（WP標準）を送信
4. インデックス登録をリクエスト

## デプロイ（WordPress.com Business / GitHub Deployments）

1. GitHub にこのリポジトリを push
2. WP.com Business 管理 → 設定 → GitHub Deployments で連携
3. デプロイ対象ディレクトリを `wp-content/` にマッピング（テーマ・プラグインを反映）
4. ステージングで検証チェックリストを再確認 → 本番反映
5. ACF 無料版は WP.com 側でインストール、GA4 測定ID は本番側で設定（リポジトリには含めない）

## 注意（コンプライアンス / YMYL）

- カードのスペックは「最終確認日」を必ず併記。`verify_status` を `verified` にするのは公式で確認した後。
- 「絶対安全 / 必ず儲かる / KYCなし」等の断定・誤認表現は使わない。
- アフィリンクは `rel="nofollow sponsored"`、PR表記を全ページに表示。
- 投資・税務の助言は行わない旨を明記。
