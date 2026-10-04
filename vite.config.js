
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
            'resources/js/pages/refunds.js',
                'resources/js/pages/refund-requests.js',
                'resources/js/pages/extra-received.js',
                'resources/js/components/due-payment.js',
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/ship-ticket-sales.js',
                'resources/js/pages/public-form.js',
                'resources/js/pages/companies.js',
                'resources/js/pages/ships.js',
                'resources/js/pages/ship-packages.js',
                'resources/js/pages/users.js',
                'resources/js/pages/roles.js',
                'resources/js/pages/permissions.js',
                'resources/js/pages/excel-settings.js',
                'resources/js/pages/sale-drafts.js',
                'resources/js/pages/cash-collections.js',
                'resources/js/pages/whatsapp.js',
                'resources/js/pages/ticket-issue.js',
                'resources/js/pages/verify-sales.js',
                'resources/js/pages/edit-sale.js',
                'resources/js/pages/reports-sales.js',
                'resources/js/pages/sale-status.js',
                'resources/js/layout/sidebar.js',
                'resources/js/layout/navigation.js',
            ],
            refresh: true,
        }),
    ],
});


