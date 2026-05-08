<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
        'cache_duration' => '0',
        // A-2: 概要版に 1 行要約 (default OFF、3者合意)
        'summary_inline_excerpt'  => false,
        'summary_excerpt_length'  => 70,
        // A-1: E-E-A-T メタブロック (default OFF、3者合意)
        'enable_eeat_block' => false,
        'eeat_settings' => [
            'organization_name'    => '',
            'organization_url'     => '',
            'organization_address' => '',
            'organization_phone'   => '',
            'organization_email'   => '',
            'representative_name'  => '',
            'representative_title' => '',
            'expertise_areas'      => '',
            'credentials'          => '',
            'editorial_policy_url' => '',
            'editorial_policy_text'=> '',
        ],
    ];
}

/**
 * 全オプションを default とマージして返す (内部ヘルパー)
 */
function kashiwazaki_seo_llmstxt_get_options() {
    $default_options = kashiwazaki_seo_llmstxt_get_default_options();
    $saved_options = get_option( KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, [] );
    $options = [];

    // F6 (full-audit-r2): runtime 読み出し時に 1〜10000 で clamp (DB に異常値が保存されている場合の DoS 対策)
    $raw_ppp = isset($saved_options['posts_per_page']) ? intval($saved_options['posts_per_page']) : $default_options['posts_per_page'];
    $options['posts_per_page'] = max(1, min(10000, $raw_ppp));
    $options['selected_types'] = isset($saved_options['selected_types']) ? $saved_options['selected_types'] : $default_options['selected_types'];
    $options['enable_yaml_header'] = isset($saved_options['enable_yaml_header']) ? $saved_options['enable_yaml_header'] : $default_options['enable_yaml_header'];
    $options['show_copyright_footer'] = isset($saved_options['show_copyright_footer']) ? $saved_options['show_copyright_footer'] : $default_options['show_copyright_footer'];
    $options['cache_duration'] = isset($saved_options['cache_duration']) ? $saved_options['cache_duration'] : $default_options['cache_duration'];

    // A-2 オプション
    $options['summary_inline_excerpt'] = isset($saved_options['summary_inline_excerpt']) ? (bool)$saved_options['summary_inline_excerpt'] : $default_options['summary_inline_excerpt'];
    $options['summary_excerpt_length'] = isset($saved_options['summary_excerpt_length']) ? max(20, min(200, intval($saved_options['summary_excerpt_length']))) : $default_options['summary_excerpt_length'];

    // A-1 オプション
    $options['enable_eeat_block'] = isset($saved_options['enable_eeat_block']) ? (bool)$saved_options['enable_eeat_block'] : $default_options['enable_eeat_block'];
    $options['eeat_settings'] = isset($saved_options['eeat_settings']) && is_array($saved_options['eeat_settings'])
        ? wp_parse_args($saved_options['eeat_settings'], $default_options['eeat_settings'])
        : $default_options['eeat_settings'];

    if (isset($saved_options['yaml_settings'])) {
        $options['yaml_settings'] = $saved_options['yaml_settings'];
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
            if ( ! isset( $saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'] ) ) {
                $options['yaml_settings']['retry-policy']['status-codes-no-retry'] = $default_options['yaml_settings']['retry-policy']['status-codes-no-retry'];
            } else {
                $options['yaml_settings']['retry-policy']['status-codes-no-retry'] = $saved_options['yaml_settings']['retry-policy']['status-codes-no-retry'];
            }
        }
        if ( ! isset( $saved_options['yaml_settings']['disallow'] ) ) {
            $options['yaml_settings']['disallow'] = $default_options['yaml_settings']['disallow'];
        } else {
            $options['yaml_settings']['disallow'] = $saved_options['yaml_settings']['disallow'];
        }
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
    return $options;
}

/**
 * A-2: 投稿の抜粋を取得 (Markdown リンク文法と衝突する文字を除去)
 */
function kashiwazaki_seo_llmstxt_get_excerpt_for_post( $post, $length ) {
    $length = max(20, min(500, intval($length)));
    $post_id = is_object($post) ? $post->ID : intval($post);
    $excerpt = '';

    if ( has_excerpt( $post_id ) ) {
        $excerpt = trim( get_the_excerpt( $post_id ) );
    } elseif ( ! empty( $post->post_content ) ) {
        $content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
        $content = preg_replace( '/\s+/', ' ', $content );
        $excerpt = mb_substr( trim( $content ), 0, $length, 'UTF-8' );
        if ( mb_strlen( trim( $content ), 'UTF-8' ) > $length ) {
            $excerpt .= '...';
        }
    } elseif ( $post->post_type === 'attachment' ) {
        $caption = wp_get_attachment_caption($post_id);
        if ( !empty($caption) ) {
            $excerpt = mb_substr(trim($caption), 0, $length, 'UTF-8') . (mb_strlen(trim($caption), 'UTF-8') > $length ? '...' : '');
        } else {
            $alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
            if ( !empty($alt) ) {
                $excerpt = mb_substr(trim($alt), 0, $length, 'UTF-8') . (mb_strlen(trim($alt), 'UTF-8') > $length ? '...' : '');
            }
        }
    }

    if ( empty( $excerpt ) ) {
        return '';
    }

    // Markdown リンク文法と衝突する文字を除去
    $excerpt = str_replace( ["\n", "\r", "\t", '[', ']', '(', ')'], ' ', $excerpt );
    $excerpt = preg_replace( '/\s+/', ' ', $excerpt );
    $excerpt = trim( $excerpt );

    return $excerpt;
}

/**
 * C-3: header (タイトル + blockquote + 生成日時 + ファイル種別) をレンダ
 *      A-3: 生成日時は last_modified_gmt 由来に固定 (RFC 7232 整合性)
 */
function kashiwazaki_seo_llmstxt_render_header( $options, $full, $last_modified_gmt = 0 ) {
    $site_name = get_bloginfo( 'name' );
    $site_desc = get_bloginfo( 'description' );

    // F7 (full-audit-r2): site_name / site_desc が Markdown 構造文字を含むと AI パーサーが
    // 見出し/blockquote/リンク行を誤認するため、F20 と同じ正規化を適用する
    $md_strip = function( $s ) {
        $s = (string) $s;
        $s = str_replace( ["\n", "\r", "\t", "[", "]", "(", ")", "`"], ' ', $s );
        return trim( preg_replace( '/\s+/', ' ', $s ) );
    };
    $site_name = $md_strip( $site_name );
    $site_desc = $md_strip( $site_desc );

    $ts = $last_modified_gmt > 0 ? (int) $last_modified_gmt : time();
    $generation_date = wp_date(get_option('date_format') . ' ' . get_option('time_format'), $ts);

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

    /**
     * Filter the rendered header section of llms.txt / llms-full.txt.
     *
     * @since 1.0.7
     * @param string $output  Header Markdown (タイトル+blockquote+生成日時+ファイル種別).
     * @param array  $options 全プラグインオプション.
     * @param bool   $full    true for llms-full.txt, false for llms.txt.
     */
    return apply_filters( 'kashiwazaki_seo_llmstxt_header', $output, $options, $full );
}

/**
 * A-1: E-E-A-T メタブロック (## About) をレンダ。default OFF、空時は出力なし。
 *      after_header フィルタ経由で挿入される。
 */
function kashiwazaki_seo_llmstxt_render_eeat_block( $options ) {
    if ( empty( $options['enable_eeat_block'] ) ) {
        return '';
    }
    $eeat = isset($options['eeat_settings']) && is_array($options['eeat_settings']) ? $options['eeat_settings'] : [];
    $eeat = apply_filters( 'kashiwazaki_seo_llmstxt_eeat_settings', $eeat, $options );

    $lines = [];

    // 運営者名 + URL (法人・個人事業主・フリーランス・個人・団体すべてカバー)
    $org_name = isset($eeat['organization_name']) ? trim((string)$eeat['organization_name']) : '';
    $org_url  = isset($eeat['organization_url'])  ? trim((string)$eeat['organization_url']) : '';
    if ( $org_name !== '' || $org_url !== '' ) {
        $name_part = $org_name !== '' ? $org_name : '';
        $url_part  = $org_url !== ''  ? ' (' . esc_url_raw($org_url) . ')' : '';
        $lines[] = '- 運営者: ' . $name_part . $url_part;
    }

    // 所在地
    $addr = isset($eeat['organization_address']) ? trim((string)$eeat['organization_address']) : '';
    if ( $addr !== '' ) {
        $lines[] = '- 所在地: ' . $addr;
    }

    // 電話 (任意)
    $phone = isset($eeat['organization_phone']) ? trim((string)$eeat['organization_phone']) : '';
    if ( $phone !== '' ) {
        $lines[] = '- 電話: ' . $phone;
    }

    // E-mail (任意、生のまま出力。難読化なし)
    //   設定画面で「公開出力に露出します」と明示警告済 → ユーザーの informed consent 前提
    //   AI クローラーは entity decoding するため text/plain での obfuscation は実効性が薄く UX を損なう
    //   ETag 安定性: 生メールも決定論的なので問題なし
    $email = isset($eeat['organization_email']) ? trim((string)$eeat['organization_email']) : '';
    if ( $email !== '' && is_email( $email ) ) {
        /**
         * Filter the rendered email output before it is added to the About block.
         *
         * @since 1.0.7
         * @param string $email_output  Default: raw email address.
         *                              IMPORTANT: filter callback MUST return a deterministic
         *                              string (no mt_rand / time-dependent values) to preserve
         *                              ETag stability for HTTP Conditional GET (RFC 7232).
         */
        $email_output = apply_filters( 'kashiwazaki_seo_llmstxt_email_output', $email );
        $email_output = is_string( $email_output ) ? trim( $email_output ) : '';
        if ( $email_output !== '' ) {
            $lines[] = '- E-mail: ' . $email_output;
        }
    }

    // 編集責任者 (E-E-A-T Authoritativeness/Trust シグナル、新聞・出版業界標準語)
    //   フォーム入力ラベルは『責任者名』だが出力では『編集責任者』に統一
    $rep_name = isset($eeat['representative_name']) ? trim((string)$eeat['representative_name']) : '';
    $rep_title = isset($eeat['representative_title']) ? trim((string)$eeat['representative_title']) : '';
    if ( $rep_name !== '' || $rep_title !== '' ) {
        $rep_part = $rep_name;
        if ( $rep_title !== '' ) {
            $rep_part = ($rep_part !== '' ? $rep_part . ' (' . $rep_title . ')' : $rep_title);
        }
        $lines[] = '- 編集責任者: ' . $rep_part;
    }

    // 専門領域 (nested list)
    $expertise_raw = isset($eeat['expertise_areas']) ? (string)$eeat['expertise_areas'] : '';
    $expertise_items = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $expertise_raw)))), 0, 20);
    if ( !empty($expertise_items) ) {
        $lines[] = '- 専門領域:';
        foreach ($expertise_items as $item) {
            $lines[] = '  - ' . wp_strip_all_tags($item);
        }
    }

    // 資格・経歴 (nested list)
    $cred_raw = isset($eeat['credentials']) ? (string)$eeat['credentials'] : '';
    $cred_items = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $cred_raw)))), 0, 20);
    if ( !empty($cred_items) ) {
        $lines[] = '- 資格・経歴:';
        foreach ($cred_items as $item) {
            $lines[] = '  - ' . wp_strip_all_tags($item);
        }
    }

    // 編集方針 (200 文字以内、文字数で truncate)
    $policy_text = isset($eeat['editorial_policy_text']) ? (string)$eeat['editorial_policy_text'] : '';
    $policy_text = wp_strip_all_tags($policy_text);
    if ( $policy_text !== '' ) {
        if ( mb_strlen($policy_text, 'UTF-8') > 200 ) {
            $policy_text = mb_substr($policy_text, 0, 200, 'UTF-8');
        }
        $lines[] = '- 編集方針: ' . $policy_text;
    }
    $policy_url = isset($eeat['editorial_policy_url']) ? trim((string)$eeat['editorial_policy_url']) : '';
    if ( $policy_url !== '' ) {
        $lines[] = '- 編集方針詳細: ' . esc_url_raw($policy_url);
    }

    if ( empty( $lines ) ) {
        return '';
    }

    /**
     * Filter the heading text for the E-E-A-T section.
     *
     * @since 1.0.7
     * @param string $heading  Default "## About"
     * @param array  $options  Plugin options
     */
    $heading = apply_filters( 'kashiwazaki_seo_llmstxt_eeat_section_heading', '## About', $options );

    $block = $heading . "\n\n" . implode("\n", $lines) . "\n\n";

    /**
     * Filter the entire E-E-A-T Markdown block before insertion.
     *
     * @since 1.0.7
     */
    return apply_filters( 'kashiwazaki_seo_llmstxt_eeat_block', $block, $eeat, $options );
}

