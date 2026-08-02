# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-08-02

### Added (新機能)
- **追加セクション**: 任意の Markdown を `llms.txt` / `llms-full.txt` に差し込めるようになりました。投稿一覧には現れないが AI に伝えたい情報 (MCP エンドポイント、API、利用条件、問い合わせ方針など) の記載に使えます
  - 挿入位置を「概要の直後」「コンテンツリストの後」から選択可能
  - 最大 5000 文字。保存時に HTML タグを除去 (出力は text/plain の Markdown のため)
  - 既定は OFF。本文が空のときも出力されません
- フィルターフック `kashiwazaki_seo_llmstxt_custom_section` を追加 (追加セクションの Markdown を挿入直前に加工可能)

## [1.1.0] - 2026-05-08

### Added (新機能)
- **管理画面の 5 タブ構成**: ダッシュボード / コンテンツ設定 / 運営者情報 / AI クローラー設定 / 高度な設定 にタブ分割し、設定項目の発見性を改善
- **ダッシュボードタブ**: llms.txt / llms-full.txt の生成状態バッジ、キャッシュ残時間、プレビュー URL、概要版/詳細版の差を一画面で確認可能
- **運営者情報 (E-E-A-T) ブロック**: `## About` セクションを生成ファイル冒頭に出力し、運営者・編集責任者・専門領域・編集方針を AI に伝達
- **概要版 1 行要約 (任意)**: llms.txt の各リンク行に投稿の抜粋から自動生成した 1 行要約を付与可能
- **HTTP Conditional GET (RFC 7232)**: ETag (weak) / Last-Modified / 304 Not Modified に対応、AI クローラーの帯域削減
- **YAML フッター詳細をグループ分割**: ライセンス / リトライ / 許可ボット の 3 つの details グループで段階的に展開
- **textarea プリセット挿入**: 主要 AI ボット 10 種・除外パス 6 種のプリセットボタンを追加
- **Sticky save bar**: ページ下部固定の保存ボタンと未保存変更警告
- **Help タブ (WP_Screen)**: 概要 / 用語集 / トラブルシュート / 出力サンプルを管理画面右上から提供
- **Danger Zone**: 「すべての設定をデフォルトに戻す」を専用タブに隔離、「理解した」チェックボックスで二段階確認

### Changed (変更)
- **タブ間の保存統合**: 全タブの設定を 1 つの form で管理、保存ボタン 1 回で全項目を反映 (POST 互換性 100% 維持)
- **投稿タイプ選択 UI**: 3 列 CSS Grid + チェック済み視覚フィードバック
- **AJAX 通知の一元化**: WP 標準 admin-notice + aria-live region に統合
- **キャッシュクリアボタン**: cache_duration=0 でも常時 enabled (旧 transient が残っている可能性に対応)

### Fixed (修正)
- **attachment 投稿タイプ**: WP_Query が `post_status='publish'` 固定だったため UI で attachment を選択しても出力されなかった不整合を修正 (`post_status='inherit'` も許可)
- **「すべての設定をデフォルトに戻す」**: 別 option の `kashiwazaki_llms_txt_enabled` / `_full` も初期値 true に戻すように修正
- **posts_per_page 上限**: POST save + runtime read 全 3 箇所で 1〜10000 で clamp し、巨大値による DoS を防止
- **Markdown 構造汚染**: site_name / site_desc に Markdown 構造文字 (`[]()` 改行 等) があった場合の正規化を追加 (post title と同じ処理)
- **PHP 8.1+ deprecation**: `trim($code)` で int を渡す deprecation を解消 (`trim((string) $code)`)
- **disabled endpoint の buffer 破壊**: 無効化された llms.txt / llms-full.txt の 404 path で他プラグインの output buffer を破壊する不具合を修正
- **plugin action link**: `admin_url()` 出力を `esc_url()` でエスケープ
- **Conditional GET の ETag/body 同期**: 配信 body と ETag/Content-Length 計算対象の bytes ミスマッチを修正 (RFC 7232 違反解消)
- **HEAD/GET Content-Length 整合**: HEAD と GET で Content-Length が一致するように修正
- **二重 BOM 解消**: 旧プラグインから移行した環境で発生していた二重 BOM を単一 BOM に修正
- **AJAX レスポンス Content-Type**: `wp_die(json_encode(...))` を `wp_send_json_success/error()` に置換し、正しい `application/json` を出力
- **filter_var(URL) IDN 拒否**: URL 検証を `wp_http_validate_url()` に置換し IDN ドメイン・multibyte path を許容
- **status-codes-no-retry 検証**: `is_numeric` を `preg_match('/^[1-5]\d{2}$/')` に変更 (`5e2`、`0xFF` 等の弾き)
- **アクティベーションフック**: `register_activation_hook` をクロージャ外に移動し、初回アクティベーションでデフォルト option 作成・rewrite flush が実行されないバグを修正

