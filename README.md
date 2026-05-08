# Kashiwazaki SEO LLMs.txt Generator

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.1.0-orange.svg)](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt/releases)

AIクローラー向けに llms.txt（概要版）と llms-full.txt（詳細版）を動的に生成するWordPressプラグインです。大規模言語モデル（LLM）に対してサイトコンテンツの構造化された情報を提供し、AI学習やクロール設定を細かく制御できます。

> AIクローラーとの適切な対話を実現し、サイト運営者の意図を明確に伝達

## 主な機能

### 動的ファイル生成
- **llms.txt（概要版）**: 投稿タイトルとURLのリストをMarkdown形式で生成
- **llms-full.txt（詳細版）**: タイトル、URL、公開日時（UTC）、最終更新日時（UTC）、抜粋を含む詳細情報
- **リアルタイム生成**: 物理ファイルは作成せず、アクセス時に最新情報を動的生成

### 柔軟な設定オプション
- **投稿タイプ選択**: 投稿、固定ページ、カスタム投稿タイプ、メディアから選択可能
- **最大取得件数**: 各投稿タイプから取得する最新記事数を指定（デフォルト1000件）

### AIクローラー向け詳細設定（YAMLフッター）
- **コンテンツライセンス**: AI学習利用・商用利用の許可設定
- **アクセス頻度制限**: 秒間・分間リクエスト数制限
- **正規ドメイン指定**: AIが引用する際の正しいサイトURL
- **リトライポリシー**: エラー時のリトライ設定
- **除外パス指定**: クロール対象外とするパス
- **サイトマップURL**: XMLサイトマップの場所を指定

### 技術的特徴
- **UTF-8 BOM付きファイル**: Excel等での文字化けを防止
- **UTC時刻表記**: 国際標準時での日時記録
- **SEO最適化**: HTMLヘッダーにファイルへのリンクを自動追加
- **セキュリティ対応**: 適切なエスケープ処理とサニタイゼーション

## クイックスタート

### インストール

1. **プラグインファイルの配置**
   ```bash
   cd /wp-content/plugins/
   # ファイルをアップロードまたは展開
   ```

2. **プラグイン有効化**
   - WordPress管理画面 → プラグイン → 「Kashiwazaki SEO LLMs.txt Generator」を有効化

3. **基本設定**
   - 管理メニューの「Kashiwazaki SEO LLMs.txt Generator」をクリック
   - 対象投稿タイプを選択（デフォルト: 投稿、固定ページ）
   - 設定を保存

4. **動作確認**
   ```
   https://example.com/llms.txt
   https://example.com/llms-full.txt
   ```

## 使い方

### 基本操作

1. **設定画面アクセス**
   - WordPress管理画面 → Kashiwazaki SEO LLMs.txt Generator
   - またはプラグイン一覧の「設定」リンク

2. **投稿タイプ選択**
   - 含めたい投稿タイプにチェック
   - 最大取得件数を指定（1-10000）

3. **AIクローラー設定**
   - ライセンス条件の設定
   - アクセス制限の設定
   - 除外パスの指定

### 出力例

**llms.txt（概要版）**
```markdown
# サイト名のコンテンツ一覧

## 投稿 (`post`)
- [記事タイトル1](https://example.com/post1/)
- [記事タイトル2](https://example.com/post2/)

---
# AI Crawler Configuration
license:
  allow-ai-training: true
  allow-ai-commercial-use: true
rate-limit:
  requests-per-second: 2
  requests-per-minute: 120
```

## 技術仕様

### システム要件
- **WordPress**: 5.0以上
- **PHP**: 7.4以上
- **メモリ**: 64MB以上推奨

### 対応環境
- **マルチサイト**: 対応
- **SSL**: 完全対応
- **キャッシュプラグイン**: 互換性あり

### API仕様
- **Content-Type**: text/plain; charset=utf-8
- **エンコーディング**: UTF-8 BOM付き
- **日時形式**: ISO 8601 UTC（例: 2025-06-08T16:59:00Z）

### セキュリティ
- **データサニタイゼーション**: 全入力値を適切にサニタイズ
- **アクセス制御**: 管理者権限必須
- **Nonce検証**: AJAX通信にCSRF保護
- **エスケープ処理**: 全出力値を適切にエスケープ

## 更新履歴

