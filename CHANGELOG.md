# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