### Improved (改善)
- **アセットバージョニング**: `time()` から `filemtime()` に変更し、ファイル変更時のみキャッシュバスター値が変わるように
- **アクセシビリティ (a11y)**: tablist/tab/tabpanel ARIA 属性、`tabindex='0'` 付与、Arrow/Home/End キーナビ、`aria-describedby` の関連付け
- **狭幅レスポンシブ**: 782px 未満で form-table を 1 カラム化、ダッシュボードグリッドを縦並びに
- **plugin header 完備**: Text Domain / Domain Path / Requires at least: 6.0 / Requires PHP: 7.4 を追加
- **uninstall 完全化**: lastmod marker option と wildcard transient (kashiwazaki_llms_lastmod_v2_*) を削除対象に追加
- **alternate link 出力**: endpoint 個別 enabled チェック後にのみ `<link rel="alternate">` を出力

## [1.0.6] - 2025-11-09

### Fixed
- **他プラグインとの競合**: 他のWordPressプラグインとの優先度競合により `/llms.txt` が404エラーになる問題を修正

### Improved
- **フィルター/アクション優先度**: `query_vars` フィルターと `template_redirect` アクションの優先度を1に変更し、他プラグインより先に実行
- **REQUEST_URI解析**: `parse_url()` を使用した堅牢なパス判定を実装

## [1.0.5] - 2025-11-07

### Fixed
- **クエリバー登録問題の修正**: クエリバーが正しく登録されていない環境でもllms.txtが生成されるようにREQUEST_URIの直接チェック機能を追加
- **フォールバック処理の改善**: llms.txtおよびllms-full.txtへのアクセス時のフォールバック処理を強化

### Changed
- **リクエスト判定ロジック**: WordPressのクエリバーに依存せず、REQUEST_URIを直接チェックする方式に変更

## [1.0.4] - 2025-10-07

### Fixed
- **YAML形式のAI標準準拠**: wait-seconds、クォート付きステータスコード、allowed-botsの配列形式に対応
- **設定値の重複問題**: 設定値のマージロジックを書き直し、重複問題を解決
- **すべての設定をリセット**: デフォルトに戻すボタンがすべての設定値を正しくリセット
- **YAMLドキュメント区切り**: 著作権セクション前のドキュメント区切りを削除

### Changed
- **レート制限のデフォルト**: 無効化に変更（enabled: false）
- **レート制限のデフォルト値**: 5 req/sec、300 req/min に変更（従来は2/120）
- **許可ボットリスト**: 実在する9種類のボットのみに限定

### Added
- **レート制限切り替え**: チェックボックスと折りたたみ可能な詳細セクション
- **リトライポリシー切り替え**: 折りたたみ可能な詳細セクション
- **許可ボット設定フィールド**: アクセスを許可するAIボットの設定

### Improved
- **UI改善**: 表示/非表示セクションによる使いやすさの向上
- **設定処理の堅牢性**: 個別フィールド割り当てによる処理の改善

## [1.0.3] - 2025-07-09

### Added
- **キャッシュ機能**: 3時間～永久まで選択可能なキャッシュシステム
- **キャッシュクリアボタン**: 管理画面から手動でキャッシュをクリア可能
- **WordPress Transient API**: 効率的なキャッシュ実装
- **安全なテキスト出力関数**: HTMLエンティティ化を回避しつつセキュリティを確保