/**
 * C-3 + A-2: 1 投稿エントリ (概要版/詳細版) をレンダ
 */
function kashiwazaki_seo_llmstxt_render_post_entry( $post, $options, $full ) {
    $post_id = $post->ID;
    $title = trim(get_the_title($post));
    // F20 (audit-r4): post title の Markdown 構造文字を空白置換 (link text 破壊防止)
    $title_md_safe = trim( preg_replace( '/\s+/', ' ', str_replace( ["\n", "\r", "\t", '[', ']', '(', ')', '`'], ' ', $title ) ) );
    $url = get_permalink($post);

    $noindex_keys = apply_filters('kashiwazaki_seo_llmstxt_noindex_keys', ['_yoast_wpseo_meta-robots-noindex', '_aioseop_robots_noindex', '_rank_math_robots']);
    $is_noindex = false;
    foreach($noindex_keys as $key) {
        $meta_value = get_post_meta($post_id, $key, true);
        if ($key === '_rank_math_robots' && is_array($meta_value) && in_array('noindex', $meta_value)) {
            $is_noindex = true; break;
        } elseif ($key !== '_rank_math_robots' && (in_array(strtolower((string)$meta_value), ['1', 'on', 'yes', 'true'], true) || $meta_value === true)) {
            $is_noindex = true; break;
        }
    }
    if ( post_password_required($post_id) ) { $is_noindex = true; }
    // F15 (audit-impl-r2): filter_var(FILTER_VALIDATE_URL) は IDN/multibyte URL を弾くため
    //   wp_http_validate_url を使用 (multibyte permalink 対応)。
    //   wp_http_validate_url は scheme + host のみ検証し、path には multibyte 文字を許容
    if ( empty( $title ) || empty( $url ) || ! wp_http_validate_url( $url ) || $is_noindex ) {
        return '';
    }

    if ( ! $full ) {
        // 概要版 (llms.txt)
        $line = '- [' . $title_md_safe . '](' . esc_url_raw( $url ) . ')';

        // A-2: 概要版に 1 行要約を追加
        if ( !empty($options['summary_inline_excerpt']) ) {
            $summary_length = isset($options['summary_excerpt_length']) ? intval($options['summary_excerpt_length']) : 70;
            /**
             * Filter the summary excerpt length for llms.txt (default 70).
             *
             * @since 1.0.7
             */
            $summary_length = (int) apply_filters( 'kashiwazaki_seo_llmstxt_summary_excerpt_length', $summary_length, $post, $options );
            $excerpt = kashiwazaki_seo_llmstxt_get_excerpt_for_post( $post, $summary_length );
            if ( $excerpt !== '' ) {
                $line .= ': ' . $excerpt;
            }
        }
        $output = $line . "\n";
    } else {
        // 詳細版 (llms-full.txt)
        $excerpt_length = (int) apply_filters('kashiwazaki_seo_llmstxt_excerpt_length', 150);
        $excerpt = kashiwazaki_seo_llmstxt_get_excerpt_for_post( $post, $excerpt_length );
        if ( $excerpt === '' ) { $excerpt = '(抜粋はありません)'; }

        $published_date_utc = get_post_time('c', true, $post_id);
        $modified_date_utc = get_post_modified_time('c', true, $post_id);

        $output  = "#### " . $title_md_safe . "\n";
        $output .= "- リンク: " . esc_url_raw( $url ) . "\n";
        $output .= "- 公開日時 (UTC): " . wp_strip_all_tags( $published_date_utc ?: 'N/A' ) . "\n";
        $output .= "- 最終更新日時 (UTC): " . wp_strip_all_tags( $modified_date_utc ?: 'N/A' ) . "\n";
        $output .= "- 抜粋: " . wp_strip_all_tags( $excerpt ) . "\n\n";
    }

    /**
     * Filter the rendered post entry.
     *
     * @since 1.0.7
     * @param string  $output  Markdown for the post entry.
     * @param WP_Post $post    Post object.
     * @param array   $options Plugin options.
     * @param bool    $full    true for llms-full.txt.
     */
    return apply_filters( 'kashiwazaki_seo_llmstxt_post_entry', $output, $post, $options, $full );
}

