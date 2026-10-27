const history = document.getElementById('saleAuditHistory');

if (history) {
    const content = history.querySelector('[data-audit-content]');
    const refresh = history.querySelector('[data-audit-refresh]');
    let currentRequest;

    async function loadHistory(url) {
        currentRequest?.abort();
        const request = new AbortController();
        currentRequest = request;
        content.setAttribute('aria-busy', 'true');
        refresh.disabled = true;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
                signal: request.signal,
            });

            if (!response.ok || response.redirected) throw new Error('Unable to load history');
            const html = await response.text();
            if (request.signal.aborted) return;
            content.innerHTML = html;
        } catch (error) {
            if (error.name === 'AbortError') return;
            content.textContent = 'History could not be loaded. Select Refresh history to try again.';
        } finally {
            if (currentRequest === request) {
                content.setAttribute('aria-busy', 'false');
                refresh.disabled = false;
            }
        }
    }

    refresh.addEventListener('click', () => loadHistory(history.dataset.url));
    content.addEventListener('click', (event) => {
        const link = event.target.closest('[data-audit-page]');
        if (!link) return;
        event.preventDefault();
        loadHistory(link.href);
    });
    loadHistory(history.dataset.url);
}
