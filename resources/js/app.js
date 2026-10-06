import './bootstrap';

import Alpine from 'alpinejs';
import { initializePermissionVisibility } from './services/authorization.js';

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePermissionVisibility, { once: true });
} else {
    initializePermissionVisibility();
}