/**
 * C-3: 1 投稿タイプの全エントリをレンダ
 */
function kashiwazaki_seo_llmstxt_render_post_section( $post_type, $options, $full ) {
    $post_type_object = get_post_type_object( $post_type );
    if ( ! $post_type_object ) return '';
    $post_type_label = $post_type_object->labels->name ?? $post_type;

    // F6 (full-audit-r2): runtime 側でも 1〜10000 で clamp して既存の異常保存値や直接 wp_options 編集に対する DoS を防ぐ
    $posts_per_page = isset($options['posts_per_page']) ? max(1, min(10000, intval($options['posts_per_page']))) : 1000;

    // F4 (full-audit-r2): attachment は post_status='inherit' のため
    // 'publish' 固定だと UI で attachment を選択しても出力されない不整合があった
    $post_status = ( $post_type === 'attachment' ) ? [ 'inherit', 'publish' ] : 'publish';

    $args = [
        'post_type' => $post_type, 'post_status' => $post_status, 'posts_per_page' => $posts_per_page,
        'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true, 'no_found_rows' => true,
    ];
    $posts_query = new WP_Query( $args );

    if ( ! $posts_query->have_posts() ) {
        return '';
    }

    $output = "### " . wp_strip_all_tags( $post_type_label ) . " (`" . wp_strip_all_tags($post_type) . "`)\n";

    while ( $posts_query->have_posts() ) {
        $posts_query->the_post();
        $entry = kashiwazaki_seo_llmstxt_render_post_entry( get_post(), $options, $full );
        if ( $entry !== '' ) {
            $output .= $entry;
        }
    }
    wp_reset_postdata();
    if (!$full) { $output .= "\n"; }
    return $output;
}

