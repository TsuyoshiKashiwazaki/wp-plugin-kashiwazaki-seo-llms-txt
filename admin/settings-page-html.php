<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap kashiwazaki-seo-llmstxt-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php if (isset($plugin_version) && $plugin_version): ?>
        <p class="kashiwazaki-seo-version-info">バージョン <?php echo esc_html($plugin_version); ?></p>
    <?php endif; ?>

    <?php if (isset($message)) echo wp_kses_post( $message ); ?>

    <form method="post">
        <?php wp_nonce_field( $save_nonce_action, $save_nonce_name ); ?>

        <div class="llms-section settings-section">
            <h2>基本設定</h2>
            <p>ここで設定した内容は、llms.txt (概要版) と llms-full.txt (詳細版) を動的に生成する際に使用されます。ファイルはWordPressのルートには物理的に作成されません。</p>

            <h3 class="settings-subheader">コンテンツリスト設定</h3>
            <table class="form-table">
                 <tbody>
                    <tr>
                        <th scope="row"><label for="posts_per_page">投稿タイプごとの最大件数</label></th>
                        <td>
                            <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_attr( $posts_per_page ); ?>" min="1" step="1" class="small-text" />
                            <p class="description">選択した各投稿タイプから取得する最大件数（新しい順）。(デフォルト: <?php echo esc_html($default_options['posts_per_page']); ?>)</p>
                        </td>
                    </tr>
                     <tr>
                        <th scope="row">含める投稿タイプ</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>含める投稿タイプ</span></legend>
                                <?php foreach ( $all_types as $type_obj ):
                                    if (!is_object($type_obj) || !isset($type_obj->name) || !isset($type_obj->labels) || !isset($type_obj->labels->name)) continue;
                                    $type = $type_obj->name;
                                    $type_label = $type_obj->labels->name;
                                    $checked = in_array( $type, $current_selected_types, true ) ? 'checked' : '';
                                ?>
                                    <label class="post-type-label">
                                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $type ); ?>" <?php echo esc_attr( $checked ); ?> />
                                        <?php echo esc_html( $type_label ); ?> (<code><?php echo esc_html($type); ?></code>)
                                    </label>
                                <?php endforeach; ?>
                                <p class="description">少なくとも1つの投稿タイプを選択する必要があります。「投稿」と「固定ページ」がデフォルトで選択されます。</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 class="settings-subheader">キャッシュ設定</h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="cache_duration">キャッシュ有効期間</label></th>
                        <td>
                            <select name="cache_duration" id="cache_duration">
                                <option value="0" <?php selected( $cache_duration, '0' ); ?>>キャッシュなし（リアルタイム生成）</option>
                                <option value="10800" <?php selected( $cache_duration, '10800' ); ?>>3時間</option>
                                <option value="86400" <?php selected( $cache_duration, '86400' ); ?>>24時間（1日）</option>
                                <option value="259200" <?php selected( $cache_duration, '259200' ); ?>>3日</option>
                                <option value="604800" <?php selected( $cache_duration, '604800' ); ?>>1週間</option>
                                <option value="-1" <?php selected( $cache_duration, '-1' ); ?>>永久（手動クリアまで）</option>
                            </select>
                            <p class="description">生成したコンテンツをキャッシュする期間を設定します。キャッシュを使用すると、サーバー負荷を軽減できます。</p>
                            <?php if ( $cache_duration !== '0' ): ?>
                                <p style="margin-top: 10px;">
                                    <button type="button" class="button button-secondary" id="clear-cache-button">キャッシュをクリア</button>
                                    <span id="cache-clear-message" style="margin-left: 10px; display: none;"></span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 class="settings-subheader">フッター設定</h3>
            <table class="form-table">
                 <tbody>
                    <tr>
                        <th scope="row">YAMLフッター設定</th>
                        <td>
                            <fieldset>
                                <label for="enable_yaml_header">
                                    <input type="checkbox" name="enable_yaml_header" id="enable_yaml_header" value="1" <?php checked( $enable_yaml_footer ); ?> />
                                    AIクローラー設定 (YAMLフッター) を有効にする
                                </label>
                                <p class="description">チェックすると、動的に生成されるファイルの末尾に YAML 形式の設定が出力されます。</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">制作者情報表示</th>
                        <td>
                            <fieldset>
                                <label for="show_copyright_footer">
                                    <input type="checkbox" name="show_copyright_footer" id="show_copyright_footer" value="1" <?php checked( $show_copyright_footer ); ?> />
                                    生成ファイル (llms.txt / llms-full.txt) の末尾にプラグイン制作者情報を表示する
                                </label>
                                <p class="description">このプラグインの開発者情報をファイルに含めるかどうかを選択します。(デフォルト: オン)</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <button type="button" class="button button-secondary" id="reset-yaml-defaults">
                    すべての設定をデフォルトに戻す
                </button>
                <span id="reset-yaml-message" style="margin-left: 10px; display: none;"></span>
                <p class="description" style="margin-top: 8px;">すべての設定をデフォルト値にリセットします。</p>
            </div>

            <h3 class="settings-subheader yaml-settings-header" <?php if (!$enable_yaml_footer) echo 'style="display: none;"'; ?>>AIクローラー設定 (YAMLフッター) 詳細</h3>
            <div class="yaml-settings-container" <?php if (!$enable_yaml_footer) echo 'style="display: none;"'; ?>>
                <p class="description" style="margin-bottom: 15px;">以下の詳細設定は、「AIクローラー設定 (YAMLフッター) を有効にする」がオンの場合にのみファイルに出力されます。</p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">コンテンツライセンス</th>
                            <td>
                                <fieldset> <legend class="screen-reader-text"><span>コンテンツライセンス</span></legend>
                                    <label> <input type="checkbox" name="yaml_settings[license][allow-ai-training]" value="1" <?php checked( $yaml_settings['license']['allow-ai-training'] ); ?> /> AI学習利用を許可する </label><br>
                                    <label> <input type="checkbox" name="yaml_settings[license][allow-ai-commercial-use]" value="1" <?php checked( $yaml_settings['license']['allow-ai-commercial-use'] ); ?> /> AI経由の商用利用を許可する </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">アクセス頻度制限</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="yaml_settings[rate-limit][enabled]" id="rate_limit_enabled" value="1" <?php checked( $yaml_settings['rate-limit']['enabled'] ); ?> />
                                        アクセス頻度制限を有効にする
                                    </label>
                                </fieldset>
                                <div class="rate-limit-details" <?php if (!$yaml_settings['rate-limit']['enabled']) echo 'style="display: none;"'; ?>>
                                    <p style="margin-top: 10px;">
                                        <label for="rate_limit_rps">秒間:</label> <input type="number" id="rate_limit_rps" name="yaml_settings[rate-limit][requests-per-second]" value="<?php echo esc_attr( $yaml_settings['rate-limit']['requests-per-second'] ); ?>" min="0" step="0.1" class="small-text" /> req/sec <br>
                                        <label for="rate_limit_rpm">分間:</label> <input type="number" id="rate_limit_rpm" name="yaml_settings[rate-limit][requests-per-minute]" value="<?php echo esc_attr( $yaml_settings['rate-limit']['requests-per-minute'] ); ?>" min="0" step="1" class="small-text" /> req/min
                                    </p>
                                    <p class="description">秒間および分間の最大リクエスト数。</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="canonical_url">正規ドメイン (URL)</label></th>
                            <td>
                                <input type="url" name="yaml_settings[canonical-url]" id="canonical_url" value="<?php echo esc_attr( $yaml_settings['canonical-url'] ); ?>" class="regular-text" />
                                <p class="description">AIがコンテンツを引用する際の正規URL。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">エラー時のリトライポリシー</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="yaml_settings[retry-policy][retry]" id="retry_enabled" value="1" <?php checked( $yaml_settings['retry-policy']['retry'] ); ?> />
                                        リトライを許可する
                                    </label>
                                </fieldset>
                                <div class="retry-policy-details" <?php if (!$yaml_settings['retry-policy']['retry']) echo 'style="display: none;"'; ?>>
                                    <p style="margin-top: 10px;">
                                        <label for="max_retries">最大リトライ回数:</label> <input type="number" id="max_retries" name="yaml_settings[retry-policy][max-retries]" value="<?php echo esc_attr( $yaml_settings['retry-policy']['max-retries'] ); ?>" min="1" step="1" class="small-text" /> 回<br>
                                        <label for="wait_seconds">リトライ待機時間:</label> <input type="number" id="wait_seconds" name="yaml_settings[retry-policy][wait-seconds]" value="<?php echo esc_attr( isset($yaml_settings['retry-policy']['wait-seconds']) ? $yaml_settings['retry-policy']['wait-seconds'] : (isset($yaml_settings['retry-policy']['retry-after-seconds']) ? $yaml_settings['retry-policy']['retry-after-seconds'] : 30) ); ?>" min="1" step="1" class="small-text" /> 秒<br>
                                        <label for="status_codes_no_retry">リトライしないステータスコード:</label> <input type="text" id="status_codes_no_retry" name="yaml_settings[retry-policy][status-codes-no-retry]" value="<?php echo esc_attr( $status_codes_string ); ?>" class="regular-text" />
                                    </p>
                                    <p class="description">HTTPステータスコードをカンマ区切りで入力（例: 400, 403, 404）。空欄可。</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="disallow_paths">除外パス</label></th>
                            <td>
                                <textarea name="yaml_settings[disallow]" id="disallow_paths" rows="4" class="large-text"><?php echo esc_textarea( $disallow_string ); ?></textarea>
                                <p class="description">AIクローラーからアクセスを除外するパス。1行に1つずつ記載（例: /wp-admin/）。空欄可。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="allowed_bots">許可するAIボット</label></th>
                            <td>
                                <textarea name="yaml_settings[allowed-bots]" id="allowed_bots" rows="8" class="large-text"><?php echo esc_textarea( $allowed_bots_string ); ?></textarea>
                                <p class="description">アクセスを許可するAIボットのUser-agentを1行に1つずつ記載。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sitemap_url">サイトマップURL</label></th>
                            <td>
                                <input type="url" name="yaml_settings[sitemap]" id="sitemap_url" value="<?php echo esc_attr( $yaml_settings['sitemap'] ); ?>" class="regular-text" />
                                <p class="description">XMLサイトマップのURL（通常: https://example.com/sitemap.xml）</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="llms-section files-section">
            <h2>ファイル生成状況</h2>
            <div class="llms-files-grid">
                <div class="llms-file-card">
                    <h3 class="file-title">llms.txt (概要版)</h3>
                    <p class="file-description">選択した投稿タイプのタイトルとリンクのリスト</p>
                    <div class="file-status">
                        <span class="status-label">ステータス: </span>
                        <span class="status-value status-<?php echo $llms_txt_enabled ? 'enabled' : 'disabled'; ?>" data-file-type="llms-txt">
                            <?php echo $llms_txt_enabled ? '生成中' : '無効'; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" class="button">プレビュー</a>
                        <button type="button" class="button toggle-file-status" data-file-type="llms-txt" data-action="<?php echo $llms_txt_enabled ? 'disable' : 'enable'; ?>">
                            <?php echo $llms_txt_enabled ? '無効化' : '生成を有効化'; ?>
                        </button>
                    </div>
                </div>

                <div class="llms-file-card">
                    <h3 class="file-title">llms-full.txt (詳細版)</h3>
                    <p class="file-description">タイトル、リンク、更新日時、公開日時、抜粋を含む詳細情報</p>
                    <div class="file-status">
                        <span class="status-label">ステータス: </span>
                        <span class="status-value status-<?php echo $llms_full_txt_enabled ? 'enabled' : 'disabled'; ?>" data-file-type="llms-full-txt">
                            <?php echo $llms_full_txt_enabled ? '生成中' : '無効'; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" class="button">プレビュー</a>
                        <button type="button" class="button toggle-file-status" data-file-type="llms-full-txt" data-action="<?php echo $llms_full_txt_enabled ? 'disable' : 'enable'; ?>">
                            <?php echo $llms_full_txt_enabled ? '無効化' : '生成を有効化'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button( '設定を保存', 'primary', 'save_settings' ); ?>
    </form>
</div>