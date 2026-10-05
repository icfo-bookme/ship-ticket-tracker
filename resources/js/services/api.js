export async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            ...options.headers,
        },
    });
    const contentType = response.headers.get('content-type') || '';
    let data;

    if (contentType.includes('application/json')) {
        try {
            data = await response.json();
        } catch {
            data = null;
        }
    } else {
        data = await response.text();
    }

    if (!response.ok) {
        if (data && typeof data === 'object') {
            throw data;
        }

        throw {
            message: `Request failed (${response.status}). Please try again.`,
            status: response.status,
        };
    }

    if (data === null || typeof data !== 'object') {
        throw { message: 'The server returned an unexpected response.' };
    }

    return data;
}

export function refreshDataTable(tableId) {
    document.dispatchEvent(new CustomEvent('data-table:refresh', { detail: { tableId } }));
}