/**
 * C-3: YAML フッターをレンダ
 */
function kashiwazaki_seo_llmstxt_render_yaml_footer( $yaml_settings ) {
    $default_options = kashiwazaki_seo_llmstxt_get_default_options();
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
        $temp_yaml_content .= "  requests-per-second: " . wp_strip_all_tags($rps) . "\n";
        $temp_yaml_content .= "  requests-per-minute: " . wp_strip_all_tags($rpm) . "\n\n";
    }
    $canonical_url_val = isset($yaml_settings['canonical-url']) ? $yaml_settings['canonical-url'] : '';
    if (!empty($canonical_url_val)) {
        $temp_yaml_content .= "## 正規ドメイン指定\n";
        $temp_yaml_content .= "canonical-url: " . esc_url_raw($canonical_url_val) . "\n\n";
    }
    if (isset($yaml_settings['retry-policy'])) {
        $temp_yaml_content .= "# エラー時のリトライポリシー\n";
        $temp_yaml_content .= "retry-policy:\n";
        $retry_enabled = !empty($yaml_settings['retry-policy']['retry']);
        $temp_yaml_content .= "  retry: " . ($retry_enabled ? 'true' : 'false') . "\n";
        if ($retry_enabled) {
            $max_retries = isset($yaml_settings['retry-policy']['max-retries']) && is_numeric($yaml_settings['retry-policy']['max-retries']) ? intval($yaml_settings['retry-policy']['max-retries']) : $default_options['yaml_settings']['retry-policy']['max-retries'];
            $wait_seconds = isset($yaml_settings['retry-policy']['wait-seconds']) && is_numeric($yaml_settings['retry-policy']['wait-seconds']) ? intval($yaml_settings['retry-policy']['wait-seconds']) : (isset($yaml_settings['retry-policy']['retry-after-seconds']) && is_numeric($yaml_settings['retry-policy']['retry-after-seconds']) ? intval($yaml_settings['retry-policy']['retry-after-seconds']) : $default_options['yaml_settings']['retry-policy']['wait-seconds']);
            $temp_yaml_content .= "  max-retries: " . wp_strip_all_tags($max_retries) . "\n";
            $temp_yaml_content .= "  wait-seconds: " . wp_strip_all_tags($wait_seconds) . "\n";
        }
        if (!empty($yaml_settings['retry-policy']['status-codes-no-retry']) && is_array($yaml_settings['retry-policy']['status-codes-no-retry'])) {
            // F16 (audit-r4): is_numeric は '5e2' '0xFF' を通すため preg_match で 3 桁 HTTP status のみ許可
            // F17 (audit-r4 deferred): trim を string に限定して PHP 8.1+ deprecation を未然に回避
            $valid_codes = array_filter($yaml_settings['retry-policy']['status-codes-no-retry'], function($code) {
                if ( ! is_string( $code ) && ! is_int( $code ) ) { return false; }
                return (bool) preg_match( '/^[1-5]\d{2}$/', trim( (string) $code ) );
            });
            if (!empty($valid_codes)) {
                $temp_yaml_content .= "  status-codes-no-retry:\n";
                foreach ($valid_codes as $code) { $temp_yaml_content .= "    - \"" . wp_strip_all_tags(trim((string) $code)) . "\"\n"; }
            }
        }
        $temp_yaml_content .= "\n";
    }
    if (!empty($yaml_settings['disallow']) && is_array($yaml_settings['disallow'])) {
        $valid_paths = array_filter($yaml_settings['disallow'], fn($path) => !empty(trim($path)) && strpos(trim($path), '/') === 0);
        if (!empty($valid_paths)) {
            $temp_yaml_content .= "# AIクロールから除外するパス\n";
            $temp_yaml_content .= "disallow:\n";
            foreach ($valid_paths as $path) { $temp_yaml_content .= "  - " . wp_strip_all_tags(trim($path)) . "\n"; }
            $temp_yaml_content .= "\n";
        }
    }
    if (!empty($yaml_settings['allowed-bots']) && is_array($yaml_settings['allowed-bots'])) {
        $valid_bots = array_filter($yaml_settings['allowed-bots'], fn($bot) => !empty(trim($bot)));
        if (!empty($valid_bots)) {
            $temp_yaml_content .= "# 許可するAIボット\n";
            $temp_yaml_content .= "allowed-bots:\n";
            foreach ($valid_bots as $bot) {
                $temp_yaml_content .= "  - " . wp_strip_all_tags(trim($bot)) . "\n";
            }
            $temp_yaml_content .= "\n";
        }
    }
    $sitemap_url_val = isset($yaml_settings['sitemap']) ? $yaml_settings['sitemap'] : '';
    if (!empty($sitemap_url_val)) {
        $temp_yaml_content .= "# サイトマップURL指定\n";
        $temp_yaml_content .= "sitemap: " . esc_url_raw($sitemap_url_val) . "\n\n";
    }

    if (empty(trim($temp_yaml_content))) {
        return '';
    }
    return "# LLMサイト設定（YAMLフッター）\n" . trim($temp_yaml_content);
}

