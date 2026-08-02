<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper: count non-empty E-E-A-T fields for <summary> count display
$eeat_data = isset($options['eeat_settings']) && is_array($options['eeat_settings']) ? $options['eeat_settings'] : [];
$eeat_keys_optional = ['organization_name','organization_url','organization_address','organization_phone','organization_email','representative_name','representative_title','expertise_areas','credentials','editorial_policy_url','editorial_policy_text'];
$eeat_filled_count = 0;
foreach ($eeat_keys_optional as $k) {
    if (!empty($eeat_data[$k])) { $eeat_filled_count++; }
}
$eeat_total_count = count($eeat_keys_optional);

// Cache status helper
$cache_seconds_map = [
    '0' => 'キャッシュなし (リアルタイム生成)',
    '10800' => '3 時間',
    '86400' => '24 時間 (1 日)',
    '259200' => '3 日',
    '604800' => '1 週間',
    '-1' => '永久 (手動クリアまで)',
];
$cache_label = isset($cache_seconds_map[(string)$cache_duration]) ? $cache_seconds_map[(string)$cache_duration] : ('カスタム ('.intval($cache_duration).' 秒)');

// Cache remaining time (transient timeout, fallback to TTL)
$cache_remaining_text = '';
if ((string)$cache_duration !== '0') {
    $transient_keys = ['kashiwazaki_llms_txt_cache','kashiwazaki_llms_full_txt_cache'];
    $remaining_secs = null;
    foreach ($transient_keys as $tk) {
        $timeout = get_option('_transient_timeout_'.$tk);
        if (is_numeric($timeout)) {
            $r = intval($timeout) - time();
            if ($r > 0 && ($remaining_secs === null || $r < $remaining_secs)) {
                $remaining_secs = $r;
            }
        }
    }
    if ($remaining_secs !== null) {
        if ($remaining_secs > 86400) {
            $cache_remaining_text = sprintf('残り 約 %d 日', floor($remaining_secs / 86400));
        } elseif ($remaining_secs > 3600) {
            $cache_remaining_text = sprintf('残り 約 %d 時間', floor($remaining_secs / 3600));
        } else {
            $cache_remaining_text = sprintf('残り 約 %d 分', max(1, floor($remaining_secs / 60)));
        }
    } elseif ((string)$cache_duration === '-1') {
        $cache_remaining_text = '手動クリアまで保持';
    } else {
        $cache_remaining_text = '次回アクセス時に再生成';
    }
}

// Recommended cache marks
$recommended_cache = ['86400','604800'];

