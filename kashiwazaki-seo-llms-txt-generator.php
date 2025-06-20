<?php
/**
 * Plugin Name:  Kashiwazaki SEO LLMs.txt Generator
 * Plugin URI:   https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-llms-txt
 * Description:  AIクローラー向けに llms.txt (概要版) と llms-full.txt (詳細版) を動的に生成。詳細なAIクローラー向け設定も可能です。
 * Version:      1.0.1
 * Author:       柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI:   https://www.tsuyoshikashiwazaki.jp/profile/
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

// セキュリティチェックのみ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
        define( 'KASHIWAZAKI_SEO_LLMSTXT_VERSION', '1.0.1' );
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
    if ( file_exists( $admin_filepath ) ) {
        require_once $admin_filepath;
    } else {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>AI LLMs Generator: 管理ファイル (admin/admin-loader.php) が見つかりません。</p></div>';
        });
    }

    // アクティベーション関数
    register_activation_hook( KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE, function() {
        if ( false === get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY ) ) {
            if ( function_exists( 'kashiwazaki_seo_llmstxt_get_default_options' ) ) {
                $default_options = kashiwazaki_seo_llmstxt_get_default_options();
                update_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, $default_options );
            }
        }
        
        // ファイル生成フラグのデフォルト設定
        if ( false === get_option( 'kashiwazaki_llms_txt_enabled' ) ) {
            update_option( 'kashiwazaki_llms_txt_enabled', true );
        }
        if ( false === get_option( 'kashiwazaki_llms_full_txt_enabled' ) ) {
            update_option( 'kashiwazaki_llms_full_txt_enabled', true );
        }
        
        if ( function_exists('kashiwazaki_seo_llmstxt_setup_rewrite_rules') ) {
            kashiwazaki_seo_llmstxt_setup_rewrite_rules();
        }
        flush_rewrite_rules();
    });

    // ディアクティベーション関数
    register_deactivation_hook( KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE, function() {
        flush_rewrite_rules();
    });

    // アンインストール関数  
    register_uninstall_hook( KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_FILE, 'kashiwazaki_seo_llmstxt_uninstall_callback' );

    // ヘッダーリンクを追加
    add_action( 'wp_head', function() {
        $output = '';
        $output .= sprintf(
            '<link rel="alternate" type="text/plain" title="%s" href="%s">',
            esc_attr( 'LLM Instructions' ),
            esc_url( home_url( '/llms.txt' ) )
        ) . "\n";
        $output .= sprintf(
            '<link rel="alternate" type="text/plain" title="%s" href="%s">',
            esc_attr( 'LLM Instructions (Full)' ),
            esc_url( home_url( '/llms-full.txt' ) )
        ) . "\n";

        if ( ! empty( $output ) ) {
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
        add_filter( 'query_vars', 'kashiwazaki_seo_llmstxt_add_query_vars' );
    }
    if ( function_exists( 'kashiwazaki_seo_llmstxt_handle_dynamic_output' ) ) {
        add_action( 'template_redirect', 'kashiwazaki_seo_llmstxt_handle_dynamic_output' );
    }
    
}, 0 ); // 優先度0で最初に実行

// アンインストールコールバック（plugins_loadedの外に配置）
function kashiwazaki_seo_llmstxt_uninstall_callback() {
    delete_option( 'kashiwazaki_seo_llmstxt_settings' );
    delete_option( 'kashiwazaki_llms_txt_enabled' );
    delete_option( 'kashiwazaki_llms_full_txt_enabled' );
}