### [1.1.0] - 2026-05-08
- **新機能**: 管理画面を 5 タブ構成に再編（ダッシュボード / コンテンツ設定 / 運営者情報 / AI クローラー設定 / 高度な設定）
- **新機能**: ダッシュボードに生成状態バッジ・キャッシュ残時間・プレビュー URL を表示
- **新機能**: 運営者情報（E-E-A-T）ブロックを生成ファイル冒頭に出力（`## About` として運営者・編集責任者・専門領域・編集方針を AI に伝達）
- **新機能**: 概要版 1 行要約（任意）— llms.txt の各リンク行に投稿の抜粋を付与
- **新機能**: HTTP Conditional GET（RFC 7232：ETag / Last-Modified / 304）対応
- **新機能**: YAML フッター詳細を 3 つの details グループに分割、textarea プリセットボタン（AI ボット 10 種・除外パス 6 種）
- **新機能**: Sticky save bar、未保存変更警告、Help タブ、Danger Zone（理解確認 checkbox + 二段階確認）
- **修正**: attachment 投稿タイプの `post_status='inherit'` 対応（メディアを選択しても出力されなかった不整合を解消）
- **修正**: posts_per_page を 1〜10000 で clamp（POST save + runtime read 全 3 箇所、巨大値による DoS 防止）
- **修正**: site_name / site_desc に Markdown 構造文字正規化を追加
- **修正**: disabled endpoint で他プラグインの output buffer を破壊する不具合
- **修正**: Conditional GET の ETag/body 同期、HEAD/GET Content-Length 一致、二重 BOM 解消
- **修正**: AJAX レスポンス Content-Type を `application/json` に統一、URL 検証を `wp_http_validate_url()` に置換
- **修正**: 「すべての設定をデフォルトに戻す」が `kashiwazaki_llms_txt_enabled` / `_full` も初期化、plugin action link を `esc_url()` でエスケープ
- **改善**: アクセシビリティ（ARIA tablist/tab/tabpanel、tabindex、Arrow キーナビ）、狭幅 782px 対応
- **改善**: register_activation_hook の closure 外配置、アセットバージョニング（filemtime）、uninstall 完全化

### [1.0.6] - 2025-11-09
- **修正**: 他のWordPressプラグインとの優先度競合により `/llms.txt` が404エラーになる問題を修正
- **改善**: `query_vars` フィルターと `template_redirect` アクションの優先度を1に変更（他プラグインより先に実行）
- **改善**: REQUEST_URIの解析に `parse_url()` を使用し、より堅牢なパス判定を実装

### [1.0.5] - 2025-11-07
- **修正**: クエリバー登録問題の修正（クエリバーが正しく登録されていない環境でもllms.txtが生成されるようにREQUEST_URIの直接チェック機能を追加）
- **改善**: フォールバック処理の強化（llms.txtおよびllms-full.txtへのアクセス時のフォールバック処理を改善）
- **変更**: リクエスト判定ロジックの変更（WordPressのクエリバーに依存せず、REQUEST_URIを直接チェックする方式に変更）

### [1.0.4] - 2025-10-07
- **修正**: YAML形式をAIクローラー標準に準拠（wait-seconds、クォート付きステータスコード、allowed-botsの配列形式）
- **修正**: 設定値の重複問題を解決
- **修正**: すべての設定をデフォルトに戻すボタンを修正
- **変更**: レート制限のデフォルトを無効化、許可ボットリストを実在する9種類に限定
- **追加**: レート制限とリトライポリシーの切り替えUI、許可ボット設定フィールド
- **改善**: UI改善と設定処理の堅牢性向上

### [1.0.3] - 2025-07-09
- **Added**: キャッシュ機能を追加（3時間～永久まで選択可能）
- **Added**: キャッシュクリアボタンを追加
- **Improved**: サーバー負荷を大幅に軽減
- **Fixed**: WordPress 6.8対応
- **Fixed**: Plugin Check (PCP) 準拠のセキュリティ修正

### [1.0.2] - 2025-07-03
- 個別ファイル制御機能、動的生成方式への変更

### [1.0.1] - 2025-06-20
- WordPress Plugin Checkの警告修正、設定値処理改善

### [1.0.0] - 2025-06-08
- 初回リリース、llms.txt/llms-full.txt生成機能、投稿タイプ選択、AIクローラー設定

## ライセンス

GPL-2.0-or-later

## サポート・開発者

**開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)
**ウェブサイト**: https://www.tsuyoshikashiwazaki.jp/
**サポート**: プラグインに関するご質問や不具合報告は、開発者ウェブサイトまでお問い合わせください。

## 貢献

プロジェクトへの貢献を歓迎します：

1. **Issue報告**: バグや機能要望をGitHub Issuesで報告
2. **プルリクエスト**: 改善提案やバグ修正
3. **ドキュメント改善**: README、コメントの充実
4. **テスト**: 様々な環境での動作テスト

### 開発環境セットアップ
```bash
git clone https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt.git
cd wp-plugin-kashiwazaki-seo-llms-txt
# WordPressテスト環境に配置
```

## サポート

- **ドキュメント**: このREADMEと設定画面のヘルプテキスト
- **FAQ**: WordPress.org プラグインページのFAQ
- **個別サポート**: 開発者ウェブサイトのお問い合わせフォーム
- **更新情報**: GitHubのReleases、開発者ブログで最新情報を配信

---

**Keywords**: WordPress, SEO, AI, LLM, crawler, llms.txt, artificial intelligence, machine learning, content optimization, dynamic generation
Made with love by [Tsuyoshi Kashiwazaki](https://github.com/TsuyoshiKashiwazaki)
