/**
 * public/js/notif-poll.js
 * Shared short-poll module for the notification badge.
 * Import this on any page, then call: NotifPoll.start(basePath)
 *
 * Short-poll every 30s — simple, reliable, no WebSocket needed.
 * Switch to 60s on hidden tabs to save requests.
 */

const NotifPoll = (() => {
    let _base       = '';
    let _timer      = null;
    let _lastCount  = -1;
    let _onNewNotif = null;  // optional callback when new notifications arrive

    const INTERVAL_ACTIVE = 30_000;  // 30s when tab is visible
    const INTERVAL_HIDDEN = 90_000;  // 90s when tab is hidden

    function _getInterval() {
        return document.visibilityState === 'hidden' ? INTERVAL_HIDDEN : INTERVAL_ACTIVE;
    }

    async function _poll() {
        try {
            const res  = await fetch(BASE + '/api/user/notifications/unread-count', {
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const json  = await res.json();
            const count = json.data?.count ?? 0;
            _updateBadge(count);

            // Fire callback if count increased (new notifications arrived)
            if (_lastCount !== -1 && count > _lastCount && typeof _onNewNotif === 'function') {
                _onNewNotif(count);
            }
            _lastCount = count;
        } catch {
            // Network error — silent fail, will retry next interval
        } finally {
            _schedule();
        }
    }

    function _schedule() {
        clearTimeout(_timer);
        _timer = setTimeout(_poll, _getInterval());
    }

    function _updateBadge(count) {
        // Update ALL badge elements on the page (sidebar + any topbar badge)
        document.querySelectorAll('[data-notif-badge]').forEach(el => {
            if (count > 0) {
                el.textContent    = count > 99 ? '99+' : count;
                el.style.display  = 'inline-block';
            } else {
                el.style.display  = 'none';
            }
        });
    }

    // Restart polling when tab becomes visible again
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            clearTimeout(_timer);
            _poll(); // poll immediately on focus
        }
    });

    return {
        /**
         * Start polling.
         * @param {string} basePath   - e.g. window.WH_BASE
         * @param {Function} [onNew]  - optional callback(newCount) when unread count increases
         */
        start(basePath, onNew) {
            _base       = basePath || '';
            _onNewNotif = onNew    || null;
            _poll();   // immediate first poll
        },

        /** Force an immediate re-poll (e.g. after marking read) */
        refresh() { clearTimeout(_timer); _poll(); },

        /** Get last known count without fetching */
        getCount() { return _lastCount; },
    };
})();