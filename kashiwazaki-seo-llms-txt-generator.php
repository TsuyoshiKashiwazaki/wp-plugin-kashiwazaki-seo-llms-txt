<?php
/**
 * Plugin Name:       Kashiwazaki SEO LLMs.txt Generator
 * Plugin URI:        https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt
 * Description:       AIクローラー向けに llms.txt (概要版) と llms-full.txt (詳細版) を動的に生成。詳細なAIクローラー向け設定も可能です。
 * Version:           1.1.1
 * Author:            柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI:        https://www.tsuyoshikashiwazaki.jp/profile/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kashiwazaki-seo-llms-txt-generator
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

// セキュリティチェックのみ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// F8 (audit-r4): activation / deactivation / uninstall は plugins_loaded の外で登録
//   旧実装はクロージャ内に置かれていたため、初回 activation request で plugins_loaded
//   が未発火 → register_activation_hook 自体が登録されないままアクティベーションが終わり
//   デフォルト option 作成・rewrite flush が初回 activation で実行されないバグがあった
register_activation_hook( __FILE__, 'kashiwazaki_seo_llmstxt_activate' );
register_deactivation_hook( __FILE__, 'kashiwazaki_seo_llmstxt_deactivate' );
register_uninstall_hook( __FILE__, 'kashiwazaki_seo_llmstxt_uninstall_callback' );

function kashiwazaki_seo_llmstxt_activate() {
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY', 'kashiwazaki_seo_llmstxt_settings' );
    }
    // core-functions.php を読み込んで default options を作成
    $core_path = plugin_dir_path( __FILE__ ) . 'includes/core-functions.php';
    if ( file_exists( $core_path ) ) {
        require_once $core_path;
    }
    if ( false === get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY ) ) {
        if ( function_exists( 'kashiwazaki_seo_llmstxt_get_default_options' ) ) {
            update_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, kashiwazaki_seo_llmstxt_get_default_options() );
        }
    }
    if ( false === get_option( 'kashiwazaki_llms_txt_enabled' ) ) {
        update_option( 'kashiwazaki_llms_txt_enabled', true );
    }
    if ( false === get_option( 'kashiwazaki_llms_full_txt_enabled' ) ) {
        update_option( 'kashiwazaki_llms_full_txt_enabled', true );
    }
    if ( function_exists( 'kashiwazaki_seo_llmstxt_setup_rewrite_rules' ) ) {
        kashiwazaki_seo_llmstxt_setup_rewrite_rules();
    }
    flush_rewrite_rules();
}

function kashiwazaki_seo_llmstxt_deactivate() {
    flush_rewrite_rules();
}

// F4 (audit-r4): uninstall callback で Phase 1 で追加した transient/option marker も完全削除
function kashiwazaki_seo_llmstxt_uninstall_callback() {
    global $wpdb;
    delete_option( 'kashiwazaki_seo_llmstxt_settings' );
    delete_option( 'kashiwazaki_llms_txt_enabled' );
    delete_option( 'kashiwazaki_llms_full_txt_enabled' );
    delete_option( 'kashiwazaki_llms_lastmod_marker' );
    // transients (旧 v1 + 新 v2_<hash> + content cache)
    delete_transient( 'kashiwazaki_llms_lastmod_v1' );
    delete_transient( 'kashiwazaki_llms_txt_cache' );
    delete_transient( 'kashiwazaki_llms_full_txt_cache' );
    // hash 付き lastmod_v2_* を wildcard 削除
    if ( isset( $wpdb ) ) {
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like( '_transient_kashiwazaki_llms_lastmod_v2_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_kashiwazaki_llms_lastmod_v2_' ) . '%'
        ) );
    }
}

// すべての初期化を plugins_loaded まで遅延
add_action( 'plugins_loaded', function() {

    // 定数定義
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY', 'kashiwazaki_seo_llmstxt_settings' );
    }
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    }
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE', __FILE__ );
    }
    if ( ! defined( 'KASHIWAZAKI_SEO_LLMSTXT_VERSION' ) ) {
        define( 'KASHIWAZAKI_SEO_LLMSTXT_VERSION', '1.1.1' );
    }

    // コアファンクションを読み込む
    $filepath = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'includes/core-functions.php';
    if ( file_exists( $filepath ) ) {
        require_once $filepath;
    } else {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>AI LLMs Generator: 必須ファイル (includes/core-functions.php) が見つかりません。プラグインを再インストールしてください。</p></div>';
        });
        return;
    }

    // 管理画面ファイルを読み込む
    $admin_filepath = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'admin/admin-loader.php';
    if ( file_exists( $admin_filepath ) && is_admin() ) {
        require_once $admin_filepath;
    } elseif ( ! file_exists( $admin_filepath ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>AI LLMs Generator: 管理ファイル (admin/admin-loader.php) が見つかりません。</p></div>';
        });
    }

    // F6 (audit-r4): wp_head の alternate link は endpoint が enable のときだけ出力
    add_action( 'wp_head', function() {
        $llms_enabled      = (bool) get_option( 'kashiwazaki_llms_txt_enabled', true );
        $llms_full_enabled = (bool) get_option( 'kashiwazaki_llms_full_txt_enabled', true );

        $output = '';
        if ( $llms_enabled ) {
            $output .= sprintf(
                '<link rel="alternate" type="text/plain" title="%s" href="%s">',
                esc_attr( 'LLM Instructions' ),
                esc_url( home_url( '/llms.txt' ) )
            ) . "\n";
        }
        if ( $llms_full_enabled ) {
            $output .= sprintf(
                '<link rel="alternate" type="text/plain" title="%s" href="%s">',
                esc_attr( 'LLM Instructions (Full)' ),
                esc_url( home_url( '/llms-full.txt' ) )
            ) . "\n";
        }

        if ( $output !== '' ) {
            echo "\n" . wp_kses( $output, array(
                'link' => array(
                    'rel' => array(),
                    'type' => array(),
                    'title' => array(),
                    'href' => array()
                )
            ) );
        }
    }, 99 );

    // リライトルールとクエリバーをセットアップ
    if ( function_exists( 'kashiwazaki_seo_llmstxt_setup_rewrite_rules' ) ) {
        add_action( 'init', 'kashiwazaki_seo_llmstxt_setup_rewrite_rules' );
    }
    if ( function_exists( 'kashiwazaki_seo_llmstxt_add_query_vars' ) ) {
        add_filter( 'query_vars', 'kashiwazaki_seo_llmstxt_add_query_vars', 1 );
    }
    if ( function_exists( 'kashiwazaki_seo_llmstxt_handle_dynamic_output' ) ) {
        add_action( 'template_redirect', 'kashiwazaki_seo_llmstxt_handle_dynamic_output', 1 );
    }

}, 0 ); // 優先度0で最初に実行