/**
 * C-3: コピーライトフッターをレンダ
 */
function kashiwazaki_seo_llmstxt_render_copyright_footer() {
    $plugin_name = 'Kashiwazaki SEO LLMs.txt Generator';
    $author_name = 'Tsuyoshi Kashiwazaki';
    $author_uri = 'https://www.tsuyoshikashiwazaki.jp';
    $plugin_version = defined('KASHIWAZAKI_SEO_LLMSTXT_VERSION') ? KASHIWAZAKI_SEO_LLMSTXT_VERSION : '1.0.0';

    $copyright_info = "\n\n";
    $copyright_info .= "# このファイルについて\n\n";
    $copyright_info .= "- 生成プラグイン: " . sprintf( '%1$s%2$s', wp_strip_all_tags($plugin_name), ($plugin_version ? ' v' . wp_strip_all_tags($plugin_version) : '') ) . "\n";
    $copyright_info .= "- 制作者: " . sprintf( '%1$s (%2$s)', wp_strip_all_tags($author_name), esc_url_raw($author_uri) ) . "\n";
    $copyright_info .= "- ライセンス情報: この生成ツールは GPLv2 以降のライセンスで提供されています。リストされたコンテンツの著作権はサイト所有者に帰属します。\n";

    return $copyright_info;
}

/**
 * メインの content 生成関数 (C-3 リファクタ済)
 */
