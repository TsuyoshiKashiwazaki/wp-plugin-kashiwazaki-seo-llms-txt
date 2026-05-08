<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings_logic_file = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'admin/settings-page-logic.php';
if ( file_exists( $settings_logic_file ) ) {
    require_once $settings_logic_file;
} else {
    add_action( 'admin_notices', function() { echo '<div class="notice notice-error"><p>Kashiwazaki SEO LLMs.txt Generator: Critical file admin/settings-page-logic.php not found.</p></div>'; });
    return;
}

function kashiwazaki_seo_llmstxt_admin_menu_init() {
    $hook = add_menu_page(
        'Kashiwazaki SEO LLMs.txt Generator 設定',
        'Kashiwazaki SEO LLMs.txt Generator',
        'manage_options',
        'kashiwazaki-seo-llmstxt-generator',
        'kashiwazaki_seo_llmstxt_render_settings_page',
        'dashicons-media-text',
        81
    );

    if ( $hook ) {
        add_action( 'load-' . $hook, 'kashiwazaki_seo_llmstxt_register_help_tabs' );
    }
}
add_action( 'admin_menu', 'kashiwazaki_seo_llmstxt_admin_menu_init' );

function kashiwazaki_seo_llmstxt_register_help_tabs() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }

    $screen->add_help_tab( [
        'id'      => 'kashiwazaki_llms_help_overview',
        'title'   => '概要',
        'content' =>
            '<h3>llms.txt とは</h3>' .
            '<p>AI クローラー (ChatGPT / Claude / Perplexity 等) がサイトの構造を素早く把握するためのテキストファイル仕様です。</p>' .
            '<ul>' .
            '<li><strong>llms.txt (概要版)</strong>: タイトルとリンクのリスト。サイト全体のインデックス用。</li>' .
            '<li><strong>llms-full.txt (詳細版)</strong>: 上記 + 抜粋・公開日・更新日。AI が引用根拠とする情報量を持たせたい場合に。</li>' .
            '</ul>' .
            '<p>WordPress のルートには物理ファイルを作成せず、リクエスト時に動的に生成・出力します。</p>',
    ] );

    $screen->add_help_tab( [
        'id'      => 'kashiwazaki_llms_help_terms',
        'title'   => '用語集',
        'content' =>
            '<h3>主な用語</h3>' .
            '<dl>' .
            '<dt><strong>E-E-A-T</strong></dt><dd>Experience / Expertise / Authoritativeness / Trustworthiness の略。Google が示す高品質コンテンツの 4 要素。AI が「誰の知識として引用するか」を判断する根拠になります。</dd>' .
            '<dt><strong>YAML フッター</strong></dt><dd>llms.txt 末尾に出力する YAML 形式の AI クローラー指示。許可ボット・アクセス頻度・除外パスなどを宣言します。</dd>' .
            '<dt><strong>正規ドメイン (canonical-url)</strong></dt><dd>AI が引用する際に使う公式 URL。CDN や代替ドメインがある場合に指定。</dd>' .
            '<dt><strong>キャッシュ</strong></dt><dd>生成済み内容を再利用する仕組み。サーバー負荷を下げる代わりに、コンテンツ更新が反映されるまでに遅延が発生します。</dd>' .
            '</dl>',
    ] );

    $screen->add_help_tab( [
        'id'      => 'kashiwazaki_llms_help_troubleshoot',
        'title'   => 'トラブルシュート',
        'content' =>
            '<h3>よくある問題</h3>' .
            '<dl>' .
            '<dt><strong>llms.txt が 404 になる</strong></dt><dd>「ダッシュボード」タブで「生成中」になっているか確認。固定リンク設定 (パーマリンク) を一度保存し直すと rewrite rule が再構築されて解消することがあります。</dd>' .
            '<dt><strong>更新内容が反映されない</strong></dt><dd>キャッシュ TTL が長い可能性。「ダッシュボード」または「高度な設定」タブで「キャッシュをクリア」してください。</dd>' .
            '<dt><strong>許可ボットが効かない</strong></dt><dd>「AI クローラー設定」タブで YAML フッターが ON になっているか、対象ボット名が正確か (大文字小文字含む) を確認。</dd>' .
            '<dt><strong>E-mail / 電話番号が公開された</strong></dt><dd>llms.txt はテキストとして全公開されるため、公開を望まない場合は「運営者情報」タブの該当項目を空にして保存してください。</dd>' .
            '</dl>',
    ] );

    $screen->add_help_tab( [
        'id'      => 'kashiwazaki_llms_help_sample',
        'title'   => '出力サンプル',
        'content' =>
            '<h3>llms.txt の典型的な出力例</h3>' .
            '<pre style="background:#f0f0f1;padding:10px;border-radius:3px;font-size:12px;line-height:1.5;overflow:auto;">' .
            '# サイト名' . "\n\n" .
            '> サイトの説明 (任意)' . "\n\n" .
            '## About' . "\n" .
            '- 運営者: 株式会社 例' . "\n" .
            '- 編集責任者: 山田 太郎' . "\n\n" .
            '## Posts' . "\n" .
            '- [記事タイトル](https://example.com/post-1/)' . "\n" .
            '- [別の記事](https://example.com/post-2/): 1 行要約 (任意)' . "\n\n" .
            '## Pages' . "\n" .
            '- [固定ページ](https://example.com/about/)' . "\n\n" .
            '---' . "\n" .
            '# YAML フッター (任意)' . "\n" .
            'license:' . "\n" .
            '  allow-ai-training: true' . "\n" .
            '  allow-ai-commercial-use: false' . "\n" .
            'allowed-bots: [GPTBot, ClaudeBot]' . "\n" .
            '</pre>',
    ] );

    $screen->set_help_sidebar(
        '<p><strong>関連情報</strong></p>' .
        '<p><a href="https://llmstxt.org/" target="_blank" rel="noopener">llms.txt 仕様 (公式)</a></p>' .
        '<p><a href="https://developers.google.com/search/docs/fundamentals/creating-helpful-content" target="_blank" rel="noopener">E-E-A-T (Google ガイド)</a></p>'
    );
}

