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
    const data = await response.json();
    if (!response.ok) throw data;
    return data;
}

export function refreshDataTable(tableId) {
    document.dispatchEvent(new CustomEvent('data-table:refresh', { detail: { tableId } }));
}
