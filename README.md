# Ship Ticket Tracker

A Laravel application for managing ship ticket sales, ticket fulfilment, refunds, and financial reports.

## Features

- Sales with departure and return ticket categories, quantities, discounts, fees, and co-passengers.
- Payments, payment proofs, due collection, BFTN settlement, and refund credit adjustments.
- Ticket issuing, PDF grouping, printing, courier shipment, and office collection, including Cox's Bazar office.
- Partial and bulk refund requests, partner approval, customer payment details, and refund completion.
- Company error refunds and extra payment refunds.
- Sales, ticket issued, refund, company loss, and BFTN reports.
- Roles, permissions, audit history, and shared sale details modals.

## Technology

Laravel 12, PHP 8.2+, Blade, Alpine.js 3, Tailwind CSS 3, Vite 7, Pest 3, and Spatie Laravel Permission.

Use Composer and a Node.js version supported by Vite 7, with npm. The application is configured for MySQL in `.env.example`; automated feature tests use in-memory SQLite.

## Local setup

Run these commands from the project directory:

```sh
composer install
```

Copy `.env.example` to `.env`. In PowerShell:

```powershell
Copy-Item .env.example .env
```

Set `APP_NAME`, `APP_URL`, and the `DB_*` values for your own local database. Generate a fresh application key for a new installation:

```sh
php artisan key:generate --no-interaction
php artisan migrate --no-interaction
php artisan db:seed --class=RolePermissionSeeder --no-interaction
php artisan user:make-admin admin@example.com --no-interaction
npm install
npm run build
```

The admin command creates a user if needed and prints a generated password for a new account. For an existing account, it assigns the Admin role without changing the password.

Start the application, queue listener, and Vite development server together:

```sh
composer run dev
```

Alternatively, use `php artisan serve --no-interaction` with the built frontend assets. On PowerShell installations that block `npm.ps1`, use `npm.cmd` in place of `npm`.

Add ships, ticket categories/packages, companies, and sales sources through the administration pages before entering sales. Default roles and permissions are defined in [config/roles.php](config/roles.php).

## Sales and collection

A sale can contain separate Departure and Return category rows. Payments and pending BFTN amounts contribute to the outstanding due calculation. Effective refund due adjustments reduce the remaining due.

Delivery options are courier delivery, office collection, and Cox's Bazar office collection. Only one office can be selected. Courier delivery requires an address.

Ticket fulfilment includes payment verification, ticket issuing, printing, parcel creation/shipping, or collection from the selected office. Access to each action and status list is controlled by permissions.

## Refund workflow

1. Select an eligible sale on **Refundable Sales** (`/refunds/create`).
2. Submit a partial request for selected categories, or select sales for bulk full refund requests.
3. Approve the request in **Requested Refunds**.
4. Add customer refund payment details in **Partner Approved Refunds**.
5. Complete customer settlement in **Refunds Ready for Payment**, or use eligible refund credit on another sale.
6. View completed requests under **Completed Refunds** (`/refunded`) and inspect financial totals in the refund report.

After a ticket refund is completed, a sale with remaining tickets appears again in Refundable Sales. Completed Refunds also offers **Refund Remaining Tickets**. Each submission creates a separate request for the same sale. While a ticket request is still open, edit that request instead of creating another. Previously requested sales cannot be selected for bulk full refunds. Remaining quantities exclude non-cancelled ticket requests; extra payment refunds do not consume tickets. Historical requests with incomplete category breakdowns require correction before another ticket request can be made.

### Refund records

| Table | Purpose |
| --- | --- |
| `refunds` | Main request, calculated amounts, status, and workflow timestamps. |
| `refund_request_tickets` | Category and Departure/Return quantities selected for a request. |
| `refund_customer_payments` | Actual customer refund payments. |
| `payments` | Sale payments, including refund credit applied to another sale. |
| `company_error_refunds` | Company error and loss information for a refund. |
| `audit_logs` | Recorded changes and workflow actions. |

`refund_partner_payments` has a model and relationship, but the current refund workflow does not create partner payment records. The `partner_received_at` timestamp is currently set when a request is approved.

## Integrations

- Google Sheets appends sale information; Google Drive supplies ticket PDFs. These services use a Google service account file at `storage/app/google/service-account.json` and their configured application settings.
- Steadfast handles courier operations. Configure its integration settings before creating parcels.
- The public booking success page queries a separate `bookme` database connection configured with `DB_SECOND_*` values in [config/database.php](config/database.php).

Keep local credentials and service account files outside version control. Integration-dependent operations require access to their configured services.

## Tests and formatting

Run an affected test file during development:

```sh
php artisan test tests/Feature/RefundListingServiceTest.php
```

Run the complete PHP suite:

```sh
composer test
```

JavaScript behaviour tests can also be run with Node:

```sh
node --test tests/Feature/SalePackageFilter.test.mjs
```

Format changed PHP files and build frontend assets:

```sh
php vendor/bin/pint --dirty
npm run build
```

The PHP test configuration is in [phpunit.xml](phpunit.xml). Test coverage lives in [tests/Feature](tests/Feature) and [tests/Unit](tests/Unit).
