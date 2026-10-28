export function initializeSalePackageFilter({ shipFilter, packageFilter, message, onChange }) {
    let request;

    function reset(label = 'Select a ship first') {
        request?.abort();
        request = null;
        packageFilter.replaceChildren(new Option(label, ''));
        packageFilter.value = '';
        packageFilter.disabled = true;
        packageFilter.setAttribute('aria-busy', 'false');
        message.textContent = '';
    }

    async function load() {
        reset(shipFilter.value ? 'Loading packages…' : 'Select a ship first');
        if (!shipFilter.value) return;
        const shipId = shipFilter.value;
        const controller = new AbortController();
        request = controller;
        packageFilter.setAttribute('aria-busy', 'true');
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const packages = [];
            let total;
            do {
                const url = new URL(packageFilter.dataset.packagesUrl.replace('__SHIP_ID__', encodeURIComponent(shipId)), window.location.origin);
                url.searchParams.set('length', '100');
                url.searchParams.set('start', String(packages.length));
                url.searchParams.set('order[0][column]', '1');
                url.searchParams.set('order[0][dir]', 'asc');
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok || response.redirected) throw new Error('Unable to load packages');
                const result = await response.json();
                if (!Array.isArray(result.data) || !Number.isSafeInteger(result.recordsFiltered) || result.recordsFiltered < 0) throw new Error('Invalid package response');
                total = result.recordsFiltered;
                if (result.data.length === 0 && packages.length < total) throw new Error('Incomplete package response');
                packages.push(...result.data);
            } while (packages.length < total);

            if (request !== controller || shipFilter.value !== shipId) return;
            packageFilter.replaceChildren(new Option('All Packages', ''));
            packages.forEach((item) => packageFilter.add(new Option(item.name, item.id)));
            packageFilter.disabled = packages.length === 0;
            message.textContent = packages.length === 0 ? 'No packages available for this ship.' : '';
        } catch {
            if (request !== controller) return;
            packageFilter.replaceChildren(new Option('Packages unavailable', ''));
            message.textContent = 'Could not load packages. Select the ship again to retry.';
        } finally {
            clearTimeout(timeout);
            if (request === controller) packageFilter.setAttribute('aria-busy', 'false');
        }
    }

    shipFilter.addEventListener('change', () => {
        load();
        onChange();
    });
    packageFilter.addEventListener('change', onChange);
    if (shipFilter.value) load();
    return { reset };
}