function kashiwazaki_seo_generate_llms_content( $full = false ) {
    $options = kashiwazaki_seo_llmstxt_get_options();

    $selected_types = isset( $options['selected_types'] ) && is_array( $options['selected_types'] ) ? array_map('sanitize_key', $options['selected_types']) : ['post','page'];
    $available_post_types = get_post_types(['public' => true, 'show_ui' => true], 'names');
    if (in_array('attachment', $selected_types) && post_type_exists('attachment')) {
        $attachment_object = get_post_type_object('attachment');
        if ($attachment_object && $attachment_object->show_ui) { $available_post_types['attachment'] = 'attachment'; }
    }
    $valid_selected_types = array_values(array_intersect($selected_types, array_keys($available_post_types)));

    if ( empty($valid_selected_types) ) {
        return "# " . get_bloginfo('name') . "\n\nエラー: 有効な投稿タイプがプラグイン設定で選択されていません。ファイルを生成するには、少なくとも1つの投稿タイプを選択してください。\n";
    }

    // A-3: 生成日時 = last_modified_gmt (RFC 7232 整合性)
    $last_modified_gmt = kashiwazaki_seo_llmstxt_get_last_modified( $valid_selected_types );

    // 1. header
    $output = kashiwazaki_seo_llmstxt_render_header( $options, $full, $last_modified_gmt );

    // 2. after_header (E-E-A-T 等の挿入点)
    $eeat_block = kashiwazaki_seo_llmstxt_render_eeat_block( $options );
    /**
     * Filter content inserted after the header section.
     * Used by E-E-A-T block (A-1) and any third-party hooks.
     *
     * @since 1.0.7
     * @param string $output  After-header Markdown (default: E-E-A-T block).
     * @param array  $options Plugin options.
     * @param bool   $full    true for llms-full.txt.
     */
    $after_header = apply_filters( 'kashiwazaki_seo_llmstxt_after_header', $eeat_block, $options, $full );
    if ( $after_header !== '' ) {
        $output .= $after_header;
    }

    // 3. ## コンテンツリスト
    // F6 (full-audit-r2): runtime 側でも 1〜10000 で clamp
    $posts_per_page = max(1, min(10000, intval($options['posts_per_page'])));
    $output .= "## コンテンツリスト\n";
    $output .= sprintf( "以下に、指定された投稿タイプごとのコンテンツリストを示します。（各タイプ最大 %d 件、新しい順）", $posts_per_page ) . "\n\n";

    $has_content = false;
    foreach( $valid_selected_types as $post_type ) {
        $section = kashiwazaki_seo_llmstxt_render_post_section( $post_type, $options, $full );
        if ( $section !== '' ) {
            $has_content = true;
            $output .= $section;
        }
    }

    if ( !$has_content ) {
        $output .= "指定された条件に一致する公開済みコンテンツは見つかりませんでした。\n";
    }

    // 4. YAML footer
    if ( !empty($options['enable_yaml_header']) ) {
        $yaml_output = kashiwazaki_seo_llmstxt_render_yaml_footer( $options['yaml_settings'] );
        if ( $yaml_output !== '' ) {
            $output .= "\n" . $yaml_output;
        }
    }

    // 5. copyright footer
    if ( !empty($options['show_copyright_footer']) ) {
        $output .= kashiwazaki_seo_llmstxt_render_copyright_footer();
    }

    /**
     * Filter the entire generated content (final post-processing).
     *
     * @since 1.0.7
     * @param string $output  The complete Markdown output.
     * @param array  $options Plugin options.
     * @param bool   $full    true for llms-full.txt.
     */
    return apply_filters( 'kashiwazaki_seo_llmstxt_after_render', $output, $options, $full );
}

function kashiwazaki_seo_llmstxt_add_query_vars( $vars ) {
    if ( ! in_array( 'kswz_llms_request_type', $vars ) ) {
        $vars[] = 'kswz_llms_request_type';
    }
    return $vars;
}

function kashiwazaki_seo_llmstxt_setup_rewrite_rules() {
    add_rewrite_rule( '^llms\.txt$', 'index.php?kswz_llms_request_type=summary', 'top' );
    add_rewrite_rule( '^llms-full\.txt$', 'index.php?kswz_llms_request_type=full', 'top' );
    add_filter( 'redirect_canonical', 'kashiwazaki_seo_llmstxt_disable_trailing_slash_redirect', 10, 2 );
}

function kashiwazaki_seo_llmstxt_disable_trailing_slash_redirect( $redirect_url, $requested_url ) {
    if ( preg_match( '/\/llms(-full)?\.txt$/i', $requested_url ) ) {
        return false;
    }
    return $redirect_url;
}

/**
 * A-3: ETag を RFC 7232 weak comparison で正規化
 */
function kashiwazaki_seo_llmstxt_normalize_etag( $etag ) {
    $etag = trim( $etag );
    if ( strpos( $etag, 'W/' ) === 0 ) {
        $etag = substr( $etag, 2 );
    }
    return trim( $etag, '"' );
}

/**
 * A-3: 対象 post_type の最終更新時刻 + option 更新時刻の最大値
 *      F13 (audit-r4): cache key に post_types のソート済みハッシュを含める (契約整合性保全)
 */
