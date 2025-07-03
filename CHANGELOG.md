# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
