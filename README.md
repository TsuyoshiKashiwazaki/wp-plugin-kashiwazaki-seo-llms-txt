# 🚀 Kashiwazaki SEO LLMs.txt Generator

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.3-orange.svg)](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt/releases)

WordPressプラグイン「**Kashiwazaki SEO LLMs.txt Generator**」は、AIクローラーや大規模言語モデル（LLM）向けに `llms.txt`（概要版）と `llms-full.txt`（詳細版）を動的に生成し、サイトのコンテンツ情報を効率的に提供するプラグインです。

> 🎯 **AIの時代に対応した、次世代のコンテンツクローリング最適化プラグイン**

## 主な機能

### 📄 動的ファイル生成
- **llms.txt（概要版）**: 選択した投稿タイプのタイトルとURLのリストをMarkdown形式で動的に生成
- **llms-full.txt（詳細版）**: タイトル、URL、公開日時（UTC）、最終更新日時（UTC）、抜粋を含む詳細情報を生成
- **リアルタイム更新**: ファイルは物理的に保存されず、アクセス時に常に最新情報を提供

### ⚙️ 柔軟な設定オプション
- **対象投稿タイプ選択**: 投稿、固定ページ、カスタム投稿タイプ、メディアから選択可能
- **最大取得件数**: 各投稿タイプから取得する最新記事数を指定
- **個別有効化/無効化**: 各ファイルの生成を個別に制御
- **キャッシュ機能**: 3時間～永久まで選択可能、サーバー負荷を軽減

### 🤖 AIクローラー向け詳細設定（YAMLフッター）
- **コンテンツライセンス**: AI学習利用・商用利用の許可設定
- **アクセス頻度制限**: 秒間・分間リクエスト数制限
- **正規ドメイン指定**: AIが引用する際の正しいサイトURL
- **リトライポリシー**: エラー時のリトライ設定
- **除外パス指定**: クロール対象外とするパス
- **サイトマップURL**: XMLサイトマップの場所を指定

### 🔧 技術的特徴
- **UTF-8 BOM付きファイル**: Excel等での文字化けを防止
- **UTC時刻表記**: 国際標準時での日時記録
- **SEO最適化**: HTMLヘッダーにファイルへのリンクを自動追加
- **noindex対応**: Yoast SEO、All in One SEO、RankMath等に対応

## 🚀 クイックスタート

### システム要件
- WordPress 5.0以降
- PHP 7.4以降

### インストール

1. **ダウンロード**
   ```bash
   git clone https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt.git
   ```

2. **アップロード**  
   プラグインファイルを `/wp-content/plugins/kashiwazaki-seo-llms-txt-generator/` にアップロード

3. **有効化**  
   WordPress管理画面の「プラグイン」メニューからプラグインを有効化

4. **設定**  
   管理メニューの「Kashiwazaki SEO LLMs.txt Generator」から基本設定を行う

### 動作確認
設定完了後、以下のURLにアクセスして動作を確認：
- `https://example.com/llms.txt` - 概要版
- `https://example.com/llms-full.txt` - 詳細版

## 使い方

### 基本設定
1. **コンテンツ選択**: 対象とする投稿タイプを選択
2. **件数設定**: 各投稿タイプから取得する最大件数を指定
3. **ファイル有効化**: 生成したいファイル（llms.txt/llms-full.txt）を有効化

### AIクローラー設定
1. **ライセンス設定**: AI学習・商用利用の許可設定
2. **アクセス制限**: リクエスト頻度の制限設定
3. **除外パス**: クロール対象外とするパスを指定

### キャッシュ設定
1. **キャッシュ期間**: 3時間から永久まで選択可能
2. **手動クリア**: 必要に応じてキャッシュを手動でクリア

## ファイル形式

### llms.txt（概要版）
```markdown
# サイト名のコンテンツ一覧

## 投稿
- [記事タイトル1](https://example.com/post1/)
- [記事タイトル2](https://example.com/post2/)

## 固定ページ  
- [ページタイトル1](https://example.com/page1/)
```

### llms-full.txt（詳細版）
```markdown
# サイト名の詳細コンテンツ情報

## 投稿
- **タイトル**: 記事タイトル1
  **URL**: https://example.com/post1/
  **公開日**: 2024-01-01T12:00:00Z
  **更新日**: 2024-01-15T15:30:00Z
  **抜粋**: 記事の概要文...
```

## よくある質問

### Q: ファイルはどこに保存されますか？
A: 物理的には保存されません。動的生成方式のため、アクセス時にリアルタイムで最新情報を生成します。

### Q: どの投稿タイプを含めることができますか？
A: 「公開」属性を持つ投稿タイプ（投稿、固定ページ、カスタム投稿タイプ）および「メディア」ライブラリのアイテムを選択できます。

### Q: ファイルにアクセスできません
A: 以下を確認してください：
1. プラグインが有効化されている
2. 管理画面でファイル生成が有効化されている
3. 少なくとも1つの投稿タイプが選択されている
4. パーマリンク設定を再保存してリライトルールを更新

## 技術仕様

- **WordPress**: 5.0以降
- **PHP**: 7.4以降
- **文字コード**: UTF-8 BOM付き
- **時刻形式**: UTC（ISO 8601形式）
- **出力形式**: Markdown + YAML

## 更新履歴

### Version 1.0.3
- **NEW**: キャッシュ機能を追加（3時間～永久まで選択可能）
- **NEW**: キャッシュクリアボタンを追加
- **IMPROVE**: サーバー負荷を大幅に軽減
- **FIX**: WordPress 6.7.0 の翻訳読み込みタイミング要件に対応

### Version 1.0.2
- **NEW**: 各ファイルの個別有効化/無効化機能
- **NEW**: ファイルステータス表示機能
- **IMPROVE**: 動的生成方式に変更、常に最新情報を提供

### Version 1.0.1
- **IMPROVE**: 空の設定項目の正しい処理
- **FIX**: デフォルト値適用問題の解決

## ライセンス

GPL-2.0-or-later

このプラグインは GNU General Public License v2 またはそれ以降のバージョンの下で配布されています。

## サポート・開発者

**開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)  
**ウェブサイト**: https://www.tsuyoshikashiwazaki.jp/  
**サポート**: プラグインに関するご質問や不具合報告は、開発者ウェブサイトまでお問い合わせください。

## 🤝 貢献

プロジェクトへの貢献を歓迎します！

### 貢献方法
1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

### 報告・提案
- 🐛 **バグ報告**: [Issues](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt/issues)
- ⭐ **スター**: このプロジェクトが役立ったらスターをお願いします！

## 📞 サポート

- **開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)
- **ウェブサイト**: [tsuyoshikashiwazaki.jp](https://www.tsuyoshikashiwazaki.jp/)
- **メール**: t.kashiwazaki@contencial.co.jp
- **GitHub**: [@TsuyoshiKashiwazaki](https://github.com/TsuyoshiKashiwazaki)

## ⚖️ ライセンス

このプロジェクトは [GPL-2.0-or-later License](LICENSE) の下で公開されています。

---

<div align="center">

**🔍 Keywords**: WordPress, SEO, AI, LLM, Crawler, llms.txt, Machine Learning, Content Discovery

Made with ❤️ by [Tsuyoshi Kashiwazaki](https://github.com/TsuyoshiKashiwazaki)

</div>