function kashiwazaki_seo_llmstxt_admin_assets_init( $hook_suffix ) {
    if ( 'toplevel_page_kashiwazaki-seo-llmstxt-generator' !== $hook_suffix ) {
        return;
    }

    // F3 (audit-r4): time() を毎回付加するとブラウザキャッシュが効かない
    //   plugin version + filemtime を使い、ファイル変更時のみ cache buster が変わる方式に修正
    $plugin_version = defined('KASHIWAZAKI_SEO_LLMSTXT_VERSION') ? KASHIWAZAKI_SEO_LLMSTXT_VERSION : '1.0.0';
    $css_path = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'admin/assets/admin-styles.css';
    $js_path  = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'admin/assets/admin-scripts.js';
    $css_ver  = file_exists( $css_path ) ? $plugin_version . '.' . filemtime( $css_path ) : $plugin_version;
    $js_ver   = file_exists( $js_path )  ? $plugin_version . '.' . filemtime( $js_path )  : $plugin_version;

    wp_enqueue_style(
        'kashiwazaki-seo-llmstxt-admin-styles',
        KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL . 'admin/assets/admin-styles.css',
        [],
        $css_ver
    );

    wp_enqueue_script(
        'kashiwazaki-seo-llmstxt-admin-scripts',
        KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL . 'admin/assets/admin-scripts.js',
        ['jquery'],
        $js_ver,
        true
    );

    // JavaScriptローカライズ
    wp_localize_script(
        'kashiwazaki-seo-llmstxt-admin-scripts',
        'kashiwazakiLlmsAdmin',
        [
            'nonce' => wp_create_nonce('kashiwazaki_llms_ajax_nonce'),
            'confirmDelete' => 'このファイルの生成を無効化してもよろしいですか？',
            'statusEnabled' => '生成中',
            'statusDisabled' => '無効',
            'ajaxurl' => admin_url('admin-ajax.php')
        ]
    );
}
add_action( 'admin_enqueue_scripts', 'kashiwazaki_seo_llmstxt_admin_assets_init' );

