# Kashiwazaki SEO LLMs.txt Generator

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.1-orange.svg)](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt/releases)

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
