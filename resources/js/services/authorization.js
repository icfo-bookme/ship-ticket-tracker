const context = document.getElementById('authorization-context');
const permissions = new Set(JSON.parse(context?.dataset.permissions || '[]'));
const isSuperAdmin = context?.dataset.superAdmin === '1';

export function can(permission) {
    return isSuperAdmin || permissions.has(permission);
}

export function applyPermissionVisibility(root = document) {
    const elements = [];

    if (root instanceof Element && root.hasAttribute('data-permission')) {
        elements.push(root);
    }

    const matches = root.querySelectorAll?.('[data-permission]') ?? [];
    elements.push(...matches);

    elements.forEach((element) => {
        if (!can(element.dataset.permission)) {
            element.remove();
        }
    });
}

export function initializePermissionVisibility() {
    applyPermissionVisibility();

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    applyPermissionVisibility(node);
                }
            });
        });
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
}