function kashiwazaki_seo_llmstxt_get_last_modified( $post_types ) {
    if ( ! is_array( $post_types ) || empty( $post_types ) ) {
        return 0;
    }
    $sorted_types = $post_types;
    sort( $sorted_types, SORT_STRING );
    $types_hash = substr( md5( implode( ',', $sorted_types ) ), 0, 8 );
    $cache_key = 'kashiwazaki_llms_lastmod_v2_' . $types_hash;
    $cached = get_transient( $cache_key );
    if ( false !== $cached ) {
        return (int) $cached;
    }

    global $wpdb;
    $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
    $sql = "SELECT UNIX_TIMESTAMP(MAX(post_modified_gmt)) FROM {$wpdb->posts}
            WHERE post_status='publish' AND post_type IN ($placeholders)";
    $post_max = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$post_types ) );

    $option_updated = (int) get_option( 'kashiwazaki_llms_lastmod_marker', 0 );

    $last_mod = max( $post_max, $option_updated );
    set_transient( $cache_key, $last_mod, 5 * MINUTE_IN_SECONDS );
    return $last_mod;
}

/**
 * A-3: transient invalidation (post 変更 / option 更新 / theme 切替)
 *      F13 (audit-r4): cache key が hash 付きになったため wpdb で wildcard 削除
 */
function kashiwazaki_seo_llmstxt_invalidate_lastmod_cache() {
    global $wpdb;
    // 後方互換 (旧 v1 key)
    delete_transient( 'kashiwazaki_llms_lastmod_v1' );
    // 新 v2_<hash> key を wildcard で削除
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_kashiwazaki_llms_lastmod_v2_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_kashiwazaki_llms_lastmod_v2_' ) . '%'
    ) );
    delete_transient( 'kashiwazaki_llms_txt_cache' );
    delete_transient( 'kashiwazaki_llms_full_txt_cache' );
}
add_action( 'save_post',     'kashiwazaki_seo_llmstxt_invalidate_lastmod_cache' );
add_action( 'deleted_post',  'kashiwazaki_seo_llmstxt_invalidate_lastmod_cache' );
add_action( 'trashed_post',  'kashiwazaki_seo_llmstxt_invalidate_lastmod_cache' );
add_action( 'untrashed_post','kashiwazaki_seo_llmstxt_invalidate_lastmod_cache' );
add_action( 'switch_theme',  'kashiwazaki_seo_llmstxt_invalidate_lastmod_cache' );

/**
 * A-3: option 更新時にマーカーを保存 + cache invalidation
 *      別 option key (kashiwazaki_llms_lastmod_marker) を使うことで循環を防止
 */
function kashiwazaki_seo_llmstxt_on_option_update() {
    update_option( 'kashiwazaki_llms_lastmod_marker', time(), false ); // autoload=false
    kashiwazaki_seo_llmstxt_invalidate_lastmod_cache();
}
add_action( 'update_option_' . KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, 'kashiwazaki_seo_llmstxt_on_option_update' );
add_action( 'add_option_'    . KASHIWAZAKI_SEO_LLMSTXT_OPTION_KEY, 'kashiwazaki_seo_llmstxt_on_option_update' );

