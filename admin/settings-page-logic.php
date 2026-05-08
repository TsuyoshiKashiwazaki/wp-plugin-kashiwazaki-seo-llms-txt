<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kashiwazaki_seo_llmstxt_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'このページにアクセスするための十分な権限がありません。' );
    }

    $message = '';
    $default_options = function_exists('kashiwazaki_seo_llmstxt_get_default_options') ? kashiwazaki_seo_llmstxt_get_default_options() : [];

    $saved_options = get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, [] );

    // トップレベルのマージ（yaml_settingsは個別処理）
    $options = [];
    $options['posts_per_page'] = isset($saved_options['posts_per_page']) ? max(1, intval($saved_options['posts_per_page'])) : $default_options['posts_per_page'];
    $options['selected_types'] = isset($saved_options['selected_types']) && is_array($saved_options['selected_types']) ? array_map('sanitize_key', $saved_options['selected_types']) : $default_options['selected_types'];
    $options['enable_yaml_header'] = isset($saved_options['enable_yaml_header']) ? (bool)$saved_options['enable_yaml_header'] : $default_options['enable_yaml_header'];
    $options['show_copyright_footer'] = isset($saved_options['show_copyright_footer']) ? (bool)$saved_options['show_copyright_footer'] : $default_options['show_copyright_footer'];
    $options['cache_duration'] = isset($saved_options['cache_duration']) ? (string)$saved_options['cache_duration'] : '0';
    // A-2: 概要版オプション
    $options['summary_inline_excerpt'] = isset($saved_options['summary_inline_excerpt']) ? (bool)$saved_options['summary_inline_excerpt'] : $default_options['summary_inline_excerpt'];
    $options['summary_excerpt_length'] = isset($saved_options['summary_excerpt_length']) ? max(20, min(200, intval($saved_options['summary_excerpt_length']))) : $default_options['summary_excerpt_length'];
    // A-1: E-E-A-T オプション
    $options['enable_eeat_block'] = isset($saved_options['enable_eeat_block']) ? (bool)$saved_options['enable_eeat_block'] : $default_options['enable_eeat_block'];
    $options['eeat_settings'] = isset($saved_options['eeat_settings']) && is_array($saved_options['eeat_settings'])
        ? wp_parse_args($saved_options['eeat_settings'], $default_options['eeat_settings'])
        : $default_options['eeat_settings'];

    // yaml_settingsの処理（重複を避けるため個別にマージ）
    if (isset($saved_options['yaml_settings'])) {
        $options['yaml_settings'] = [];

        // License
        $options['yaml_settings']['license'] = isset($saved_options['yaml_settings']['license'])
            ? wp_parse_args($saved_options['yaml_settings']['license'], $default_options['yaml_settings']['license'])
            : $default_options['yaml_settings']['license'];

        // Rate Limit
        $options['yaml_settings']['rate-limit'] = isset($saved_options['yaml_settings']['rate-limit'])
            ? wp_parse_args($saved_options['yaml_settings']['rate-limit'], $default_options['yaml_settings']['rate-limit'])
            : $default_options['yaml_settings']['rate-limit'];

        // Retry Policy
        if (isset($saved_options['yaml_settings']['retry-policy'])) {
            $options['yaml_settings']['retry-policy'] = wp_parse_args($saved_options['yaml_settings']['retry-policy'], $default_options['yaml_settings']['retry-policy']);
            // status-codes-no-retryは配列なので上書きが必要
            if (isset($saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'])) {
                $options['yaml_settings']['retry-policy']['status-codes-no-retry'] = $saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'];
            }
        } else {
            $options['yaml_settings']['retry-policy'] = $default_options['yaml_settings']['retry-policy'];
        }

        // Disallow（空配列でも保存値を優先）
        $options['yaml_settings']['disallow'] = isset($saved_options['yaml_settings']['disallow'])
            ? $saved_options['yaml_settings']['disallow']
            : $default_options['yaml_settings']['disallow'];

        // Allowed Bots（空配列でも保存値を優先）
        $options['yaml_settings']['allowed-bots'] = isset($saved_options['yaml_settings']['allowed-bots'])
            ? $saved_options['yaml_settings']['allowed-bots']
            : $default_options['yaml_settings']['allowed-bots'];

        // Canonical URL
        $options['yaml_settings']['canonical-url'] = isset($saved_options['yaml_settings']['canonical-url']) && !empty(trim($saved_options['yaml_settings']['canonical-url']))
            ? $saved_options['yaml_settings']['canonical-url']
            : $default_options['yaml_settings']['canonical-url'];

        // Sitemap
        $options['yaml_settings']['sitemap'] = isset($saved_options['yaml_settings']['sitemap']) && !empty(trim($saved_options['yaml_settings']['sitemap']))
            ? $saved_options['yaml_settings']['sitemap']
            : $default_options['yaml_settings']['sitemap'];
    } else {
        $options['yaml_settings'] = $default_options['yaml_settings'];
    }

    $posts_per_page = $options['posts_per_page'];
    $saved_selected_types = $options['selected_types'];
    $enable_yaml_footer = $options['enable_yaml_header'];
    $yaml_settings = $options['yaml_settings'];
    $show_copyright_footer = $options['show_copyright_footer'];
    $cache_duration = $options['cache_duration'];

    $save_nonce_action = 'save_seo_llmstxt_settings_nonce';
    $save_nonce_name = 'kashiwazaki_seo_llmstxt_nonce_field';

    if ( isset( $_POST['save_settings'] ) && check_admin_referer( $save_nonce_action, $save_nonce_name ) ) {
        $new_posts_per_page = isset( $_POST['posts_per_page'] ) && is_numeric( $_POST['posts_per_page'] ) && $_POST['posts_per_page'] > 0
            ? max( 1, min( 10000, intval( $_POST['posts_per_page'] ) ) )
            : $default_options['posts_per_page'];
        $new_selected_types_input = isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] ) ? array_map( 'sanitize_key', $_POST['post_types'] ) : array();

        if ( empty( $new_selected_types_input ) ) {
            $message .= '<div class="notice notice-error is-dismissible"><p>エラー: 最低1つの投稿タイプを選択してください。</p></div>';
        } else {
            $all_available_types_keys = array_keys(get_post_types( ['public' => true, 'show_ui' => true], 'names' ) + ['attachment' => 'attachment']);
            $new_selected_types = array_intersect($new_selected_types_input, $all_available_types_keys);

            $new_enable_yaml_footer = isset($_POST['enable_yaml_header']);
            $new_show_copyright_footer = isset($_POST['show_copyright_footer']);
            $new_cache_duration = isset($_POST['cache_duration']) ? sanitize_text_field(wp_unslash($_POST['cache_duration'])) : '0';

            // 多次元配列の再帰的サニタイゼーション関数
            $sanitize_recursive = function($data) use (&$sanitize_recursive) {
                if (is_array($data)) {
                    return array_map($sanitize_recursive, $data);
                }
                return sanitize_text_field($data);
            };
            
            $new_yaml_settings = isset($_POST['yaml_settings']) && is_array($_POST['yaml_settings']) 
                ? $sanitize_recursive(wp_unslash($_POST['yaml_settings'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                : [];
            $sanitized_yaml = $default_options['yaml_settings']; // Start with defaults

            // License
            $sanitized_yaml['license']['allow-ai-training'] = isset($new_yaml_settings['license']['allow-ai-training']);
            $sanitized_yaml['license']['allow-ai-commercial-use'] = isset($new_yaml_settings['license']['allow-ai-commercial-use']);

            // Rate Limit
            $sanitized_yaml['rate-limit']['enabled'] = isset($new_yaml_settings['rate-limit']['enabled']);
            $sanitized_yaml['rate-limit']['requests-per-second'] = isset($new_yaml_settings['rate-limit']['requests-per-second']) && is_numeric($new_yaml_settings['rate-limit']['requests-per-second']) && $new_yaml_settings['rate-limit']['requests-per-second'] >= 0 ? floatval($new_yaml_settings['rate-limit']['requests-per-second']) : $default_options['yaml_settings']['rate-limit']['requests-per-second'];
            $sanitized_yaml['rate-limit']['requests-per-minute'] = isset($new_yaml_settings['rate-limit']['requests-per-minute']) && is_numeric($new_yaml_settings['rate-limit']['requests-per-minute']) && $new_yaml_settings['rate-limit']['requests-per-minute'] >= 0 ? intval($new_yaml_settings['rate-limit']['requests-per-minute']) : $default_options['yaml_settings']['rate-limit']['requests-per-minute'];
            
            // Canonical URL
            $sanitized_yaml['canonical-url'] = isset($new_yaml_settings['canonical-url']) ? esc_url_raw(trim($new_yaml_settings['canonical-url'])) : '';
            if (empty($sanitized_yaml['canonical-url'])) { $sanitized_yaml['canonical-url'] = $default_options['yaml_settings']['canonical-url']; }

            // Retry Policy
            $sanitized_yaml['retry-policy']['retry'] = isset($new_yaml_settings['retry-policy']['retry']);
            $sanitized_yaml['retry-policy']['max-retries'] = isset($new_yaml_settings['retry-policy']['max-retries']) && is_numeric($new_yaml_settings['retry-policy']['max-retries']) && $new_yaml_settings['retry-policy']['max-retries'] >= 0 ? intval($new_yaml_settings['retry-policy']['max-retries']) : $default_options['yaml_settings']['retry-policy']['max-retries'];
            $sanitized_yaml['retry-policy']['wait-seconds'] = isset($new_yaml_settings['retry-policy']['wait-seconds']) && is_numeric($new_yaml_settings['retry-policy']['wait-seconds']) && $new_yaml_settings['retry-policy']['wait-seconds'] >= 0 ? intval($new_yaml_settings['retry-policy']['wait-seconds']) : (isset($new_yaml_settings['retry-policy']['retry-after-seconds']) && is_numeric($new_yaml_settings['retry-policy']['retry-after-seconds']) ? intval($new_yaml_settings['retry-policy']['retry-after-seconds']) : $default_options['yaml_settings']['retry-policy']['wait-seconds']);
            
            // F16 (audit-impl-r2): is_string ガードで配列 POST に対する trim/explode TypeError を防止
            if (isset($new_yaml_settings['retry-policy']['status-codes-no-retry'])
                && is_string($new_yaml_settings['retry-policy']['status-codes-no-retry'])
                && trim($new_yaml_settings['retry-policy']['status-codes-no-retry']) !== '') {
                $codes = explode(',', $new_yaml_settings['retry-policy']['status-codes-no-retry']);
                // F16 (audit-r4): is_numeric は '5e2' '0xFF' を通すため preg_match で 3 桁 100-599 のみ許可
                $sanitized_yaml['retry-policy']['status-codes-no-retry'] = array_values(array_filter(array_map(function($code) {
                    $trimmed_code = trim( (string) $code );
                    return preg_match( '/^[1-5]\d{2}$/', $trimmed_code ) ? $trimmed_code : null;
                }, $codes)));
            } else {
                $sanitized_yaml['retry-policy']['status-codes-no-retry'] = [];
            }

            // Disallow (POSTから直接取得して改行を保持)
            if (isset($_POST['yaml_settings']['disallow'])) {
                $disallow_raw = sanitize_textarea_field(wp_unslash($_POST['yaml_settings']['disallow']));
                if (trim($disallow_raw) !== '') {
                    $paths = explode("\n", str_replace(["\r\n", "\r"], "\n", $disallow_raw));
                    $sanitized_yaml['disallow'] = array_values(array_filter(array_map(function($path) {
                        $trimmed_path = trim($path);
                        if (!empty($trimmed_path) && strpos($trimmed_path, '/') === 0 && preg_match('/^[\/a-zA-Z0-9\-_\.\*]+$/', $trimmed_path)) { return sanitize_text_field($trimmed_path); } return null;
                    }, $paths)));
                } else {
                    $sanitized_yaml['disallow'] = [];
                }
            } else {
                $sanitized_yaml['disallow'] = [];
            }

            // Allowed Bots (POSTから直接取得して改行を保持)
            if (isset($_POST['yaml_settings']['allowed-bots'])) {
                $bots_raw = sanitize_textarea_field(wp_unslash($_POST['yaml_settings']['allowed-bots']));
                if (trim($bots_raw) !== '') {
                    $bots = explode("\n", str_replace(["\r\n", "\r"], "\n", $bots_raw));
                    $sanitized_yaml['allowed-bots'] = array_values(array_filter(array_map(function($bot) {
                        $trimmed_bot = trim($bot);
                        if (!empty($trimmed_bot) && preg_match('/^[a-zA-Z0-9\-_\.]+$/', $trimmed_bot)) { return sanitize_text_field($trimmed_bot); } return null;
                    }, $bots)));
                } else {
                    $sanitized_yaml['allowed-bots'] = [];
                }
            } else {
                $sanitized_yaml['allowed-bots'] = [];
            }

            // Sitemap
            $sanitized_yaml['sitemap'] = isset($new_yaml_settings['sitemap']) ? esc_url_raw(trim($new_yaml_settings['sitemap'])) : '';
             if (empty($sanitized_yaml['sitemap'])) { $sanitized_yaml['sitemap'] = $default_options['yaml_settings']['sitemap']; }

            // A-2: 概要版オプション
            $new_summary_inline_excerpt = isset($_POST['summary_inline_excerpt']);
            $new_summary_excerpt_length = isset($_POST['summary_excerpt_length']) && is_numeric($_POST['summary_excerpt_length'])
                ? max(20, min(200, intval($_POST['summary_excerpt_length'])))
                : $default_options['summary_excerpt_length'];

            // A-1: E-E-A-T オプション
            $new_enable_eeat_block = isset($_POST['enable_eeat_block']);
            $eeat_input = isset($_POST['eeat_settings']) && is_array($_POST['eeat_settings'])
                ? wp_unslash($_POST['eeat_settings'])
                : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            $sanitized_eeat = [
                'organization_name'    => isset($eeat_input['organization_name'])    ? sanitize_text_field( $eeat_input['organization_name'] ) : '',
                'organization_url'     => isset($eeat_input['organization_url'])     ? esc_url_raw( trim( $eeat_input['organization_url'] ) ) : '',
                'organization_address' => isset($eeat_input['organization_address']) ? sanitize_text_field( $eeat_input['organization_address'] ) : '',
                'organization_phone'   => isset($eeat_input['organization_phone'])   ? preg_replace( '/[^0-9+\-() ]/', '', $eeat_input['organization_phone'] ) : '',
                'organization_email'   => '',
                'representative_name'  => isset($eeat_input['representative_name'])  ? sanitize_text_field( $eeat_input['representative_name'] ) : '',
                'representative_title' => isset($eeat_input['representative_title']) ? sanitize_text_field( $eeat_input['representative_title'] ) : '',
                'expertise_areas'      => isset($eeat_input['expertise_areas'])      ? sanitize_textarea_field( $eeat_input['expertise_areas'] ) : '',
                'credentials'          => isset($eeat_input['credentials'])          ? sanitize_textarea_field( $eeat_input['credentials'] ) : '',
                'editorial_policy_url' => isset($eeat_input['editorial_policy_url']) ? esc_url_raw( trim( $eeat_input['editorial_policy_url'] ) ) : '',
                'editorial_policy_text'=> '',
            ];

            // email: sanitize_email + is_email
            if ( isset( $eeat_input['organization_email'] ) ) {
                $email_raw = sanitize_email( $eeat_input['organization_email'] );
                $sanitized_eeat['organization_email'] = is_email( $email_raw ) ? $email_raw : '';
            }

            // editorial_policy_text: 文字数 200 で truncate (mb_strlen ガード + mb_substr)
            if ( isset( $eeat_input['editorial_policy_text'] ) ) {
                $text_raw = sanitize_textarea_field( $eeat_input['editorial_policy_text'] );
                $sanitized_eeat['editorial_policy_text'] = mb_strlen( $text_raw, 'UTF-8' ) > 200
                    ? mb_substr( $text_raw, 0, 200, 'UTF-8' )
                    : $text_raw;
            }

            $new_options_to_save = [
                'posts_per_page' => $new_posts_per_page,
                'selected_types' => $new_selected_types,
                'enable_yaml_header' => $new_enable_yaml_footer,
                'yaml_settings'  => $sanitized_yaml,
                'show_copyright_footer' => $new_show_copyright_footer,
                'cache_duration' => $new_cache_duration,
                'summary_inline_excerpt' => $new_summary_inline_excerpt,
                'summary_excerpt_length' => $new_summary_excerpt_length,
                'enable_eeat_block' => $new_enable_eeat_block,
                'eeat_settings' => $sanitized_eeat,
            ];
            update_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, $new_options_to_save );

            $options = $new_options_to_save;
            $posts_per_page = $new_posts_per_page;
            $saved_selected_types = $new_selected_types;
            $enable_yaml_footer = $new_enable_yaml_footer;
            $yaml_settings = $sanitized_yaml;
            $show_copyright_footer = $new_show_copyright_footer;
            $cache_duration = $new_cache_duration;

            $message .= '<div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>';
        }
    }

    $all_public_types = get_post_types( ['public' => true, 'show_ui' => true ], 'objects' );
    $all_types = $all_public_types;
    $attachment_object = get_post_type_object('attachment');
    if ($attachment_object && $attachment_object->show_ui) {
         if (!isset($attachment_object->labels)) $attachment_object->labels = new stdClass();
         $attachment_object->labels->name = $attachment_object->labels->name ?? 'メディア';
        $all_types['attachment'] = $attachment_object;
    }
    $available_type_keys = array_keys($all_types);
    $current_selected_types = array_intersect($saved_selected_types, $available_type_keys);

    $plugin_version = defined('KASHIWAZAKI_SEO_LLMSTXT_VERSION') ? KASHIWAZAKI_SEO_LLMSTXT_VERSION : '1.0.0';
    
    // ファイル生成の有効/無効状態を取得
    $llms_txt_enabled = get_option('kashiwazaki_llms_txt_enabled', true);
    $llms_full_txt_enabled = get_option('kashiwazaki_llms_full_txt_enabled', true);
    
    // YAML設定用の文字列準備
    $status_codes_string = isset($yaml_settings['retry-policy']['status-codes-no-retry']) && is_array($yaml_settings['retry-policy']['status-codes-no-retry']) ? implode(', ', $yaml_settings['retry-policy']['status-codes-no-retry']) : '';
    $disallow_string = isset($yaml_settings['disallow']) && is_array($yaml_settings['disallow']) ? implode("\n", $yaml_settings['disallow']) : '';
    $allowed_bots_string = isset($yaml_settings['allowed-bots']) && is_array($yaml_settings['allowed-bots']) ? implode("\n", $yaml_settings['allowed-bots']) : '';

    $settings_html_file = KASHIWAZAKI_SEO_LLMSTXT_PLUGIN_PATH . 'admin/settings-page-html.php';
    if ( file_exists( $settings_html_file ) ) {
        require $settings_html_file;
    } else {
        echo '<div class="notice notice-error"><p>Kashiwazaki SEO LLMs.txt Generator: Critical file admin/settings-page-html.php not found.</p></div>';
    }
}
?>