// プラグイン一覧ページに設定リンクを追加
function kashiwazaki_seo_llmstxt_plugin_action_links( $links ) {
    // F12 (full-audit-r2): admin_url() の出力は HTML 属性文脈なので esc_url() で escape する
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=kashiwazaki-seo-llmstxt-generator' ) ) . '">' . esc_html__( '設定', 'kashiwazaki-seo-llms-txt-generator' ) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// プラグインアクションリンクのフィルターを init フックで登録
function kashiwazaki_seo_llmstxt_register_plugin_links() {
    add_filter('plugin_action_links_' . plugin_basename(KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE), 'kashiwazaki_seo_llmstxt_plugin_action_links');
}
add_action('init', 'kashiwazaki_seo_llmstxt_register_plugin_links');

// F2 (audit-r4): AJAX レスポンスを wp_send_json_success / wp_send_json_error に統一
//   旧実装の wp_die(json_encode(...)) は Content-Type: text/html で JSON を返していた

// AJAXハンドラー
function kashiwazaki_toggle_llms_file_ajax_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました。']);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => '権限がありません。']);
    }

    $file_type = isset($_POST['file_type']) ? sanitize_text_field(wp_unslash($_POST['file_type'])) : '';
    $action = isset($_POST['toggle_action']) ? sanitize_text_field(wp_unslash($_POST['toggle_action'])) : '';

    $valid_file_types = ['llms-txt', 'llms-full-txt'];
    if (!in_array($file_type, $valid_file_types, true)) {
        wp_send_json_error(['message' => '無効なファイルタイプです。']);
    }
    if (!in_array($action, ['enable', 'disable'], true)) {
        wp_send_json_error(['message' => '無効なアクションです。']);
    }

    $option_name = $file_type === 'llms-txt' ? 'kashiwazaki_llms_txt_enabled' : 'kashiwazaki_llms_full_txt_enabled';
    $enabled = $action === 'enable';
    update_option($option_name, $enabled);

    $message = $enabled
        ? sprintf('%s の生成を有効化しました。', str_replace('-', '.', $file_type))
        : sprintf('%s の生成を無効化しました。', str_replace('-', '.', $file_type));

    wp_send_json_success([
        'message' => $message,
        'enabled' => $enabled,
    ]);
}
add_action('wp_ajax_kashiwazaki_toggle_llms_file', 'kashiwazaki_toggle_llms_file_ajax_handler');

// キャッシュクリアAJAXハンドラー
function kashiwazaki_clear_llms_cache_ajax_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました。']);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => '権限がありません。']);
    }

    delete_transient('kashiwazaki_llms_txt_cache');
    delete_transient('kashiwazaki_llms_full_txt_cache');

    wp_send_json_success(['message' => 'キャッシュをクリアしました。']);
}
add_action('wp_ajax_kashiwazaki_clear_llms_cache', 'kashiwazaki_clear_llms_cache_ajax_handler');

// YAML設定をデフォルトにリセットするAJAXハンドラー
function kashiwazaki_reset_yaml_defaults_ajax_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました。']);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => '権限がありません。']);
    }

    if (!function_exists('kashiwazaki_seo_llmstxt_get_default_options')) {
        wp_send_json_error(['message' => 'デフォルト設定の取得に失敗しました。']);
    }

    $default_options = kashiwazaki_seo_llmstxt_get_default_options();
    update_option(KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, $default_options);

    // F5 (full-audit-r2): ファイル生成 ON/OFF も別 option に保存されているため、
    // 「すべての設定」のリセット時に同時に初期値 (true = 有効) に戻す
    update_option('kashiwazaki_llms_txt_enabled', true);
    update_option('kashiwazaki_llms_full_txt_enabled', true);

    delete_transient('kashiwazaki_llms_txt_cache');
    delete_transient('kashiwazaki_llms_full_txt_cache');

    wp_send_json_success(['message' => 'すべての設定をデフォルトに戻しました。ページを更新してください。']);
}
add_action('wp_ajax_kashiwazaki_reset_yaml_defaults', 'kashiwazaki_reset_yaml_defaults_ajax_handler');
?>