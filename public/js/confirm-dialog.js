/**
 * public/js/confirm-dialog.js
 * Custom confirm dialog — drop-in replacement for window.confirm().
 *
 * Usage:
 *   const ok = await ConfirmDialog.show('Delete this task?');
 *   const ok = await ConfirmDialog.show('Are you sure?', {
 *       title:         'Delete Task',
 *       confirmText:   'Yes, Delete',
 *       cancelText:    'Cancel',
 *       type:          'danger',   // 'danger' | 'warning' | 'info'
 *   });
 *   if (ok) { ... }
 */

const ConfirmDialog = (() => {

    // ── Inject styles once ────────────────────────────────────────────
    function _injectStyles() {
        if (document.getElementById('confirm-dialog-styles')) return;
        const style = document.createElement('style');
        style.id = 'confirm-dialog-styles';
        style.textContent = `
            .cd-backdrop {
                position: fixed; inset: 0; z-index: 9999;
                background: rgba(26, 18, 24, 0.55);
                backdrop-filter: blur(4px);
                display: flex; align-items: center; justify-content: center;
                opacity: 0; transition: opacity 0.2s cubic-bezier(.4,0,.2,1);
                padding: 16px;
            }
            .cd-backdrop.cd-open { opacity: 1; }

            .cd-modal {
                background: #ffffff;
                border-radius: 16px;
                padding: 32px 28px 24px;
                width: 100%; max-width: 400px;
                box-shadow: 0 20px 60px rgba(0,0,0,.2), 0 4px 16px rgba(0,0,0,.1);
                transform: scale(0.93) translateY(12px);
                transition: transform 0.22s cubic-bezier(.4,0,.2,1);
                display: flex; flex-direction: column; gap: 8px;
            }
            .cd-backdrop.cd-open .cd-modal { transform: scale(1) translateY(0); }

            .cd-icon-wrap {
                width: 52px; height: 52px; border-radius: 14px;
                display: flex; align-items: center; justify-content: center;
                margin-bottom: 4px; flex-shrink: 0;
            }
            .cd-icon-wrap.danger  { background: #fef2f2; }
            .cd-icon-wrap.warning { background: #fff7ed; }
            .cd-icon-wrap.info    { background: #eff6ff; }
            .cd-icon-wrap svg { width: 26px; height: 26px; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; fill: none; }
            .cd-icon-wrap.danger  svg { stroke: #dc2626; }
            .cd-icon-wrap.warning svg { stroke: #ea580c; }
            .cd-icon-wrap.info    svg { stroke: #1d4ed8; }

            .cd-title {
                font-family: 'Playfair Display', 'Georgia', serif;
                font-size: 19px; font-weight: 700;
                color: #1a1218; line-height: 1.3;
                margin: 0;
            }

            .cd-message {
                font-size: 14px; color: #6b5b65;
                line-height: 1.6; margin: 0;
            }

            .cd-actions {
                display: flex; gap: 10px;
                margin-top: 20px; justify-content: flex-end;
            }

            .cd-btn {
                padding: 10px 22px; border-radius: 8px;
                font-family: 'DM Sans', sans-serif;
                font-size: 14px; font-weight: 600;
                cursor: pointer; border: none;
                transition: all 0.18s ease;
                display: inline-flex; align-items: center; gap: 7px;
                min-width: 90px; justify-content: center;
            }
            .cd-btn:focus-visible {
                outline: 2px solid #6A0031;
                outline-offset: 2px;
            }

            /* Cancel */
            .cd-btn-cancel {
                background: transparent;
                border: 1.5px solid #e8dde3;
                color: #6b5b65;
            }
            .cd-btn-cancel:hover {
                border-color: #6A0031;
                color: #6A0031;
                background: #fdf2f6;
            }

            /* Confirm variants */
            .cd-btn-confirm.danger {
                background: #dc2626; color: #fff;
                box-shadow: 0 4px 12px rgba(220,38,38,.3);
            }
            .cd-btn-confirm.danger:hover {
                background: #b91c1c;
                box-shadow: 0 4px 16px rgba(220,38,38,.4);
                transform: translateY(-1px);
            }

            .cd-btn-confirm.warning {
                background: #ea580c; color: #fff;
                box-shadow: 0 4px 12px rgba(234,88,12,.3);
            }
            .cd-btn-confirm.warning:hover {
                background: #c2410c;
                box-shadow: 0 4px 16px rgba(234,88,12,.4);
                transform: translateY(-1px);
            }

            .cd-btn-confirm.info {
                background: #6A0031; color: #fff;
                box-shadow: 0 4px 12px rgba(106,0,49,.3);
            }
            .cd-btn-confirm.info:hover {
                background: #8a1144;
                box-shadow: 0 4px 16px rgba(106,0,49,.4);
                transform: translateY(-1px);
            }

            .cd-btn-confirm:active { transform: translateY(0); }
        `;
        document.head.appendChild(style);
    }

    // ── SVG icons per type 
    function _icon(type) {
        if (type === 'danger') return `
            <svg viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>`;
        if (type === 'warning') return `
            <svg viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>`;
        return `
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>`;
    }

    // ── Build DOM 
    function _build(message, options) {
        const {
            title       = 'Are you sure?',
            confirmText = 'Confirm',
            cancelText  = 'Cancel',
            type        = 'danger',
        } = options;

        const backdrop = document.createElement('div');
        backdrop.className = 'cd-backdrop';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        backdrop.setAttribute('aria-labelledby', 'cd-title');

        backdrop.innerHTML = `
            <div class="cd-modal">
                <div class="cd-icon-wrap ${type}">${_icon(type)}</div>
                <h2 class="cd-title" id="cd-title">${_escHtml(title)}</h2>
                <p  class="cd-message">${_escHtml(message)}</p>
                <div class="cd-actions">
                    <button class="cd-btn cd-btn-cancel"  id="cd-cancel">${_escHtml(cancelText)}</button>
                    <button class="cd-btn cd-btn-confirm ${type}" id="cd-confirm">${_escHtml(confirmText)}</button>
                </div>
            </div>
        `;

        return backdrop;
    }

    function _escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ── Main show function 
    function show(message, options = {}) {
        _injectStyles();

        return new Promise(resolve => {
            const backdrop = _build(message, options);
            document.body.appendChild(backdrop);

            // Focus trap — focus confirm button by default
            const confirmBtn = backdrop.querySelector('#cd-confirm');
            const cancelBtn  = backdrop.querySelector('#cd-cancel');

            // Animate in
            requestAnimationFrame(() => {
                requestAnimationFrame(() => backdrop.classList.add('cd-open'));
            });

            // Focus the cancel button by default (safer UX)
            setTimeout(() => cancelBtn.focus(), 50);

            function cleanup(result) {
                backdrop.classList.remove('cd-open');
                setTimeout(() => {
                    backdrop.remove();
                    resolve(result);
                }, 200); // wait for fade-out transition
            }

            // Button handlers
            confirmBtn.addEventListener('click', () => cleanup(true));
            cancelBtn.addEventListener('click',  () => cleanup(false));

            // Click backdrop to cancel
            backdrop.addEventListener('click', e => {
                if (e.target === backdrop) cleanup(false);
            });

            // Keyboard: Escape = cancel, Enter = confirm
            function onKey(e) {
                if (e.key === 'Escape') { document.removeEventListener('keydown', onKey); cleanup(false); }
                if (e.key === 'Enter' && document.activeElement === confirmBtn) {
                    document.removeEventListener('keydown', onKey);
                    cleanup(true);
                }
            }
            document.addEventListener('keydown', onKey);
        });
    }

    // ── Convenience shorthands 
    return {
        show,

        /** Quick danger confirm — for deletes */
        danger(message, title = 'Delete') {
            return show(message, { title, type: 'danger', confirmText: 'Yes, Delete' });
        },

        /** Quick warning confirm */
        warning(message, title = 'Are you sure?') {
            return show(message, { title, type: 'warning', confirmText: 'Continue' });
        },

        /** Quick info confirm */
        info(message, title = 'Confirm') {
            return show(message, { title, type: 'info', confirmText: 'OK' });
        },
    };
})();