// Bot/disallow presets
$bot_presets = [
    'GPTBot' => 'GPTBot (OpenAI)',
    'ChatGPT-User' => 'ChatGPT-User',
    'OAI-SearchBot' => 'OAI-SearchBot',
    'ClaudeBot' => 'ClaudeBot (Anthropic)',
    'anthropic-ai' => 'anthropic-ai',
    'PerplexityBot' => 'PerplexityBot',
    'Google-Extended' => 'Google-Extended',
    'Applebot-Extended' => 'Applebot-Extended',
    'Bytespider' => 'Bytespider (ByteDance)',
    'CCBot' => 'CCBot (Common Crawl)',
];
$disallow_presets = ['/wp-admin/','/wp-login.php','/wp-json/','/?s=','/feed/','/cgi-bin/'];
?>
<div class="wrap kashiwazaki-seo-llmstxt-wrap kw-llms-admin">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php if (isset($plugin_version) && $plugin_version): ?>
        <p class="kashiwazaki-seo-version-info">バージョン <?php echo esc_html($plugin_version); ?></p>
    <?php endif; ?>

    <div id="kw-llms-notice-area" role="status" aria-live="polite"></div>
    <?php if (isset($message)) echo wp_kses_post( $message ); ?>

    <h2 class="nav-tab-wrapper kw-llms-tabs" role="tablist">
        <a href="#kw-tab-dashboard" class="nav-tab nav-tab-active" id="kw-tab-link-dashboard" role="tab" aria-controls="kw-tab-dashboard" aria-selected="true" data-tab="dashboard">
            <span class="dashicons dashicons-dashboard" aria-hidden="true"></span> ダッシュボード
        </a>
        <a href="#kw-tab-content" class="nav-tab" id="kw-tab-link-content" role="tab" aria-controls="kw-tab-content" aria-selected="false" data-tab="content">
            <span class="dashicons dashicons-edit" aria-hidden="true"></span> コンテンツ設定
        </a>
        <a href="#kw-tab-eeat" class="nav-tab" id="kw-tab-link-eeat" role="tab" aria-controls="kw-tab-eeat" aria-selected="false" data-tab="eeat">
            <span class="dashicons dashicons-businessperson" aria-hidden="true"></span> 運営者情報
        </a>
        <a href="#kw-tab-crawler" class="nav-tab" id="kw-tab-link-crawler" role="tab" aria-controls="kw-tab-crawler" aria-selected="false" data-tab="crawler">
            <span class="dashicons dashicons-admin-network" aria-hidden="true"></span> AI クローラー設定
        </a>
        <a href="#kw-tab-advanced" class="nav-tab" id="kw-tab-link-advanced" role="tab" aria-controls="kw-tab-advanced" aria-selected="false" data-tab="advanced">
            <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span> 高度な設定
        </a>
    </h2>

    <form method="post" id="kw-llms-settings-form">
        <?php wp_nonce_field( $save_nonce_action, $save_nonce_name ); ?>

        <!-- ============ TAB 1: ダッシュボード ============ -->
        <section id="kw-tab-dashboard" class="kw-tab-pane kw-tab-pane-active" role="tabpanel" aria-labelledby="kw-tab-link-dashboard" tabindex="0">
            <p class="kw-tab-intro">このサイトの llms.txt / llms-full.txt の生成状況とキャッシュ状態の概要です。各ファイルの ON/OFF はカードのボタンから即座に切り替えられます。</p>

            <div class="kw-dashboard-grid">

                <!-- llms.txt card -->
                <div class="kw-card kw-card-status">
                    <div class="kw-card-header">
                        <h3 class="kw-card-title">llms.txt</h3>
                        <span class="kw-status-badge kw-status-<?php echo $llms_txt_enabled ? 'on' : 'off'; ?>" data-file-type="llms-txt" aria-live="polite">
                            <?php echo $llms_txt_enabled ? '生成中' : '無効'; ?>
                        </span>
                    </div>
                    <p class="kw-card-desc">概要版: タイトルとリンクの一覧。AI クローラーがサイト全体を素早く把握するための入口ファイル。</p>
                    <p class="kw-card-url">
                        <span class="kw-url-label">URL:</span>
                        <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener" class="kw-url-link"><?php echo esc_html( home_url( '/llms.txt' ) ); ?></a>
                    </p>
                    <div class="kw-card-actions">
                        <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener" class="button">プレビュー</a>
                        <button type="button" class="button kw-toggle-file <?php echo $llms_txt_enabled ? 'button-secondary' : 'button-primary'; ?>" data-file-type="llms-txt" data-action="<?php echo $llms_txt_enabled ? 'disable' : 'enable'; ?>">
                            <?php echo $llms_txt_enabled ? '無効化' : '有効化'; ?>
                        </button>
                    </div>
                </div>

                <!-- llms-full.txt card -->
                <div class="kw-card kw-card-status">
                    <div class="kw-card-header">
                        <h3 class="kw-card-title">llms-full.txt</h3>
                        <span class="kw-status-badge kw-status-<?php echo $llms_full_txt_enabled ? 'on' : 'off'; ?>" data-file-type="llms-full-txt" aria-live="polite">
                            <?php echo $llms_full_txt_enabled ? '生成中' : '無効'; ?>
                        </span>
                    </div>
                    <p class="kw-card-desc">詳細版: 概要 + 抜粋 + 公開日 + 更新日を含む。AI が引用根拠とするための情報量を持たせたい場合に使用。</p>
                    <p class="kw-card-url">
                        <span class="kw-url-label">URL:</span>
                        <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" rel="noopener" class="kw-url-link"><?php echo esc_html( home_url( '/llms-full.txt' ) ); ?></a>
                    </p>
                    <div class="kw-card-actions">
                        <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" rel="noopener" class="button">プレビュー</a>
                        <button type="button" class="button kw-toggle-file <?php echo $llms_full_txt_enabled ? 'button-secondary' : 'button-primary'; ?>" data-file-type="llms-full-txt" data-action="<?php echo $llms_full_txt_enabled ? 'disable' : 'enable'; ?>">
                            <?php echo $llms_full_txt_enabled ? '無効化' : '有効化'; ?>
                        </button>
                    </div>
                </div>

                <!-- Cache status card -->
                <div class="kw-card kw-card-cache">
                    <div class="kw-card-header">
                        <h3 class="kw-card-title">キャッシュ</h3>
                        <span class="kw-status-badge kw-status-<?php echo (string)$cache_duration === '0' ? 'off' : 'on'; ?>">
                            <?php echo (string)$cache_duration === '0' ? 'なし' : esc_html($cache_label); ?>
                        </span>
                    </div>
                    <?php if ((string)$cache_duration !== '0'): ?>
                        <p class="kw-card-desc">現在のキャッシュ: <strong><?php echo esc_html($cache_label); ?></strong><?php if ($cache_remaining_text): ?> — <?php echo esc_html($cache_remaining_text); ?><?php endif; ?>。</p>
                    <?php else: ?>
                        <p class="kw-card-desc">毎回リアルタイム生成。負荷が気になる場合は「高度な設定」タブで TTL を設定してください。</p>
                    <?php endif; ?>
                    <div class="kw-card-actions">
                        <button type="button" class="button kw-clear-cache-btn" id="kw-clear-cache-btn">キャッシュをクリア</button>
                        <a href="#kw-tab-advanced" class="button button-secondary kw-tab-jump" data-target-tab="advanced">キャッシュ設定へ</a>
                    </div>
                </div>

            </div>

            <div class="kw-card kw-card-info">
                <h3 class="kw-card-title">概要版と詳細版の違い</h3>
                <ul class="kw-diff-list">
                    <li><strong>llms.txt (概要版)</strong>: <code>- [タイトル](URL)</code> 形式のリストのみ。サイト構造の入口。任意で 1 行要約付与可能 (「コンテンツ設定」タブ)。</li>
                    <li><strong>llms-full.txt (詳細版)</strong>: 上記 + 公開日 / 更新日 / 抜粋 を含む。AI が引用根拠を取りやすい形式。</li>
                </ul>
            </div>
        </section>

        <!-- ============ TAB 2: コンテンツ設定 ============ -->
        <section id="kw-tab-content" class="kw-tab-pane" role="tabpanel" aria-labelledby="kw-tab-link-content" tabindex="0" hidden>
            <p class="kw-tab-intro">llms.txt / llms-full.txt に <strong>何を含めるか</strong> を決める設定です。</p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="posts_per_page">投稿タイプごとの最大件数</label></th>
                        <td>
                            <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_attr( $posts_per_page ); ?>" min="1" max="10000" step="1" class="small-text" aria-describedby="posts_per_page-desc" />
                            <p class="description" id="posts_per_page-desc">選択した各投稿タイプから新しい順で取得する最大件数。1〜10000 の範囲。多すぎるとファイルが肥大化します (デフォルト: <?php echo esc_html($default_options['posts_per_page']); ?>)。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">含める投稿タイプ</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">含める投稿タイプ</legend>
                                <div class="kw-post-type-grid">
                                    <?php foreach ( $all_types as $type_obj ):
                                        if (!is_object($type_obj) || !isset($type_obj->name) || !isset($type_obj->labels) || !isset($type_obj->labels->name)) continue;
                                        $type = $type_obj->name;
                                        $type_label = $type_obj->labels->name;
                                        $checked = in_array( $type, $current_selected_types, true );
                                        $cb_id = 'pt_' . sanitize_html_class($type);
                                    ?>
                                        <label class="kw-post-type-item <?php echo $checked ? 'is-checked' : ''; ?>" for="<?php echo esc_attr($cb_id); ?>">
                                            <input type="checkbox" name="post_types[]" id="<?php echo esc_attr($cb_id); ?>" value="<?php echo esc_attr( $type ); ?>" <?php checked($checked); ?> />
                                            <span class="kw-post-type-name"><?php echo esc_html( $type_label ); ?></span>
                                            <span class="kw-post-type-slug"><code><?php echo esc_html($type); ?></code></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">少なくとも 1 つ選択してください。「投稿」「固定ページ」がデフォルト選択です。</p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">概要版に 1 行要約を付与</th>
                        <td>
                            <fieldset>
                                <label for="summary_inline_excerpt">
                                    <input type="checkbox" name="summary_inline_excerpt" id="summary_inline_excerpt" value="1" <?php checked( !empty($options['summary_inline_excerpt']) ); ?> aria-describedby="summary_inline_excerpt-desc" />
                                    llms.txt の各リンク行末に 1 行要約を追加する
                                </label>
                                <p class="description" id="summary_inline_excerpt-desc">出力例: <code>- [タイトル](URL): 1 行要約</code>。AI クローラーが引用しやすい URL を判別する補助情報になります。</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="summary_excerpt_length">要約文字数</label></th>
                        <td>
                            <input type="number" name="summary_excerpt_length" id="summary_excerpt_length" value="<?php echo esc_attr( isset($options['summary_excerpt_length']) ? $options['summary_excerpt_length'] : 70 ); ?>" min="20" max="200" step="1" class="small-text" aria-describedby="summary_excerpt_length-desc" />
                            <p class="description" id="summary_excerpt_length-desc">20〜200 文字 (推奨 50〜100、デフォルト 70)。</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">制作者情報フッター</th>
                        <td>
                            <fieldset>
                                <label for="show_copyright_footer">
                                    <input type="checkbox" name="show_copyright_footer" id="show_copyright_footer" value="1" <?php checked( $show_copyright_footer ); ?> aria-describedby="show_copyright_footer-desc" />
                                    生成ファイルの末尾にプラグイン制作者情報を表示する
                                </label>
                                <p class="description" id="show_copyright_footer-desc">このプラグインの開発者情報をファイル末尾に含めます (デフォルト: ON)。</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 class="kw-section-h3">追加セクション</h3>
            <p class="description">
                プラグインが自動生成しない情報を、任意の Markdown として生成ファイルに追記できます。
                MCP エンドポイント・API・利用条件・問い合わせ方針など、投稿一覧には現れないが
                AI に伝えたい情報の記載に使えます。
            </p>

            <div class="notice notice-warning inline kw-public-warning">
                <p><strong>公開出力に含まれます:</strong> 入力内容は <code>llms.txt</code> および <code>llms-full.txt</code> として誰でも閲覧可能になります。</p>
            </div>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">追加セクション</th>
                        <td>
                            <fieldset>
                                <label for="enable_custom_section">
                                    <input type="checkbox" name="enable_custom_section" id="enable_custom_section" value="1" <?php checked( ! empty( $options['enable_custom_section'] ) ); ?> aria-describedby="enable_custom_section-desc" />
                                    追加セクションを出力する
                                </label>
                                <p class="description" id="enable_custom_section-desc">OFF のとき、または本文が空のときは何も出力されません (デフォルト: OFF)。</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="custom_section_position">挿入位置</label></th>
                        <td>
                            <select name="custom_section_position" id="custom_section_position" aria-describedby="custom_section_position-desc">
                                <option value="after_header" <?php selected( ( $options['custom_section_position'] ?? 'after_header' ), 'after_header' ); ?>>概要の直後 (コンテンツリストより前)</option>
                                <option value="before_footer" <?php selected( ( $options['custom_section_position'] ?? 'after_header' ), 'before_footer' ); ?>>コンテンツリストの後 (フッターより前)</option>
                            </select>
                            <p class="description" id="custom_section_position-desc">重要な情報ほど前に置くと AI に読まれやすくなります。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="custom_section_text">本文 (Markdown)</label></th>
                        <td>
                            <textarea name="custom_section_text" id="custom_section_text" rows="10" class="large-text code" aria-describedby="custom_section_text-desc" placeholder="## お問い合わせ&#10;&#10;- メール: info@example.com&#10;- フォーム: https://example.com/contact/"><?php echo esc_textarea( $options['custom_section_text'] ?? '' ); ?></textarea>
                            <p class="description" id="custom_section_text-desc">
                                最大 5000 文字。見出しは <code>##</code> から始めると他のセクションと揃います。
                                HTML タグは保存時に除去されます (出力は text/plain の Markdown のため)。
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- ============ TAB 3: 運営者情報 ============ -->
        <section id="kw-tab-eeat" class="kw-tab-pane" role="tabpanel" aria-labelledby="kw-tab-link-eeat" tabindex="0" hidden>
            <p class="kw-tab-intro">生成ファイルの冒頭に <code>## About</code> ブロックを出力し、運営者・編集責任者・専門領域・編集方針を AI に伝えます。AI が「誰の知識として引用すべきか」を判断する根拠になります。</p>

            <div class="notice notice-warning inline kw-public-warning">
                <p><strong>公開出力に含まれます:</strong> 入力した内容は <code>llms.txt</code> および <code>llms-full.txt</code> として誰でも閲覧可能になります。電話番号・メールアドレスは AI クローラーやスクレイパーから露出するため、公開してよい情報のみ入力してください。</p>
            </div>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">運営者情報ブロック</th>
                        <td>
                            <fieldset>
                                <label for="enable_eeat_block">
                                    <input type="checkbox" name="enable_eeat_block" id="enable_eeat_block" value="1" <?php checked( !empty($options['enable_eeat_block']) ); ?> aria-describedby="enable_eeat_block-desc" />
                                    <code>## About</code> ブロックを有効にする
                                </label>
                                <p class="description" id="enable_eeat_block-desc">ON にすると、下記項目のうち入力済みのものが出力されます。空項目はスキップされます。</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <details class="kw-eeat-details" id="kw-eeat-details" <?php echo (!empty($options['enable_eeat_block']) && $eeat_filled_count > 0) ? 'open' : ''; ?>>
                <summary class="kw-eeat-summary">
                    <span class="kw-eeat-summary-label">運営者の詳細情報</span>
                    <span class="kw-eeat-summary-count" id="kw-eeat-summary-count" data-filled="<?php echo intval($eeat_filled_count); ?>" data-total="<?php echo intval($eeat_total_count); ?>">
                        <?php echo intval($eeat_filled_count); ?> / <?php echo intval($eeat_total_count); ?> 項目入力済
                    </span>
                </summary>

                <table class="form-table kw-eeat-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="eeat_organization_name">運営者名</label></th>
                            <td><input type="text" name="eeat_settings[organization_name]" id="eeat_organization_name" value="<?php echo esc_attr( $eeat_data['organization_name'] ?? '' ); ?>" class="regular-text kw-eeat-field" placeholder="個人名 / 屋号 / 法人名 / 団体名" aria-describedby="eeat_organization_name-desc" />
                            <p class="description" id="eeat_organization_name-desc">個人・個人事業主・法人・団体いずれも可。出力には <code>- 運営者: ...</code> として表示。</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_organization_url">運営者 URL</label></th>
                            <td><input type="url" name="eeat_settings[organization_url]" id="eeat_organization_url" value="<?php echo esc_attr( $eeat_data['organization_url'] ?? '' ); ?>" class="regular-text kw-eeat-field" placeholder="https://example.com/" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_organization_address">所在地</label></th>
                            <td><input type="text" name="eeat_settings[organization_address]" id="eeat_organization_address" value="<?php echo esc_attr( $eeat_data['organization_address'] ?? '' ); ?>" class="regular-text kw-eeat-field" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_organization_phone">電話</label></th>
                            <td><input type="text" name="eeat_settings[organization_phone]" id="eeat_organization_phone" value="<?php echo esc_attr( $eeat_data['organization_phone'] ?? '' ); ?>" class="regular-text kw-eeat-field" aria-describedby="eeat_organization_phone-desc" />
                            <p class="description" id="eeat_organization_phone-desc">数字 / + / - / ( ) / 空白のみ。<strong class="kw-public-mark">公開出力に露出します</strong>。</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_organization_email">E-mail</label></th>
                            <td><input type="email" name="eeat_settings[organization_email]" id="eeat_organization_email" value="<?php echo esc_attr( $eeat_data['organization_email'] ?? '' ); ?>" class="regular-text kw-eeat-field" aria-describedby="eeat_organization_email-desc" />
                            <p class="description" id="eeat_organization_email-desc"><strong class="kw-public-mark">公開出力にそのまま露出します</strong>。Web Archive 等にキャッシュされ削除困難になるため、spam リスクを許容できる場合のみ入力してください。</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_representative_name">編集責任者名</label></th>
                            <td><input type="text" name="eeat_settings[representative_name]" id="eeat_representative_name" value="<?php echo esc_attr( $eeat_data['representative_name'] ?? '' ); ?>" class="regular-text kw-eeat-field" aria-describedby="eeat_representative_name-desc" />
                            <p class="description" id="eeat_representative_name-desc">サイト運営に責任を持つ人。個人運営の場合は運営者と同名で構いません。出力には <code>- 編集責任者: ...</code> として表示。</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_representative_title">編集責任者の肩書</label></th>
                            <td><input type="text" name="eeat_settings[representative_title]" id="eeat_representative_title" value="<?php echo esc_attr( $eeat_data['representative_title'] ?? '' ); ?>" class="regular-text kw-eeat-field" placeholder="代表 / 編集長 / 主宰 / 代表取締役 等" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_expertise_areas">専門領域</label></th>
                            <td><textarea name="eeat_settings[expertise_areas]" id="eeat_expertise_areas" rows="4" class="large-text kw-eeat-field" aria-describedby="eeat_expertise_areas-desc"><?php echo esc_textarea( $eeat_data['expertise_areas'] ?? '' ); ?></textarea>
                            <p class="description" id="eeat_expertise_areas-desc">改行区切りで列挙 (最大 20 行)。例: SEO / 検索最適化</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_credentials">資格・認定・経歴</label></th>
                            <td><textarea name="eeat_settings[credentials]" id="eeat_credentials" rows="4" class="large-text kw-eeat-field" aria-describedby="eeat_credentials-desc"><?php echo esc_textarea( $eeat_data['credentials'] ?? '' ); ?></textarea>
                            <p class="description" id="eeat_credentials-desc">改行区切りで列挙 (最大 20 行)。例: 特許 JPxxxxxxx / Google Analytics 認定資格 / 〇〇社 SEO 担当 5 年。出力には <code>- 資格・経歴:</code> として表示。</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_editorial_policy_url">編集方針ページ URL</label></th>
                            <td><input type="url" name="eeat_settings[editorial_policy_url]" id="eeat_editorial_policy_url" value="<?php echo esc_attr( $eeat_data['editorial_policy_url'] ?? '' ); ?>" class="regular-text kw-eeat-field" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="eeat_editorial_policy_text">編集方針サマリ</label></th>
                            <td><textarea name="eeat_settings[editorial_policy_text]" id="eeat_editorial_policy_text" rows="3" maxlength="200" class="large-text kw-eeat-field" aria-describedby="eeat_editorial_policy_text-desc"><?php echo esc_textarea( $eeat_data['editorial_policy_text'] ?? '' ); ?></textarea>
                            <p class="description" id="eeat_editorial_policy_text-desc">200 文字以内 (全角・半角問わず)。空欄の場合は出力スキップ。</p></td>
                        </tr>
                    </tbody>
                </table>
            </details>
        </section>

        <!-- ============ TAB 4: AI クローラー設定 ============ -->
        <section id="kw-tab-crawler" class="kw-tab-pane" role="tabpanel" aria-labelledby="kw-tab-link-crawler" tabindex="0" hidden>
            <p class="kw-tab-intro">AI クローラーへの動作指示 (YAML フッター) を生成ファイル末尾に出力します。許可するボット・アクセス頻度・除外パスなどを宣言できます。</p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">YAML フッター</th>
                        <td>
                            <fieldset>
                                <label for="enable_yaml_header">
                                    <input type="checkbox" name="enable_yaml_header" id="enable_yaml_header" value="1" <?php checked( $enable_yaml_footer ); ?> aria-describedby="enable_yaml_header-desc" />
                                    YAML フッター (AI クローラー指示) を有効にする
                                </label>
                                <p class="description" id="enable_yaml_header-desc">ON にすると、下記の詳細項目が生成ファイル末尾に YAML 形式で出力されます。</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="kw-yaml-details-wrapper" <?php echo (!$enable_yaml_footer) ? 'hidden' : ''; ?>>

                <details class="kw-yaml-group" open>
                    <summary class="kw-yaml-group-summary">グループ A — ライセンス / アクセス頻度</summary>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">コンテンツライセンス</th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">コンテンツライセンス</legend>
                                        <label><input type="checkbox" name="yaml_settings[license][allow-ai-training]" value="1" <?php checked( $yaml_settings['license']['allow-ai-training'] ); ?> /> AI 学習利用を許可する</label>
                                        <label><input type="checkbox" name="yaml_settings[license][allow-ai-commercial-use]" value="1" <?php checked( $yaml_settings['license']['allow-ai-commercial-use'] ); ?> /> AI 経由の商用利用を許可する</label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">アクセス頻度制限</th>
                                <td>
                                    <fieldset>
                                        <label><input type="checkbox" name="yaml_settings[rate-limit][enabled]" id="rate_limit_enabled" value="1" <?php checked( $yaml_settings['rate-limit']['enabled'] ); ?> /> アクセス頻度制限を有効にする</label>
                                    </fieldset>
                                    <div class="rate-limit-details kw-conditional" <?php if (!$yaml_settings['rate-limit']['enabled']) echo 'hidden'; ?>>
                                        <p style="margin-top:10px;">
                                            <label for="rate_limit_rps">秒間:</label> <input type="number" id="rate_limit_rps" name="yaml_settings[rate-limit][requests-per-second]" value="<?php echo esc_attr( $yaml_settings['rate-limit']['requests-per-second'] ); ?>" min="0" step="0.1" class="small-text" /> req/sec<br>
                                            <label for="rate_limit_rpm">分間:</label> <input type="number" id="rate_limit_rpm" name="yaml_settings[rate-limit][requests-per-minute]" value="<?php echo esc_attr( $yaml_settings['rate-limit']['requests-per-minute'] ); ?>" min="0" step="1" class="small-text" /> req/min
                                        </p>
                                        <p class="description">秒間および分間の最大リクエスト数。</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </details>

                <details class="kw-yaml-group">
                    <summary class="kw-yaml-group-summary">グループ B — リトライ / 除外パス</summary>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">エラー時のリトライ</th>
                                <td>
                                    <fieldset>
                                        <label><input type="checkbox" name="yaml_settings[retry-policy][retry]" id="retry_enabled" value="1" <?php checked( $yaml_settings['retry-policy']['retry'] ); ?> /> リトライを許可する</label>
                                    </fieldset>
                                    <div class="retry-policy-details kw-conditional" <?php if (!$yaml_settings['retry-policy']['retry']) echo 'hidden'; ?>>
                                        <p style="margin-top:10px;">
                                            <label for="max_retries">最大リトライ回数:</label> <input type="number" id="max_retries" name="yaml_settings[retry-policy][max-retries]" value="<?php echo esc_attr( $yaml_settings['retry-policy']['max-retries'] ); ?>" min="1" step="1" class="small-text" /> 回<br>
                                            <label for="wait_seconds">リトライ待機時間:</label> <input type="number" id="wait_seconds" name="yaml_settings[retry-policy][wait-seconds]" value="<?php echo esc_attr( isset($yaml_settings['retry-policy']['wait-seconds']) ? $yaml_settings['retry-policy']['wait-seconds'] : (isset($yaml_settings['retry-policy']['retry-after-seconds']) ? $yaml_settings['retry-policy']['retry-after-seconds'] : 30) ); ?>" min="1" step="1" class="small-text" /> 秒<br>
                                            <label for="status_codes_no_retry">リトライしないステータスコード:</label> <input type="text" id="status_codes_no_retry" name="yaml_settings[retry-policy][status-codes-no-retry]" value="<?php echo esc_attr( $status_codes_string ); ?>" class="regular-text" />
                                        </p>
                                        <p class="description">HTTP ステータスコードをカンマ区切り (例: 400, 403, 404)。空欄可。</p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="disallow_paths">除外パス</label></th>
                                <td>
                                    <textarea name="yaml_settings[disallow]" id="disallow_paths" rows="4" class="large-text" aria-describedby="disallow_paths-desc"><?php echo esc_textarea( $disallow_string ); ?></textarea>
                                    <p class="description" id="disallow_paths-desc">AI クローラーからアクセスを除外するパス。1 行に 1 つ (例: /wp-admin/)。</p>
                                    <div class="kw-preset-bar" data-target="disallow_paths">
                                        <span class="kw-preset-label">よく使うパスを追加:</span>
                                        <?php foreach ($disallow_presets as $p): ?>
                                            <button type="button" class="button button-small kw-preset-btn" data-preset-value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </details>

                <details class="kw-yaml-group">
                    <summary class="kw-yaml-group-summary">グループ C — 許可ボット / 正規ドメイン / サイトマップ</summary>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="allowed_bots">許可する AI ボット</label></th>
                                <td>
                                    <textarea name="yaml_settings[allowed-bots]" id="allowed_bots" rows="8" class="large-text" aria-describedby="allowed_bots-desc"><?php echo esc_textarea( $allowed_bots_string ); ?></textarea>
                                    <p class="description" id="allowed_bots-desc">アクセスを許可する AI ボットの User-agent を 1 行に 1 つ記載。</p>
                                    <div class="kw-preset-bar" data-target="allowed_bots">
                                        <span class="kw-preset-label">主要 AI ボットを追加:</span>
                                        <?php foreach ($bot_presets as $val => $lbl): ?>
                                            <button type="button" class="button button-small kw-preset-btn" data-preset-value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="canonical_url">正規ドメイン (URL)</label></th>
                                <td>
                                    <input type="url" name="yaml_settings[canonical-url]" id="canonical_url" value="<?php echo esc_attr( $yaml_settings['canonical-url'] ); ?>" class="regular-text" aria-describedby="canonical_url-desc" />
                                    <p class="description" id="canonical_url-desc">AI がコンテンツを引用する際の正規 URL。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="sitemap_url">サイトマップ URL</label></th>
                                <td>
                                    <input type="url" name="yaml_settings[sitemap]" id="sitemap_url" value="<?php echo esc_attr( $yaml_settings['sitemap'] ); ?>" class="regular-text" aria-describedby="sitemap_url-desc" />
                                    <p class="description" id="sitemap_url-desc">XML サイトマップの URL (通常: https://example.com/sitemap.xml)。</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </details>

            </div>
        </section>

        <!-- ============ TAB 5: 高度な設定 ============ -->
        <section id="kw-tab-advanced" class="kw-tab-pane" role="tabpanel" aria-labelledby="kw-tab-link-advanced" tabindex="0" hidden>
            <p class="kw-tab-intro">キャッシュなどパフォーマンス関連設定と、すべての設定をリセットする操作です。</p>

            <h3 class="kw-section-h3">キャッシュ</h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="cache_duration">キャッシュ有効期間</label></th>
                        <td>
                            <select name="cache_duration" id="cache_duration" aria-describedby="cache_duration-desc">
                                <?php foreach ($cache_seconds_map as $val => $lbl):
                                    $is_recommended = in_array($val, $recommended_cache, true);
                                    $display = $lbl . ($is_recommended ? '  [推奨]' : '');
                                ?>
                                    <option value="<?php echo esc_attr($val); ?>" <?php selected( $cache_duration, $val ); ?>><?php echo esc_html($display); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description" id="cache_duration-desc">短いほど更新が反映されやすく、長いほどサーバー負荷が下がります。月間 PV が多いサイトでは <strong>24 時間〜1 週間</strong> を推奨。</p>
                            <p style="margin-top:10px;">
                                <button type="button" class="button button-secondary" id="clear-cache-button">キャッシュをクリア</button>
                                <span id="cache-clear-message" class="kw-inline-msg" hidden></span>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="kw-danger-zone">
                <h3 class="kw-danger-zone-title">
                    <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                    Danger Zone — 取り消し不能な操作
                </h3>
                <p class="kw-danger-desc">以下の操作は取り消せません。実行前に内容を確認してください。</p>

                <div class="kw-danger-action">
                    <h4 class="kw-danger-action-title">すべての設定をデフォルトに戻す</h4>
                    <p class="kw-danger-action-desc">投稿タイプ・YAML フッター・運営者情報など、このプラグインのすべての設定を初期値に戻します。一度実行すると元に戻せません。</p>

                    <div class="kw-danger-confirm-row">
                        <label class="kw-confirm-label">
                            <input type="checkbox" id="kw-reset-understand" />
                            私はこの操作が <strong>取り消せない</strong> ことを理解しました
                        </label>
                    </div>

                    <p class="kw-danger-action-buttons">
                        <button type="button" class="button button-danger" id="reset-yaml-defaults" disabled>
                            すべての設定をデフォルトに戻す
                        </button>
                        <span id="reset-yaml-message" class="kw-inline-msg" hidden></span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Sticky save bar -->
        <div class="kw-save-bar" id="kw-save-bar" role="region" aria-label="設定の保存">
            <div class="kw-save-bar-inner">
                <span class="kw-unsaved-indicator" id="kw-unsaved-indicator" hidden>
                    <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                    未保存の変更があります
                </span>
                <div class="kw-save-bar-buttons">
                    <input type="submit" name="save_settings" class="button button-primary button-large" value="設定を保存" />
                </div>
            </div>
        </div>
    </form>
</div>
