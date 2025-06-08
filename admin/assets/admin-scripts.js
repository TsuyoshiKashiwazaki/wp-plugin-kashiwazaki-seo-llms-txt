// admin/assets/admin-scripts.js
document.addEventListener('DOMContentLoaded', function() {
    // YAMLフッター設定の表示/非表示切り替え
    const yamlToggle = document.getElementById('enable_yaml_header');
    const yamlContainer = document.querySelector('.yaml-settings-container');
    const yamlHeader = document.querySelector('.yaml-settings-header');

    if (yamlToggle && yamlContainer && yamlHeader) {
        function toggleYamlForm() {
            const show = yamlToggle.checked;
            yamlContainer.style.display = show ? '' : 'none';
            yamlHeader.style.display = show ? '' : 'none';
        }
        yamlToggle.addEventListener('change', toggleYamlForm);
        toggleYamlForm(); // 初期状態設定
    }

    // アクセス頻度制限の表示/非表示切り替え
    const rateLimitToggle = document.getElementById('rate_limit_enabled');
    const rateLimitDetails = document.querySelector('.rate-limit-details');

    if (rateLimitToggle && rateLimitDetails) {
        function toggleRateLimitDetails() {
            rateLimitDetails.style.display = rateLimitToggle.checked ? '' : 'none';
        }
        rateLimitToggle.addEventListener('change', toggleRateLimitDetails);
        toggleRateLimitDetails(); // 初期状態設定
    }

    // リトライポリシー詳細の表示/非表示切り替え
    const retryToggle = document.getElementById('retry_enabled');
    const retryDetails = document.querySelector('.retry-policy-details');

    if (retryToggle && retryDetails) {
        function toggleRetryDetails() {
            retryDetails.style.display = retryToggle.checked ? '' : 'none';
        }
        retryToggle.addEventListener('change', toggleRetryDetails);
        toggleRetryDetails(); // 初期状態設定
    }

    // ファイル生成の有効化/無効化ボタンの処理
    const toggleButtons = document.querySelectorAll('.toggle-file-status');
    console.log('Found toggle buttons:', toggleButtons.length); // デバッグ用
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // デフォルト動作を防ぐ
            
            const fileType = this.getAttribute('data-file-type');
            const action = this.getAttribute('data-action');
            
            console.log('Button clicked:', fileType, action); // デバッグ用
            
            // 確認ダイアログ（無効化時のみ）
            if (action === 'disable') {
                if (!confirm(kashiwazakiLlmsAdmin.confirmDelete || 'このファイルの生成を無効化してもよろしいですか？')) {
                    return;
                }
            }
            
            handleFileToggle(fileType, action, this);
        });
    });

    // ファイルの有効化/無効化を処理
    function handleFileToggle(fileType, action, button) {
        const nonce = kashiwazakiLlmsAdmin.nonce || '';
        const ajaxUrl = kashiwazakiLlmsAdmin.ajaxurl || ajaxurl;
        
        console.log('Toggle action:', fileType, action); // デバッグ用
        
        // ボタンを無効化
        button.disabled = true;
        
        // Ajaxリクエスト
        const data = new FormData();
        data.append('action', 'kashiwazaki_toggle_llms_file');
        data.append('file_type', fileType);
        data.append('toggle_action', action);
        data.append('nonce', nonce);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin' // Cookie を含める
        })
        .then(response => {
            console.log('Response status:', response.status); // デバッグ用
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                // ステータステキストを更新
                const statusElement = document.querySelector(`.status-value[data-file-type="${fileType}"]`);
                if (statusElement) {
                    const newStatus = result.data.enabled ? 
                        (kashiwazakiLlmsAdmin.statusEnabled || '生成中') : 
                        (kashiwazakiLlmsAdmin.statusDisabled || '無効');
                    statusElement.textContent = newStatus;
                    statusElement.className = 'status-value status-' + (result.data.enabled ? 'enabled' : 'disabled');
                }
                
                // ボタンのテキストとアクションを更新
                if (result.data.enabled) {
                    button.textContent = '無効化';
                    button.setAttribute('data-action', 'disable');
                } else {
                    button.textContent = '生成を有効化';
                    button.setAttribute('data-action', 'enable');
                }
                
                // 成功メッセージを表示
                showNotice(result.data.message || '設定が更新されました。', 'success');
            } else {
                // エラーメッセージを表示
                showNotice(result.data.message || 'エラーが発生しました。', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotice('通信エラーが発生しました。', 'error');
        })
        .finally(() => {
            // ボタンを再度有効化
            button.disabled = false;
        });
    }
    
    // 通知メッセージを表示
    function showNotice(message, type) {
        const noticeDiv = document.createElement('div');
        noticeDiv.className = 'notice notice-' + type + ' is-dismissible';
        noticeDiv.innerHTML = '<p>' + message + '</p>';
        
        const wrapDiv = document.querySelector('.wrap.kashiwazaki-seo-llmstxt-wrap');
        if (wrapDiv) {
            const firstH1 = wrapDiv.querySelector('h1');
            if (firstH1 && firstH1.nextSibling) {
                wrapDiv.insertBefore(noticeDiv, firstH1.nextSibling);
            } else {
                wrapDiv.insertBefore(noticeDiv, wrapDiv.firstChild);
            }
            
            // 5秒後に自動的に削除
            setTimeout(() => {
                noticeDiv.remove();
            }, 5000);
        }
    }
    
    // キャッシュクリアボタンの処理
    const clearCacheButton = document.getElementById('clear-cache-button');
    if (clearCacheButton) {
        clearCacheButton.addEventListener('click', function() {
            const nonce = kashiwazakiLlmsAdmin.nonce || '';
            const messageSpan = document.getElementById('cache-clear-message');

            this.disabled = true;

            const data = new FormData();
            data.append('action', 'kashiwazaki_clear_llms_cache');
            data.append('nonce', nonce);

            fetch(kashiwazakiLlmsAdmin.ajaxurl || ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (messageSpan) {
                        messageSpan.textContent = result.data.message || 'キャッシュをクリアしました';
                        messageSpan.style.color = '#46b450';
                        messageSpan.style.display = 'inline';
                    }
                } else {
                    if (messageSpan) {
                        messageSpan.textContent = result.data.message || 'エラーが発生しました';
                        messageSpan.style.color = '#dc3232';
                        messageSpan.style.display = 'inline';
                    }
                }

                setTimeout(() => {
                    if (messageSpan) {
                        messageSpan.style.display = 'none';
                    }
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                if (messageSpan) {
                    messageSpan.textContent = '通信エラーが発生しました';
                    messageSpan.style.color = '#dc3232';
                    messageSpan.style.display = 'inline';
                }
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    }

    // YAML設定をデフォルトに戻すボタンの処理
    const resetYamlButton = document.getElementById('reset-yaml-defaults');
    console.log('Reset YAML button found:', resetYamlButton); // デバッグ
    if (resetYamlButton) {
        resetYamlButton.addEventListener('click', function() {
            console.log('Reset YAML button clicked'); // デバッグ

            if (!confirm('すべての設定をデフォルトに戻してもよろしいですか？\nこの操作は元に戻せません。')) {
                console.log('User cancelled reset'); // デバッグ
                return;
            }

            const nonce = kashiwazakiLlmsAdmin.nonce || '';
            const messageSpan = document.getElementById('reset-yaml-message');

            console.log('Starting reset...'); // デバッグ
            this.disabled = true;

            const data = new FormData();
            data.append('action', 'kashiwazaki_reset_yaml_defaults');
            data.append('nonce', nonce);

            fetch(kashiwazakiLlmsAdmin.ajaxurl || ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => {
                console.log('Response received:', response.status); // デバッグ
                return response.json();
            })
            .then(result => {
                console.log('Result:', result); // デバッグ
                if (result.success) {
                    if (messageSpan) {
                        messageSpan.textContent = result.data.message || 'デフォルトに戻しました';
                        messageSpan.style.color = '#46b450';
                        messageSpan.style.display = 'inline';
                    }

                    // 2秒後にページをリロード
                    console.log('Will reload in 2 seconds'); // デバッグ
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    console.error('Reset failed:', result.data); // デバッグ
                    if (messageSpan) {
                        messageSpan.textContent = result.data.message || 'エラーが発生しました';
                        messageSpan.style.color = '#dc3232';
                        messageSpan.style.display = 'inline';
                    }
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (messageSpan) {
                    messageSpan.textContent = '通信エラーが発生しました';
                    messageSpan.style.color = '#dc3232';
                    messageSpan.style.display = 'inline';
                }
                this.disabled = false;
            });
        });
    } else {
        console.error('Reset YAML button not found!'); // デバッグ
    }
});