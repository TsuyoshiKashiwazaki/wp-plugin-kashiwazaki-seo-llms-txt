/* admin/assets/admin-scripts.js — UX redesign (3者合意 R4) */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        var noticeArea = document.getElementById('kw-llms-notice-area');
        var form = document.getElementById('kw-llms-settings-form');

        // -------- Notice helper (a11y: aria-live region) --------
        function showNotice(msg, type) {
            if (!noticeArea) return;
            var cls = (type === 'error') ? 'notice-error' : (type === 'warning' ? 'notice-warning' : 'notice-success');
            var wrap = document.createElement('div');
            wrap.className = 'notice ' + cls + ' is-dismissible';
            var p = document.createElement('p');
            p.textContent = msg;
            wrap.appendChild(p);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'notice-dismiss';
            btn.setAttribute('aria-label', 'この通知を閉じる');
            btn.addEventListener('click', function() { wrap.remove(); });
            wrap.appendChild(btn);
            noticeArea.innerHTML = '';
            noticeArea.appendChild(wrap);
            // Auto-dismiss success after 4s
            if (type !== 'error') {
                setTimeout(function() { if (wrap.parentNode) wrap.remove(); }, 4500);
            }
        }

        // -------- Tabs (hash-based) --------
        var tabLinks = document.querySelectorAll('.kw-llms-tabs .nav-tab');
        var tabPanes = document.querySelectorAll('.kw-tab-pane');
        var tabHashKey = 'kw_llms_active_tab';

        function activateTab(tabId) {
            if (!tabId) return;
            var found = false;
            tabLinks.forEach(function(a) {
                var matches = (a.dataset.tab === tabId);
                a.classList.toggle('nav-tab-active', matches);
                a.setAttribute('aria-selected', matches ? 'true' : 'false');
                a.setAttribute('tabindex', matches ? '0' : '-1');
                if (matches) found = true;
            });
            tabPanes.forEach(function(p) {
                var matches = (p.id === 'kw-tab-' + tabId);
                p.classList.toggle('kw-tab-pane-active', matches);
                if (matches) {
                    p.removeAttribute('hidden');
                } else {
                    p.setAttribute('hidden', '');
                }
            });
            if (found) {
                try { localStorage.setItem(tabHashKey, tabId); } catch (e) {}
            }
        }

        // Initial activation: location.hash > localStorage > default
        var initialTab = 'dashboard';
        if (window.location.hash) {
            var h = window.location.hash.replace('#kw-tab-', '');
            if (document.getElementById('kw-tab-' + h)) initialTab = h;
        } else {
            try {
                var stored = localStorage.getItem(tabHashKey);
                if (stored && document.getElementById('kw-tab-' + stored)) initialTab = stored;
            } catch (e) {}
        }
        activateTab(initialTab);

        tabLinks.forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                activateTab(this.dataset.tab);
                window.history.replaceState(null, '', '#kw-tab-' + this.dataset.tab);
            });
            a.addEventListener('keydown', function(e) {
                var idx = Array.prototype.indexOf.call(tabLinks, this);
                var next = null;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = tabLinks[idx + 1] || tabLinks[0];
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = tabLinks[idx - 1] || tabLinks[tabLinks.length - 1];
                else if (e.key === 'Home') next = tabLinks[0];
                else if (e.key === 'End') next = tabLinks[tabLinks.length - 1];
                if (next) {
                    e.preventDefault();
                    next.focus();
                    activateTab(next.dataset.tab);
                    window.history.replaceState(null, '', '#kw-tab-' + next.dataset.tab);
                }
            });
        });

        // Tab jump links (e.g., dashboard "キャッシュ設定へ")
        // F3 (full-audit-r2): activate 後に target tab link へ focus を移す
        // (元のリンク要素は hidden パネル内にあるため focus が宙に浮く問題を解消)
        document.querySelectorAll('.kw-tab-jump').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var t = this.dataset.targetTab;
                if (t) {
                    activateTab(t);
                    window.history.replaceState(null, '', '#kw-tab-' + t);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    var targetLink = document.getElementById('kw-tab-link-' + t);
                    if (targetLink) {
                        targetLink.focus();
                    }
                }
            });
        });

        // -------- Post type checkbox visual state --------
        document.querySelectorAll('.kw-post-type-item input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                this.closest('.kw-post-type-item').classList.toggle('is-checked', this.checked);
            });
        });

        // -------- E-E-A-T summary count update --------
        var eeatCount = document.getElementById('kw-eeat-summary-count');
        var eeatFields = document.querySelectorAll('.kw-eeat-field');
        var eeatBlockToggle = document.getElementById('enable_eeat_block');
        var eeatDetails = document.getElementById('kw-eeat-details');

        function updateEeatCount() {
            if (!eeatCount) return;
            var filled = 0;
            eeatFields.forEach(function(f) {
                if (f.value && f.value.trim() !== '') filled++;
            });
            var total = parseInt(eeatCount.dataset.total, 10) || eeatFields.length;
            eeatCount.textContent = filled + ' / ' + total + ' 項目入力済';
            eeatCount.classList.toggle('is-complete', filled >= total);
        }
        eeatFields.forEach(function(f) {
            f.addEventListener('input', updateEeatCount);
            f.addEventListener('change', updateEeatCount);
        });
        updateEeatCount();

        // E-E-A-T toggle: when enable_eeat_block checkbox flips ON, auto-open details
        if (eeatBlockToggle && eeatDetails) {
            eeatBlockToggle.addEventListener('change', function() {
                if (this.checked) {
                    eeatDetails.open = true;
                }
            });
        }

        // -------- YAML wrapper toggle --------
        var yamlToggle = document.getElementById('enable_yaml_header');
        var yamlWrapper = document.querySelector('.kw-yaml-details-wrapper');
        if (yamlToggle && yamlWrapper) {
            yamlToggle.addEventListener('change', function() {
                if (this.checked) yamlWrapper.removeAttribute('hidden');
                else yamlWrapper.setAttribute('hidden', '');
            });
        }

        // Conditional sub-areas (rate-limit, retry-policy)
        function bindConditional(toggleId, areaSelector) {
            var t = document.getElementById(toggleId);
            var area = document.querySelector(areaSelector);
            if (t && area) {
                t.addEventListener('change', function() {
                    if (this.checked) area.removeAttribute('hidden');
                    else area.setAttribute('hidden', '');
                });
            }
        }
        bindConditional('rate_limit_enabled', '.rate-limit-details');
        bindConditional('retry_enabled', '.retry-policy-details');

        // -------- Preset insert buttons (textarea) --------
        document.querySelectorAll('.kw-preset-bar').forEach(function(bar) {
            var targetId = bar.dataset.target;
            var textarea = document.getElementById(targetId);
            if (!textarea) return;
            bar.querySelectorAll('.kw-preset-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var val = this.dataset.presetValue;
                    if (!val) return;
                    var lines = (textarea.value || '').split(/\r?\n/).map(function(l) { return l.trim(); }).filter(function(l) { return l !== ''; });
                    if (lines.indexOf(val) === -1) {
                        lines.push(val);
                        textarea.value = lines.join('\n');
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        showNotice('「' + val + '」を追加しました', 'success');
                    } else {
                        showNotice('「' + val + '」はすでに含まれています', 'warning');
                    }
                });
            });
        });

        // -------- Reset Danger Zone (checkbox_understand_then_button) --------
        var resetUnderstand = document.getElementById('kw-reset-understand');
        var resetButton = document.getElementById('reset-yaml-defaults');
        var resetMessage = document.getElementById('reset-yaml-message');

        if (resetUnderstand && resetButton) {
            resetUnderstand.addEventListener('change', function() {
                resetButton.disabled = !this.checked;
            });
            resetButton.addEventListener('click', function() {
                if (resetButton.disabled) return;
                if (!confirm('本当にすべての設定をデフォルトに戻しますか？\nこの操作は取り消せません。')) return;

                var nonce = (window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.nonce) || '';
                resetButton.disabled = true;
                if (resetMessage) {
                    resetMessage.textContent = '処理中...';
                    resetMessage.className = 'kw-inline-msg';
                    resetMessage.hidden = false;
                }
                var data = new FormData();
                data.append('action', 'kashiwazaki_reset_yaml_defaults');
                data.append('nonce', nonce);
                fetch((window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.ajaxurl) || window.ajaxurl, {
                    method: 'POST',
                    body: data
                }).then(function(r) { return r.json(); }).then(function(result) {
                    if (result && result.success) {
                        var m = (result.data && result.data.message) || 'デフォルトに戻しました';
                        showNotice(m, 'success');
                        if (resetMessage) {
                            resetMessage.textContent = m;
                            resetMessage.className = 'kw-inline-msg is-success';
                        }
                        // Disable beforeunload before reload
                        formDirty = false;
                        setTimeout(function() { window.location.reload(); }, 1500);
                    } else {
                        var em = (result && result.data && result.data.message) || 'エラーが発生しました';
                        showNotice(em, 'error');
                        if (resetMessage) {
                            resetMessage.textContent = em;
                            resetMessage.className = 'kw-inline-msg is-error';
                        }
                        if (resetUnderstand) resetUnderstand.checked = false;
                        resetButton.disabled = true;
                    }
                }).catch(function() {
                    showNotice('通信エラーが発生しました', 'error');
                    if (resetMessage) {
                        resetMessage.textContent = '通信エラーが発生しました';
                        resetMessage.className = 'kw-inline-msg is-error';
                    }
                    if (resetUnderstand) resetUnderstand.checked = false;
                    resetButton.disabled = true;
                });
            });
        }

        // -------- File toggle (llms.txt / llms-full.txt ON/OFF) --------
        document.querySelectorAll('.kw-toggle-file').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var fileType = this.dataset.fileType;
                var action = this.dataset.action;
                if (!fileType || !action) return;
                if (action === 'disable') {
                    var msg = (window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.confirmDelete) || 'このファイルの生成を無効化してもよろしいですか？';
                    if (!confirm(msg)) return;
                }
                var nonce = (window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.nonce) || '';
                var self = this;
                self.disabled = true;
                var data = new FormData();
                data.append('action', 'kashiwazaki_toggle_llms_file');
                data.append('nonce', nonce);
                data.append('file_type', fileType);
                data.append('toggle_action', action);
                fetch((window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.ajaxurl) || window.ajaxurl, {
                    method: 'POST',
                    body: data
                }).then(function(r) { return r.json(); }).then(function(result) {
                    if (result && result.success) {
                        var enabled = result.data && result.data.enabled;
                        var card = self.closest('.kw-card');
                        var badge = card ? card.querySelector('.kw-status-badge') : null;
                        if (badge) {
                            badge.classList.toggle('kw-status-on', enabled);
                            badge.classList.toggle('kw-status-off', !enabled);
                            badge.textContent = enabled ? '生成中' : '無効';
                        }
                        self.dataset.action = enabled ? 'disable' : 'enable';
                        self.textContent = enabled ? '無効化' : '有効化';
                        self.classList.toggle('button-primary', !enabled);
                        self.classList.toggle('button-secondary', enabled);
                        showNotice((result.data && result.data.message) || (enabled ? '有効化しました' : '無効化しました'), 'success');
                    } else {
                        showNotice((result && result.data && result.data.message) || 'エラーが発生しました', 'error');
                    }
                }).catch(function() {
                    showNotice('通信エラーが発生しました', 'error');
                }).finally(function() {
                    self.disabled = false;
                });
            });
        });

        // -------- Cache clear (top dashboard + advanced tab) --------
        function bindClearCache(buttonId, messageId) {
            var btn = document.getElementById(buttonId);
            if (!btn) return;
            btn.addEventListener('click', function() {
                if (btn.disabled) return;
                var nonce = (window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.nonce) || '';
                btn.disabled = true;
                var msgEl = messageId ? document.getElementById(messageId) : null;
                if (msgEl) { msgEl.textContent = 'クリア中...'; msgEl.className = 'kw-inline-msg'; msgEl.hidden = false; }
                var data = new FormData();
                data.append('action', 'kashiwazaki_clear_llms_cache');
                data.append('nonce', nonce);
                fetch((window.kashiwazakiLlmsAdmin && window.kashiwazakiLlmsAdmin.ajaxurl) || window.ajaxurl, {
                    method: 'POST',
                    body: data
                }).then(function(r) { return r.json(); }).then(function(result) {
                    if (result && result.success) {
                        var m = (result.data && result.data.message) || 'キャッシュをクリアしました';
                        showNotice(m, 'success');
                        if (msgEl) { msgEl.textContent = m; msgEl.className = 'kw-inline-msg is-success'; }
                    } else {
                        var em = (result && result.data && result.data.message) || 'エラーが発生しました';
                        showNotice(em, 'error');
                        if (msgEl) { msgEl.textContent = em; msgEl.className = 'kw-inline-msg is-error'; }
                    }
                }).catch(function() {
                    showNotice('通信エラーが発生しました', 'error');
                    if (msgEl) { msgEl.textContent = '通信エラーが発生しました'; msgEl.className = 'kw-inline-msg is-error'; }
                }).finally(function() {
                    btn.disabled = false;
                });
            });
        }
        bindClearCache('kw-clear-cache-btn', null);
        bindClearCache('clear-cache-button', 'cache-clear-message');

        // -------- Unsaved changes detection + sticky save bar indicator --------
        var unsavedIndicator = document.getElementById('kw-unsaved-indicator');
        var formDirty = false;
        var initialFormData = null;

        function snapshotForm() {
            if (!form) return null;
            var fd = new FormData(form);
            var pairs = [];
            fd.forEach(function(v, k) { pairs.push(k + '=' + v); });
            pairs.sort();
            return pairs.join('|');
        }

        function checkDirty() {
            if (!form || initialFormData === null) return;
            var current = snapshotForm();
            var newDirty = (current !== initialFormData);
            if (newDirty !== formDirty) {
                formDirty = newDirty;
                if (unsavedIndicator) {
                    if (formDirty) unsavedIndicator.removeAttribute('hidden');
                    else unsavedIndicator.setAttribute('hidden', '');
                }
            }
        }

        if (form) {
            // Snapshot after a tick to let other scripts initialize values
            setTimeout(function() { initialFormData = snapshotForm(); }, 100);
            form.addEventListener('input', checkDirty);
            form.addEventListener('change', checkDirty);

            window.addEventListener('beforeunload', function(e) {
                if (formDirty) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            });

            form.addEventListener('submit', function() {
                formDirty = false; // submit clears dirty
            });
        }
    });
})();
