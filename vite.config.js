import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/ship-ticket-sales.js',
                'resources/js/pages/public-form.js',
                'resources/js/pages/reports.js',
                'resources/js/pages/pending-sales.js',
                'resources/js/pages/refund.js',
                'resources/js/pages/refunded-sales.js',
            ],
            refresh: true,
        }),
    ],
});
