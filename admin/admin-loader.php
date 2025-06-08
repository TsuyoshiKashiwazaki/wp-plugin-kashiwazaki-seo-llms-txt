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
    add_menu_page(
        'Kashiwazaki SEO LLMs.txt Generator 設定',
        'Kashiwazaki SEO LLMs.txt Generator',
        'manage_options',
        'kashiwazaki-seo-llmstxt-generator',
        'kashiwazaki_seo_llmstxt_render_settings_page',
        'dashicons-media-text',
        81
    );
}
add_action( 'admin_menu', 'kashiwazaki_seo_llmstxt_admin_menu_init' );

function kashiwazaki_seo_llmstxt_admin_assets_init( $hook_suffix ) {
    if ( 'toplevel_page_kashiwazaki-seo-llmstxt-generator' !== $hook_suffix ) {
        return;
    }

    $plugin_version = defined('KASHIWAZAKI_SEO_LLMSTXT_VERSION') ? KASHIWAZAKI_SEO_LLMSTXT_VERSION : '1.0.0';
    $timestamp = time(); // キャッシュバスティング用のタイムスタンプ

    wp_enqueue_style(
        'kashiwazaki-seo-llmstxt-admin-styles',
        KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL . 'admin/assets/admin-styles.css',
        [],
        $plugin_version . '.' . $timestamp
    );

    wp_enqueue_script(
        'kashiwazaki-seo-llmstxt-admin-scripts',
        KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL . 'admin/assets/admin-scripts.js',
        ['jquery'],
        $plugin_version . '.' . $timestamp,
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
    $settings_link = '<a href="' . admin_url('admin.php?page=kashiwazaki-seo-llmstxt-generator') . '">設定</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// プラグインアクションリンクのフィルターを init フックで登録
function kashiwazaki_seo_llmstxt_register_plugin_links() {
    add_filter('plugin_action_links_' . plugin_basename(KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE), 'kashiwazaki_seo_llmstxt_plugin_action_links');
}
add_action('init', 'kashiwazaki_seo_llmstxt_register_plugin_links');

// AJAXハンドラー
function kashiwazaki_toggle_llms_file_ajax_handler() {
    // nonceチェック
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'セキュリティチェックに失敗しました。']
        ]));
    }

    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => '権限がありません。']
        ]));
    }

    $file_type = isset($_POST['file_type']) ? sanitize_text_field(wp_unslash($_POST['file_type'])) : '';
    $action = isset($_POST['toggle_action']) ? sanitize_text_field(wp_unslash($_POST['toggle_action'])) : '';

    // ファイルタイプの検証
    $valid_file_types = ['llms-txt', 'llms-full-txt'];
    if (!in_array($file_type, $valid_file_types)) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => '無効なファイルタイプです。']
        ]));
    }

    // アクションの検証
    if (!in_array($action, ['enable', 'disable'])) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => '無効なアクションです。']
        ]));
    }

    // オプション名を決定
    $option_name = $file_type === 'llms-txt' ? 'kashiwazaki_llms_txt_enabled' : 'kashiwazaki_llms_full_txt_enabled';
    
    // 設定を更新
    $enabled = $action === 'enable';
    update_option($option_name, $enabled);

    // 成功レスポンス
    $message = $enabled 
        ? sprintf('%s の生成を有効化しました。', str_replace('-', '.', $file_type))
        : sprintf('%s の生成を無効化しました。', str_replace('-', '.', $file_type));

    wp_die(json_encode([
        'success' => true,
        'data' => [
            'message' => $message,
            'enabled' => $enabled
        ]
    ]));
}
add_action('wp_ajax_kashiwazaki_toggle_llms_file', 'kashiwazaki_toggle_llms_file_ajax_handler');

// キャッシュクリアAJAXハンドラー
function kashiwazaki_clear_llms_cache_ajax_handler() {
    // nonceチェック
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'セキュリティチェックに失敗しました。']
        ]));
    }

    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => '権限がありません。']
        ]));
    }

    // キャッシュをクリア
    delete_transient('kashiwazaki_llms_txt_cache');
    delete_transient('kashiwazaki_llms_full_txt_cache');

    wp_die(json_encode([
        'success' => true,
        'data' => ['message' => 'キャッシュをクリアしました。']
    ]));
}
add_action('wp_ajax_kashiwazaki_clear_llms_cache', 'kashiwazaki_clear_llms_cache_ajax_handler');

// YAML設定をデフォルトにリセットするAJAXハンドラー
function kashiwazaki_reset_yaml_defaults_ajax_handler() {
    // nonceチェック
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kashiwazaki_llms_ajax_nonce')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'セキュリティチェックに失敗しました。']
        ]));
    }

    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => '権限がありません。']
        ]));
    }

    // 現在の設定を取得
    $current_options = get_option(KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, []);

    // デフォルト設定を取得
    if (function_exists('kashiwazaki_seo_llmstxt_get_default_options')) {
        $default_options = kashiwazaki_seo_llmstxt_get_default_options();

        // すべての設定をデフォルトに戻す
        update_option(KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, $default_options);

        // キャッシュをクリア
        delete_transient('kashiwazaki_llms_txt_cache');
        delete_transient('kashiwazaki_llms_full_txt_cache');

        wp_die(json_encode([
            'success' => true,
            'data' => [
                'message' => 'すべての設定をデフォルトに戻しました。ページを更新してください。',
            ]
        ]));
    } else {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'デフォルト設定の取得に失敗しました。']
        ]));
    }
}
add_action('wp_ajax_kashiwazaki_reset_yaml_defaults', 'kashiwazaki_reset_yaml_defaults_ajax_handler');
?>