function kashiwazaki_seo_llmstxt_handle_dynamic_output() {
    global $wp_query;

    $request_type = get_query_var( 'kswz_llms_request_type' );

    // F7 (audit-r4): REQUEST_URI を wp_unslash + path のみで完全一致判定
    //   旧実装は preg_match で `/foo/bar/llms.txt` のような任意 path も拾う問題があった
    if ( empty( $request_type ) && isset( $_SERVER['REQUEST_URI'] ) ) {
        $request_uri_raw = wp_unslash( $_SERVER['REQUEST_URI'] );
        $request_uri = parse_url( $request_uri_raw, PHP_URL_PATH );
        if ( is_string( $request_uri ) ) {
            // ストアのトップディレクトリの場合 / で正規化
            $home_path = parse_url( home_url('/'), PHP_URL_PATH );
            $home_path = is_string( $home_path ) ? $home_path : '/';
            if ( $home_path === '' ) { $home_path = '/'; }
            // home_path 直下の llms.txt / llms-full.txt のみ受理 (case-insensitive)
            $expected_summary = rtrim( $home_path, '/' ) . '/llms.txt';
            $expected_full    = rtrim( $home_path, '/' ) . '/llms-full.txt';
            $normalized = rtrim( $request_uri, '/' );
            if ( strcasecmp( $normalized, $expected_summary ) === 0 ) {
                $request_type = 'summary';
            } elseif ( strcasecmp( $normalized, $expected_full ) === 0 ) {
                $request_type = 'full';
            }
        }
    }

    if ( ! $request_type ) {
        return;
    }

    $is_full = ( $request_type === 'full' );

    // ファイル生成が有効かチェック
    $option_name = $is_full ? 'kashiwazaki_llms_full_txt_enabled' : 'kashiwazaki_llms_txt_enabled';
    $is_enabled = get_option($option_name, true);

    if (!$is_enabled) {
        // F11 (full-audit-r2): 無効化された endpoint では buffer 制御せずに WordPress に
        // 通常の 404 ハンドリングを委譲する (他プラグインの buffer を破壊しないため)
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }

    // F9/F10 (audit-impl-r2): endpoint 確定 + enabled 確認後にのみ output buffer を制御
    //   旧実装は handle_dynamic_output 冒頭で無条件 ob_clean/ob_start していたため、
    //   非 endpoint リクエスト (通常ページ) でも他プラグインの buffer を破壊していた
    // F11 (full-audit-r2): disabled endpoint でも buffer 破壊する不整合を解消
    while ( ob_get_level() > 0 ) {
        @ob_end_clean();
    }
    ob_start();

    // キャッシュ設定
    $options = kashiwazaki_seo_llmstxt_get_options();
    $cache_duration = isset($options['cache_duration']) ? (int)$options['cache_duration'] : 0;
    $cache_key = $is_full ? 'kashiwazaki_llms_full_txt_cache' : 'kashiwazaki_llms_txt_cache';

    $content = false;
    $cache_hit = false;

    if ($cache_duration !== 0) {
        $cached_content = get_transient($cache_key);
        if ($cached_content !== false) {
            $content = $cached_content;
            $cache_hit = true;
        }
    }

    if ( $content === false ) {
        $content = kashiwazaki_seo_generate_llms_content( $is_full );
        if ($cache_duration !== 0) {
            if ($cache_duration === -1) {
                set_transient($cache_key, $content, YEAR_IN_SECONDS);
            } else {
                set_transient($cache_key, $content, $cache_duration);
            }
        }
    }

    // F9/F10 (audit-r4): 配信 body と ETag/Content-Length 計算対象を完全一致させる
    //   旧実装は出力時 wp_strip_all_tags() が内部 trim() で末尾改行を削除していたため
    //   ETag = hash(trim 前) / body = trim 後 のミスマッチが発生 (RFC 7232 違反)
    //   content 生成時点で既に sanitize 済のため、出力時の strip は不要
    $content_with_bom = "\xEF\xBB\xBF" . $content;

    // A-3: ETag 計算 (実配信 body と同じ bytes に対して計算)
    $etag_seed = apply_filters( 'kashiwazaki_seo_llmstxt_etag_seed', '' );
    $etag_raw = substr( hash( 'sha256', $etag_seed . $content_with_bom ), 0, 32 );
    $etag = '"' . $etag_raw . '"';

    // A-3: Last-Modified 計算
    $selected_types = isset($options['selected_types']) && is_array($options['selected_types']) ? array_map('sanitize_key', $options['selected_types']) : ['post','page'];
    $last_modified_gmt = kashiwazaki_seo_llmstxt_get_last_modified( $selected_types );
    $last_modified_http = $last_modified_gmt > 0 ? gmdate( 'D, d M Y H:i:s', $last_modified_gmt ) . ' GMT' : '';

    // A-3: Conditional GET 評価
    $client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) : '';
    $client_ims  = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? trim( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) : '';

    $matched = false;
    if ( $client_etag !== '' ) {
        // INM 評価優先 (RFC 7232 §3.3)
        if ( $client_etag === '*' ) {
            $matched = true;
        } else {
            $server_normalized = kashiwazaki_seo_llmstxt_normalize_etag( $etag );
            $client_etags = array_map( 'trim', explode( ',', $client_etag ) );
            foreach ( $client_etags as $cet ) {
                if ( $cet === '' ) continue;
                if ( kashiwazaki_seo_llmstxt_normalize_etag( $cet ) === $server_normalized ) {
                    $matched = true;
                    break;
                }
            }
        }
    } elseif ( $client_ims !== '' && $last_modified_gmt > 0 ) {
        $ims_ts = strtotime( $client_ims );
        if ( false !== $ims_ts && $ims_ts >= $last_modified_gmt ) {
            $matched = true;
        }
    }

    $cache_control = apply_filters( 'kashiwazaki_seo_llmstxt_cache_control_header', 'public, max-age=0, must-revalidate' );

    if ( $matched ) {
        if ( ob_get_length() !== false ) { @ob_clean(); } // 304 body は空
        status_header( 304 );
        header( 'ETag: ' . $etag );
        if ( $last_modified_http !== '' ) {
            header( 'Last-Modified: ' . $last_modified_http );
        }
        header( 'Cache-Control: ' . $cache_control );
        header( 'Vary: Accept-Encoding' );
        while ( ob_get_level() > 0 ) { @ob_end_flush(); }
        exit;
    }

    // 通常配信
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: DENY' );
    header( 'ETag: ' . $etag );
    if ( $last_modified_http !== '' ) {
        header( 'Last-Modified: ' . $last_modified_http );
    }
    header( 'Cache-Control: ' . $cache_control );
    header( 'Vary: Accept-Encoding' );
    header( 'X-Kashiwazaki-Cache: ' . ( $cache_hit ? 'HIT' : 'MISS' ) );
    header( 'Content-Length: ' . strlen( $content_with_bom ) );

    // HEAD リクエスト時は body を出さずに終了
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'HEAD' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
        if ( ob_get_length() !== false ) { @ob_clean(); }
        while ( ob_get_level() > 0 ) { @ob_end_flush(); }
        exit;
    }

    // F9/F10 (audit-r4): output buffer を完全クリアしてから単一 BOM 付き body を出力
    //   $content は既に sanitize 済 (wp_strip_all_tags + 制御文字除去)
    if ( ob_get_length() !== false ) { @ob_clean(); }
    echo $content_with_bom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    while ( ob_get_level() > 0 ) { @ob_end_flush(); }
    exit;
}
?>
