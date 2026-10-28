const counters = document.querySelector('[data-sale-status-counters]');

if (counters) {
    let loading = false;
    let queued = false;
    let stopped = false;
    let suspended = false;
    let request;

    function setHidden(element, hidden) {
        element.hidden = hidden;
        element.classList.toggle('hidden', hidden);
    }

    async function refreshCounts() {
        if (stopped || suspended || document.hidden) return;
        if (loading) {
            queued = true;
            return;
        }
        loading = true;
        request = new AbortController();
        const timeout = setTimeout(() => request.abort(), 15000);
        try {
            const response = await fetch(counters.dataset.url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin', cache: 'no-store', signal: request.signal,
            });
            if ([401, 403].includes(response.status)) {
                stopped = true;
                setHidden(counters, true);
                return;
            }
            if (!response.ok || response.redirected) return;
            const counts = await response.json();
            if (!counts || Array.isArray(counts) || typeof counts !== 'object') return;
            counters.querySelectorAll('[data-status-counter]').forEach((link) => {
                const raw = counts[link.dataset.statusCounter];
                if (raw === undefined) {
                    setHidden(link, true);
                    return;
                }
                if (!Number.isSafeInteger(raw) || raw < 0) return;
                setHidden(link, false);
                const label = `${link.dataset.label} — ${raw} sales`;
                const badge = link.querySelector('[data-count-badge]');
                badge.textContent = raw > 99 ? '99+' : String(raw);
                badge.classList.toggle('hidden', raw === 0);
                link.setAttribute('aria-label', label);
                link.querySelector('[data-count-tooltip]').textContent = label;
            });
            setHidden(counters, !Array.from(counters.querySelectorAll('[data-status-counter]')).some((link) => !link.hidden));
        } catch {
            // Keep the last successful counts when the connection is interrupted.
        } finally {
            clearTimeout(timeout);
            loading = false;
            if (queued) {
                queued = false;
                refreshCounts();
            }
        }
    }

    let timer = setInterval(refreshCounts, 60000);
    document.addEventListener('data-table:refresh', refreshCounts);
    document.addEventListener('sales:counts-refresh', refreshCounts);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshCounts();
    });
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        suspended = false;
        timer = setInterval(refreshCounts, 60000);
        refreshCounts();
    });
    window.addEventListener('pagehide', () => {
        suspended = true;
        clearInterval(timer);
        request?.abort();
    });
    refreshCounts();
}
