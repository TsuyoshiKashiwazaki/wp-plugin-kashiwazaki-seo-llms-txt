<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * テキストファイル用の安全な出力関数
 * HTMLエンティティ化せず、HTMLタグのみ除去
 */
function kashiwazaki_safe_text_output( $content ) {
    // HTMLタグを除去し、改行とスペースは保持
    $content = wp_strip_all_tags( $content );
    // 不正な制御文字を除去（タブ、改行、キャリッジリターンは保持）
    $content = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content );
    return $content;
}

function kashiwazaki_seo_llmstxt_get_default_options() {
    $site_home_url = home_url('/');
    $default_sitemap_url = $site_home_url . 'sitemap.xml';
    return [
        'posts_per_page' => 1000,
        'selected_types' => ['post', 'page'],
        'enable_yaml_header' => true,
        'yaml_settings' => [
             'license' => ['allow-ai-training' => true, 'allow-ai-commercial-use' => true],
             'rate-limit' => ['enabled' => false, 'requests-per-second' => 5, 'requests-per-minute' => 300],
             'canonical-url' => $site_home_url,
             'retry-policy' => ['retry' => true, 'max-retries' => 2, 'wait-seconds' => 30, 'status-codes-no-retry' => [ '400', '401', '403', '404' ]],
             'disallow' => [ '/wp-admin/', '/wp-login.php', '/wp-includes/', '/trackback/' ],
             'allowed-bots' => [ 'GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'Claude-Web', 'PerplexityBot', 'PerplexityAI', 'Google-Extended', 'Applebot-Extended', 'CCBot' ],
             'sitemap' => $default_sitemap_url,
        ],
        'show_copyright_footer' => true,
        'cache_duration' => '0', // デフォルトはキャッシュなし
    ];
}

function kashiwazaki_seo_generate_llms_content( $full = false ) {
    $default_options = kashiwazaki_seo_llmstxt_get_default_options();
    $saved_options = get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, [] );

    // yaml_settingsは個別に処理するため、トップレベルのみマージ
    $options = [];
    $options['posts_per_page'] = isset($saved_options['posts_per_page']) ? $saved_options['posts_per_page'] : $default_options['posts_per_page'];
    $options['selected_types'] = isset($saved_options['selected_types']) ? $saved_options['selected_types'] : $default_options['selected_types'];
    $options['enable_yaml_header'] = isset($saved_options['enable_yaml_header']) ? $saved_options['enable_yaml_header'] : $default_options['enable_yaml_header'];
    $options['show_copyright_footer'] = isset($saved_options['show_copyright_footer']) ? $saved_options['show_copyright_footer'] : $default_options['show_copyright_footer'];

    if (isset($saved_options['yaml_settings'])) {
        // Use saved yaml_settings as base, only fill missing keys with defaults
        $options['yaml_settings'] = $saved_options['yaml_settings'];
        
        // Only set defaults for missing keys, not empty arrays
        if ( ! isset( $options['yaml_settings']['license'] ) ) {
            $options['yaml_settings']['license'] = $default_options['yaml_settings']['license'];
        } else {
            $options['yaml_settings']['license'] = wp_parse_args($options['yaml_settings']['license'], $default_options['yaml_settings']['license']);
        }
        
        if ( ! isset( $options['yaml_settings']['rate-limit'] ) ) {
            $options['yaml_settings']['rate-limit'] = $default_options['yaml_settings']['rate-limit'];
        } else {
            $options['yaml_settings']['rate-limit'] = wp_parse_args($options['yaml_settings']['rate-limit'], $default_options['yaml_settings']['rate-limit']);
        }
        
        if ( ! isset( $options['yaml_settings']['retry-policy'] ) ) {
            $options['yaml_settings']['retry-policy'] = $default_options['yaml_settings']['retry-policy'];
        } else {
            $options['yaml_settings']['retry-policy'] = wp_parse_args($options['yaml_settings']['retry-policy'], $default_options['yaml_settings']['retry-policy']);
            
            // For status-codes-no-retry, preserve empty arrays
            if ( ! isset( $saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'] ) ) {
                $options['yaml_settings']['retry-policy']['status-codes-no-retry'] = $default_options['yaml_settings']['retry-policy']['status-codes-no-retry'];
            } else {
                $options['yaml_settings']['retry-policy']['status-codes-no-retry'] = $saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'];
            }
        }
        
        // For disallow, preserve empty arrays
        if ( ! isset( $saved_options['yaml_settings']['disallow'] ) ) {
            $options['yaml_settings']['disallow'] = $default_options['yaml_settings']['disallow'];
        } else {
            $options['yaml_settings']['disallow'] = $saved_options['yaml_settings']['disallow'];
        }

        // For allowed-bots, preserve empty arrays
        if ( ! isset( $saved_options['yaml_settings']['allowed-bots'] ) ) {
            $options['yaml_settings']['allowed-bots'] = isset($default_options['yaml_settings']['allowed-bots']) ? $default_options['yaml_settings']['allowed-bots'] : [];
        } else {
            $options['yaml_settings']['allowed-bots'] = $saved_options['yaml_settings']['allowed-bots'];
        }

        $options['yaml_settings']['canonical-url'] = isset($options['yaml_settings']['canonical-url']) && is_string($options['yaml_settings']['canonical-url']) && !empty(trim($options['yaml_settings']['canonical-url'])) ? trim($options['yaml_settings']['canonical-url']) : $default_options['yaml_settings']['canonical-url'];
        $options['yaml_settings']['sitemap'] = isset($options['yaml_settings']['sitemap']) && is_string($options['yaml_settings']['sitemap']) && !empty(trim($options['yaml_settings']['sitemap'])) ? trim($options['yaml_settings']['sitemap']) : $default_options['yaml_settings']['sitemap'];

    } else {
        $options['yaml_settings'] = $default_options['yaml_settings'];
    }

    $posts_per_page_per_type = isset( $options['posts_per_page'] ) ? max(1, intval($options['posts_per_page'])) : $default_options['posts_per_page'];
    $selected_types = isset( $options['selected_types'] ) && is_array( $options['selected_types'] ) ? array_map('sanitize_key', $options['selected_types']) : $default_options['selected_types'];
    $enable_yaml_footer = isset($options['enable_yaml_header']) ? (bool)$options['enable_yaml_header'] : $default_options['enable_yaml_header'];
    $yaml_settings = $options['yaml_settings'];
    $show_copyright_footer = isset($options['show_copyright_footer']) ? (bool)$options['show_copyright_footer'] : $default_options['show_copyright_footer'];

    $available_post_types = get_post_types(['public' => true, 'show_ui' => true], 'names');
    if (in_array('attachment', $selected_types) && post_type_exists('attachment')) {
        $attachment_object = get_post_type_object('attachment');
        if ($attachment_object && $attachment_object->show_ui) { $available_post_types['attachment'] = 'attachment'; }
    }
    $valid_selected_types = array_intersect($selected_types, array_keys($available_post_types));

    if ( empty($valid_selected_types) ) {
        return "# " . get_bloginfo('name') . "\n\nエラー: 有効な投稿タイプがプラグイン設定で選択されていません。ファイルを生成するには、少なくとも1つの投稿タイプを選択してください。\n";
    }

    $yaml_output = '';
    if ($enable_yaml_footer) {
        $temp_yaml_content = '';
        if (isset($yaml_settings['license'])) {
            $temp_yaml_content .= "## コンテンツライセンス設定\n";
            $temp_yaml_content .= "license:\n";
            $allow_training = !empty($yaml_settings['license']['allow-ai-training']);
            $allow_commercial = !empty($yaml_settings['license']['allow-ai-commercial-use']);
            $temp_yaml_content .= "  allow-ai-training: " . ($allow_training ? 'true' : 'false') . "\n";
            $temp_yaml_content .= "  allow-ai-commercial-use: " . ($allow_commercial ? 'true' : 'false') . "\n\n";
        }
        if (isset($yaml_settings['rate-limit']) && !empty($yaml_settings['rate-limit']['enabled'])) {
            $temp_yaml_content .= "## AIクローラーのアクセス頻度制限\n";
            $temp_yaml_content .= "rate-limit:\n";
            $rps = isset($yaml_settings['rate-limit']['requests-per-second']) && is_numeric($yaml_settings['rate-limit']['requests-per-second']) ? floatval($yaml_settings['rate-limit']['requests-per-second']) : $default_options['yaml_settings']['rate-limit']['requests-per-second'];
            $rpm = isset($yaml_settings['rate-limit']['requests-per-minute']) && is_numeric($yaml_settings['rate-limit']['requests-per-minute']) ? intval($yaml_settings['rate-limit']['requests-per-minute']) : $default_options['yaml_settings']['rate-limit']['requests-per-minute'];
            $temp_yaml_content .= "  requests-per-second: " . esc_html($rps) . "\n";
            $temp_yaml_content .= "  requests-per-minute: " . esc_html($rpm) . "\n\n";
        }
        $canonical_url_val = $yaml_settings['canonical-url'];
        if (!empty($canonical_url_val)) {
            $temp_yaml_content .= "## 正規ドメイン指定\n";
            $temp_yaml_content .= "canonical-url: " . esc_url($canonical_url_val) . "\n\n";
        }
        if (isset($yaml_settings['retry-policy'])) {
            $temp_yaml_content .= "# エラー時のリトライポリシー\n";
            $temp_yaml_content .= "retry-policy:\n";
            $retry_enabled = !empty($yaml_settings['retry-policy']['retry']);
            $temp_yaml_content .= "  retry: " . ($retry_enabled ? 'true' : 'false') . "\n";
            if ($retry_enabled) {
                $max_retries = isset($yaml_settings['retry-policy']['max-retries']) && is_numeric($yaml_settings['retry-policy']['max-retries']) ? intval($yaml_settings['retry-policy']['max-retries']) : $default_options['yaml_settings']['retry-policy']['max-retries'];
                $wait_seconds = isset($yaml_settings['retry-policy']['wait-seconds']) && is_numeric($yaml_settings['retry-policy']['wait-seconds']) ? intval($yaml_settings['retry-policy']['wait-seconds']) : (isset($yaml_settings['retry-policy']['retry-after-seconds']) && is_numeric($yaml_settings['retry-policy']['retry-after-seconds']) ? intval($yaml_settings['retry-policy']['retry-after-seconds']) : $default_options['yaml_settings']['retry-policy']['wait-seconds']);
                $temp_yaml_content .= "  max-retries: " . esc_html($max_retries) . "\n";
                $temp_yaml_content .= "  wait-seconds: " . esc_html($wait_seconds) . "\n";
            }
            if (!empty($yaml_settings['retry-policy']['status-codes-no-retry']) && is_array($yaml_settings['retry-policy']['status-codes-no-retry'])) {
                $valid_codes = array_filter($yaml_settings['retry-policy']['status-codes-no-retry'], fn($code) => is_numeric(trim($code)));
                if (!empty($valid_codes)) {
                    $temp_yaml_content .= "  status-codes-no-retry:\n";
                    foreach ($valid_codes as $code) { $temp_yaml_content .= "    - \"" . esc_html(trim($code)) . "\"\n"; }
                }
            }
            $temp_yaml_content .= "\n";
        }
        if (!empty($yaml_settings['disallow']) && is_array($yaml_settings['disallow'])) {
             $valid_paths = array_filter($yaml_settings['disallow'], fn($path) => !empty(trim($path)) && strpos(trim($path), '/') === 0);
            if (!empty($valid_paths)) {
                $temp_yaml_content .= "# AIクロールから除外するパス\n";
                $temp_yaml_content .= "disallow:\n";
                foreach ($valid_paths as $path) { $temp_yaml_content .= "  - " . esc_html(trim($path)) . "\n"; }
                $temp_yaml_content .= "\n";
            }
        }
        if (!empty($yaml_settings['allowed-bots']) && is_array($yaml_settings['allowed-bots'])) {
            $valid_bots = array_filter($yaml_settings['allowed-bots'], fn($bot) => !empty(trim($bot)));
            if (!empty($valid_bots)) {
                $temp_yaml_content .= "# 許可するAIボット\n";
                $temp_yaml_content .= "allowed-bots:\n";
                foreach ($valid_bots as $bot) {
                    $temp_yaml_content .= "  - " . esc_html(trim($bot)) . "\n";
                }
                $temp_yaml_content .= "\n";
            }
        }
        $sitemap_url_val = $yaml_settings['sitemap'];
        if (!empty($sitemap_url_val)) {
            $temp_yaml_content .= "# サイトマップURL指定\n";
            $temp_yaml_content .= "sitemap: " . esc_url($sitemap_url_val) . "\n\n";
        }
        if (!empty(trim($temp_yaml_content))) {
            $yaml_output = "# LLMサイト設定（YAMLフッター）\n";
            $yaml_output .= trim($temp_yaml_content);
        }
    }

    $site_name = get_bloginfo( 'name' );
    $site_desc = get_bloginfo( 'description' );
    $generation_date = wp_date(get_option('date_format') . ' ' . get_option('time_format'));

    $output = "# {$site_name}\n";
    if ($site_desc) { $output .= "> {$site_desc}\n\n"; } else { $output .= "\n"; }
    $output .= "ファイル生成日時: {$generation_date}\n\n";


    if ($full) {
        $output .= "ファイル種別: 詳細版 (llms-full.txt)\n";
        $output .= "> 各コンテンツのタイトル、リンク、最終更新日時(UTC)、公開日時(UTC)、抜粋を含みます。\n\n";
    } else {
        $output .= "ファイル種別: 概要版 (llms.txt)\n";
        $output .= "> 各コンテンツのタイトルとリンクのリストです。\n\n";
    }

    $output .= "## コンテンツリスト\n";
    $output .= sprintf( "以下に、指定された投稿タイプごとのコンテンツリストを示します。（各タイプ最大 %d 件、新しい順）", $posts_per_page_per_type ) . "\n\n";

    $has_content = false;

    foreach( $valid_selected_types as $post_type ) {
        $post_type_object = get_post_type_object( $post_type );
        if ( ! $post_type_object ) continue;
        $post_type_label = $post_type_object->labels->name ?? $post_type;

        $args = [
            'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $posts_per_page_per_type,
            'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true, 'no_found_rows'  => true,
        ];
        $posts_query = new WP_Query( $args );

        if ( $posts_query->have_posts() ) {
            $has_content = true;
            $output .= "### " . esc_html( $post_type_label ) . " (`" . esc_html($post_type) . "`)\n";

            while ( $posts_query->have_posts() ) {
                $posts_query->the_post();
                $post_id = get_the_ID();
                $title = trim(get_the_title());
                $url = get_permalink();

                $noindex_keys = apply_filters('kashiwazaki_seo_llmstxt_noindex_keys', ['_yoast_wpseo_meta-robots-noindex', '_aioseop_robots_noindex', '_rank_math_robots']);
                $is_noindex = false;
                foreach($noindex_keys as $key) {
                    $meta_value = get_post_meta($post_id, $key, true);
                    if ($key === '_rank_math_robots' && is_array($meta_value) && in_array('noindex', $meta_value)) { $is_noindex = true; break; }
                    elseif ($key !== '_rank_math_robots' && (in_array(strtolower((string)$meta_value), ['1', 'on', 'yes', 'true'], true) || $meta_value === true)) { $is_noindex = true; break; }
                }
                if ( post_password_required($post_id) ) { $is_noindex = true; }

                if ( empty( $title ) || empty( $url ) || !filter_var($url, FILTER_VALIDATE_URL) || $is_noindex ) { continue; }

                if ( !$full ) {
                    $output .= "- [" . esc_html( $title ) . "](" . esc_url( $url ) . ")\n";
                } else {
                    $current_post_obj = get_post($post_id);
                    $excerpt = '';
                    if ( has_excerpt($post_id) ) {
                        $excerpt = trim(get_the_excerpt($post_id));
                    } else if ( ! empty( $current_post_obj->post_content ) ) {
                        $content = wp_strip_all_tags( strip_shortcodes( $current_post_obj->post_content ) );
                        $content = preg_replace( '/\s+/', ' ', $content );
                        $excerpt_length = apply_filters('kashiwazaki_seo_llmstxt_excerpt_length', 150);
                        $excerpt = mb_substr( trim($content), 0, $excerpt_length, 'UTF-8' );
                        if ( mb_strlen( trim($content), 'UTF-8' ) > $excerpt_length ) { $excerpt .= '...'; }
                    } else if ( $current_post_obj->post_type === 'attachment' ) {
                         $caption = wp_get_attachment_caption($post_id);
                         if ( !empty($caption) ) { $excerpt = mb_substr(trim($caption), 0, 150, 'UTF-8') . (mb_strlen(trim($caption), 'UTF-8') > 150 ? '...' : ''); }
                         else { $alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
                              if ( !empty($alt) ) { $excerpt = mb_substr(trim($alt), 0, 150, 'UTF-8') . (mb_strlen(trim($alt), 'UTF-8') > 150 ? '...' : ''); }
                              else { $desc = $current_post_obj->post_content;
                                  if ( !empty($desc) ) { $desc_stripped = preg_replace('/\s+/', ' ', wp_strip_all_tags($desc)); $excerpt = mb_substr( trim($desc_stripped), 0, 150, 'UTF-8' ) . (mb_strlen(trim($desc_stripped), 'UTF-8') > 150 ? '...' : ''); }
                              } } }
                    if ( empty( $excerpt ) ) { $excerpt = '(抜粋はありません)'; }

                    $published_date_utc = get_post_time('c', true, $post_id);
                    $modified_date_utc = get_post_modified_time('c', true, $post_id);

                    $output .= "#### " . esc_html( $title ) . "\n";
                    $output .= "- リンク: " . esc_url( $url ) . "\n";
                    $output .= "- 公開日時 (UTC): " . esc_html( $published_date_utc ?: 'N/A' ) . "\n";
                    $output .= "- 最終更新日時 (UTC): " . esc_html( $modified_date_utc ?: 'N/A' ) . "\n";
                    $output .= "- 抜粋: " . esc_html( $excerpt ) . "\n\n";
                }
            }
            wp_reset_postdata();
            if (!$full) { $output .= "\n"; }
        }
    }

    if ( !$has_content ) {
        $output .= "指定された条件に一致する公開済みコンテンツは見つかりませんでした。\n";
    }

    if ($enable_yaml_footer && !empty($yaml_output)) {
        $output .= "\n";
        $output .= $yaml_output;
    }

    if ($show_copyright_footer) {
        $plugin_name = 'Kashiwazaki SEO LLMs.txt Generator';
        $author_name = 'Tsuyoshi Kashiwazaki';
        $author_uri = 'https://www.tsuyoshikashiwazaki.jp';
        $plugin_version = defined('KASHIWAZAKI_SEO_LLMSTXT_VERSION') ? KASHIWAZAKI_SEO_LLMSTXT_VERSION : '1.0.0';

        $copyright_info = "\n\n";
        $copyright_info .= "# このファイルについて\n\n";
        $copyright_info .= "- 生成プラグイン: " . sprintf( '%1$s%2$s', esc_html($plugin_name), ($plugin_version ? ' v' . esc_html($plugin_version) : '') ) . "\n";
        $copyright_info .= "- 制作者: " . sprintf( '%1$s (%2$s)', esc_html($author_name), esc_url($author_uri) ) . "\n";
        $copyright_info .= "- ライセンス情報: この生成ツールは GPLv2 以降のライセンスで提供されています。リストされたコンテンツの著作権はサイト所有者に帰属します。\n";

        $output .= $copyright_info;
    }
    return $output;
}

function kashiwazaki_seo_llmstxt_add_query_vars( $vars ) {
    $vars[] = 'kswz_llms_request_type';
    return $vars;
}

function kashiwazaki_seo_llmstxt_setup_rewrite_rules() {
    add_rewrite_rule( '^llms\.txt$', 'index.php?kswz_llms_request_type=summary', 'top' );
    add_rewrite_rule( '^llms-full\.txt$', 'index.php?kswz_llms_request_type=full', 'top' );
    
    // WordPressの自動リダイレクト（末尾スラッシュ追加）を無効化
    add_filter( 'redirect_canonical', 'kashiwazaki_seo_llmstxt_disable_trailing_slash_redirect', 10, 2 );
}

function kashiwazaki_seo_llmstxt_disable_trailing_slash_redirect( $redirect_url, $requested_url ) {
    // llms.txt または llms-full.txt へのアクセスの場合はリダイレクトを無効化
    if ( preg_match( '/\/llms(-full)?\.txt$/i', $requested_url ) ) {
        return false;
    }
    return $redirect_url;
}

function kashiwazaki_seo_llmstxt_handle_dynamic_output() {
    global $wp_query;

    $request_type = get_query_var( 'kswz_llms_request_type' );

    if ( $request_type ) {
        $is_full = ( $request_type === 'full' );
        
        // ファイル生成が有効かチェック
        $option_name = $is_full ? 'kashiwazaki_llms_full_txt_enabled' : 'kashiwazaki_llms_txt_enabled';
        $is_enabled = get_option($option_name, true); // デフォルトは有効
        
        if (!$is_enabled) {
            // ファイル生成が無効の場合は404を返す
            status_header(404);
            nocache_headers();
            
            // WordPress の標準404処理
            $wp_query->set_404();
            return;
        }
        
        // キャッシュ設定を取得
        $options = get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, [] );
        $cache_duration = isset($options['cache_duration']) ? (int)$options['cache_duration'] : 0;
        
        // キャッシュキーを作成
        $cache_key = $is_full ? 'kashiwazaki_llms_full_txt_cache' : 'kashiwazaki_llms_txt_cache';
        
        // キャッシュが有効な場合
        if ($cache_duration !== 0) {
            // キャッシュからコンテンツを取得
            $cached_content = get_transient($cache_key);
            
            if ($cached_content !== false) {
                // キャッシュされたコンテンツを返す
                $content_with_bom = "\xEF\xBB\xBF" . $cached_content;
                
                header( 'Content-Type: text/plain; charset=utf-8' );
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: DENY');
                header('X-Kashiwazaki-Cache: HIT'); // キャッシュヒットを示すヘッダー
                nocache_headers();

                // テキストファイル出力のためHTMLエンティティ化は不要、タグ除去済み
                echo wp_strip_all_tags( $content_with_bom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                exit;
            }
        }
        
        // キャッシュがない場合、またはキャッシュが無効な場合は新規生成
        $content = kashiwazaki_seo_generate_llms_content( $is_full );
        
        // キャッシュが有効な場合は保存
        if ($cache_duration !== 0) {
            if ($cache_duration === -1) {
                // 永久キャッシュの場合は期限なし（実際には1年）
                set_transient($cache_key, $content, YEAR_IN_SECONDS);
            } else {
                // 指定された期間でキャッシュ
                set_transient($cache_key, $content, $cache_duration);
            }
        }
        
        $content_with_bom = "\xEF\xBB\xBF" . $content;

        header( 'Content-Type: text/plain; charset=utf-8' );
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-Kashiwazaki-Cache: MISS'); // キャッシュミスを示すヘッダー
        nocache_headers();

        // テキストファイル出力のためHTMLエンティティ化は不要、タグ除去済み
        echo wp_strip_all_tags( $content_with_bom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
}
?>