### Fixed
- **Plugin Check (PCP) 完全対応**: WordPress.org審査要件を完全にクリア
- **AI学習許可設定**: チェックボックス設定が正常に保存・反映されない問題を修正
- **HTMLエンティティ出力問題**: `&gt;`等が生成テキストに含まれる問題を解決
- **エスケープ処理**: テキストファイル出力で適切なエスケープ処理を実装
- **多次元配列サニタイゼーション**: YAML設定の安全な処理を改善
- **WordPress 6.8対応**: readme.txtのテスト済みバージョンを更新
- **バージョン整合性**: メインファイルとreadme.txtのバージョン統一
- **末尾スラッシュ問題**: llms.txtとllms-full.txtアクセス時のWordPress自動リダイレクト（末尾スラッシュ追加）を無効化

### Improved
- **パフォーマンス向上**: サーバー負荷を大幅に軽減
- **セキュリティ強化**: 全入出力の適切なサニタイゼーション・エスケープ処理
- **コードクオリティ**: WordPress標準関数の使用（`wp_strip_all_tags()`等）
- **エラー処理**: より堅牢な入力値検証とエラーハンドリング

### Technical
- **キャッシュシステム**: WordPress標準のTransient APIを使用した堅牢な実装
- **再帰的サニタイゼーション**: 多次元配列の安全な処理関数を実装
- **WordPress準拠**: `strip_tags()`から`wp_strip_all_tags()`への変更
- **PHPCS準拠**: 適切なコメントによるコーディング規約対応

## [1.0.2] - 2025-07-03

### Added
- **個別ファイル制御**: llms.txt と llms-full.txt の個別有効化/無効化機能
- **ファイルステータス表示**: 各ファイルの生成状況をリアルタイム表示
- **動的生成方式**: 物理ファイル保存からリアルタイム生成に変更

### Improved
- **常に最新情報**: アクセス時に最新のコンテンツ情報を提供
- **404エラー処理**: 無効化時の適切なエラーレスポンス
- **UI改善**: 生成/無効化ボタンのデザイン向上

### Changed
- **ファイル保存方式**: 物理ファイル保存を廃止し、完全動的生成に移行

## [1.0.1] - 2025-06-20

### Fixed
- **設定値処理**: 空の設定項目の正しい処理ロジックを実装
- **デフォルト値**: 適用問題の解決とバックアップ機能強化

### Improved
- **設定処理**: 設定値処理ロジックの最適化
- **エラー処理**: より堅牢なエラーハンドリング

## [1.0.0] - 2025-06-08

### Added
- **初回リリース**: WordPressプラグインとしての基本機能
- **llms.txt生成**: 概要版ファイルの動的生成機能
- **llms-full.txt生成**: 詳細版ファイルの動的生成機能
- **投稿タイプ選択**: 対象コンテンツの柔軟な選択機能
- **件数設定**: 各投稿タイプからの最大取得件数指定
- **AIクローラー設定**: YAML形式での詳細設定機能
- **制作者情報**: フッター表示のオン/オフ機能
- **UTF-8 BOM対応**: Excel等での文字化け防止
- **UTC時刻表記**: 国際標準時での日時記録
- **日本語完全対応**: すべての機能で日本語をサポート

### Technical Features
- **WordPress標準API**: WordPress推奨の実装方法を使用
- **SEO最適化**: HTMLヘッダーへのリンク自動追加
- **noindex対応**: 主要SEOプラグインとの互換性
- **セキュリティ**: 適切な入力値検証とサニタイゼーション

---

## Migration Notes

### 1.0.2 → 1.0.3
- 新しいキャッシュ機能により大幅なパフォーマンス向上
- 設定画面でキャッシュ期間を選択可能
- 既存設定は自動的に保持されます

### 1.0.1 → 1.0.2
- **重要**: 物理ファイル保存から動的生成に変更
- 既存の物理ファイル（もし存在する場合）は手動で削除してください
- パーマリンク設定の再保存を推奨

### Upgrade Process
1. プラグインファイルを更新
2. WordPress管理画面でプラグインを再有効化
3. パーマリンク設定を再保存（推奨）
4. 設定画面で新機能を確認・設定

## Compatibility

- **WordPress**: 5.0以降
- **PHP**: 7.4以降
- **ブラウザ**: Chrome, Firefox, Safari, Edge（最新2バージョン）
- **SEOプラグイン**: Yoast SEO, All in One SEO, RankMath対応

## Known Issues

現在のところ、重大な既知の問題はありません。

## Support

- **開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)
- **ウェブサイト**: https://www.tsuyoshikashiwazaki.jp/
- **バグ報告**: プラグイン設定画面またはウェブサイト経由でご報告ください
