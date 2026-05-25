# SportWears 3ON E-Commerce Agent Guide

This guide is specific to the current repository. Future agents must read the local codebase first and must not replace these rules with external Bagisto or Laravel assumptions when the existing files show a different pattern.

## 1. Project Context & Goals

SportWears 3ON is a PHP/Laravel e-commerce project built on the Bagisto platform. The repository is a modular Laravel application with Bagisto core modules kept under `packages/Webkul`, project-level glue code in `app` and `routes`, and custom business integrations under `packages/Triatek`.

The current architecture is:

- `app/` contains application-level providers, controllers, middleware, and console commands.
- `routes/web.php` contains project-level routes for Indonesian region lookup, Midtrans payment redirection, and a fast stock sync endpoint.
- `packages/Webkul/` contains Bagisto modules such as Admin, Shop, Sales, Product, Checkout, Payment, Shipping, Inventory, Theme, and related modules.
- `packages/Triatek/` contains custom modules:
  - `Midtrans` for payment integration.
  - `RajaOngkir` for shipping rate calculation.
  - `RajaOngkirTest` as a disabled/test package.
  - `MultiChannelSync` for Shopee and TikTok Shop synchronization.
- `resources/css/app.css` and `resources/js/app.js` are the Vite entry points configured in `vite.config.js`.

The two highest-priority project goals are:

1. Product uploads integrated directly with the Shopee API and TikTok Shop API.
   - Product create/update events must flow from Bagisto into `Triatek\MultiChannelSync`.
   - Marketplace API calls belong in `ShopeeService` and `TikTokService`.
   - API work must be dispatched through jobs such as `SyncProductToShopeeJob`, `SyncProductToTikTokJob`, `UpdateProductOnShopeeJob`, and `UpdateProductOnTikTokJob`.

2. Automatic stock integration and synchronization whenever a purchase occurs on any platform, with Bagisto as the core omnichannel hub.
   - Bagisto product IDs must map to marketplace product IDs through `channel_products`.
   - Stock changes must be logged through `channel_stock_logs`.
   - Marketplace orders must be tracked through `channel_orders`.
   - Stock synchronization must use `StockSyncListener`, `SyncStockToShopeeJob`, and `SyncStockToTikTokJob`.

## 2. Tech Stack & Dependencies

Runtime and platform observed in this repository:

- PHP: `^8.2` in `composer.json`; local runtime reported by Artisan is `8.2.12`.
- Laravel Framework: `^11.0` in `composer.json`; locked version is `11.44.2`.
- Bagisto platform: project package is `bagisto/bagisto`, with Webkul modules present under `packages/Webkul`.
- Composer: local runtime reported by Artisan is `2.8.9`.
- Frontend build tool: Vite through `vite.config.js`.

Important Composer dependencies from the current project:

- `laraditz/shopee`: declared `*`, locked `1.1.7`.
- `laraditz/tiktok`: declared `*`, locked `1.1.4`.
- `midtrans/midtrans-php`: declared `^2.6`, locked `2.6.2`.
- `prettus/l5-repository`: declared `^2.6`, locked `2.10.1`.
- `laravel/sanctum`: declared `^4.0`, locked `4.0.8`.
- `laravel/octane`: declared `^2.3`, locked `2.8.2`.
- `bagisto/rest-api`: declared `dev-master`, locked `dev-master 5e4e18c`.
- `bagisto/image-cache`: declared `dev-master`, locked `dev-master b5a24e8`.
- `bagisto/bagisto-package-generator`: declared `*`, locked `2.1.2`.
- `maatwebsite/excel`: declared `^3.1.46`, locked `3.1.64`.
- `barryvdh/laravel-dompdf`: declared `^2.0.0`, locked `2.2.0`.
- `mpdf/mpdf`: declared `^8.2`, locked `8.2.5`.
- `openai-php/laravel`: declared `^0.10.1`, locked `0.10.2`.

Development dependencies:

- `laravel/pint`: declared `^1.19`, locked `1.21.2`.
- `pestphp/pest`: declared `^3.0`, locked `3.7.4`.
- `pestphp/pest-plugin-laravel`: declared `^3.0`, locked `3.1.0`.
- `phpunit/phpunit`: declared `^11.0`, locked `11.5.3`.
- `barryvdh/laravel-debugbar`: declared `^3.8`, locked `3.15.2`.

Node dependencies:

- `axios`: declared `^1.7.9`, locked `1.13.5`.
- `laravel-vite-plugin`: declared `^1.0`, locked `1.3.0`.
- `vite`: declared `^5.4.12`, locked `5.4.21`.

## 3. Architecture Rules

Use the existing directory responsibilities:

- Put application-specific controllers, commands, middleware, and app providers under `app`.
- Put business integrations and reusable custom modules under `packages/Triatek/<PackageName>/src`.
- Treat `packages/Webkul` as Bagisto core modules. Do not add new feature work there when the same behavior can be implemented through `packages/Triatek`, service providers, config merging, events, routes, or views.
- Keep Composer PSR-4 namespaces aligned with `composer.json`:
  - `App\` -> `app/`
  - `Triatek\Midtrans\` -> `packages/Triatek/Midtrans/src`
  - `Triatek\RajaOngkir\` -> `packages/Triatek/RajaOngkir/src/`
  - `Triatek\MultiChannelSync\` -> `packages/Triatek/MultiChannelSync/src/`
  - `Webkul\...` -> `packages/Webkul/.../src`

For custom packages, follow the folder patterns already present:

- `Providers/` for service provider registration.
- `Config/` for package config arrays merged into Bagisto/Laravel config.
- `Services/` for external APIs and integration logic.
- `Jobs/` for queue work.
- `Listeners/` for Bagisto/Laravel event handling.
- `Events/` for custom event classes.
- `Models/` for Eloquent models.
- `Database/migrations/` for package-owned tables.
- `Http/Controllers/` for package-owned HTTP handlers.

Logic separation rules:

- External marketplace calls must stay inside service classes such as `ShopeeService` and `TikTokService`.
- Queue jobs must orchestrate service calls and failure handling, not contain full API implementations.
- Event listeners must react to Bagisto events and dispatch jobs.
- Eloquent models such as `ChannelProduct` must own mapping helpers like `findMapping`, `markSynced`, and `markFailed`.
- Use Webkul repositories when interacting with Bagisto domain models where repositories already exist, as shown by `CancelOrderCommand` using `Webkul\Sales\Repositories\OrderRepository`.
- For custom payment and shipping methods, extend the existing Bagisto base abstractions:
  - `Triatek\Midtrans\Payment\Midtrans` extends `Webkul\Payment\Payment\Payment`.
  - `Triatek\RajaOngkir\Carriers\RajaOngkir` extends `Webkul\Shipping\Carriers\AbstractShipping`.

Routing rules:

- Project-level routes currently live in `routes/web.php`.
- Current project-level routes are `/indo-region/provinces`, `/indo-region/cities/{code}`, `/midtrans/pay`, and `/fast-stock-sync`.
- `Triatek\MultiChannelSync\Http\Controllers\WebhookController` defines Shopee and TikTok webhook handlers, and `multichannel.php` defines webhook paths. If those webhooks are exposed, register routes explicitly and keep signature verification through `verifyShopeeSignature` and `verifyTikTokSignature`.
- Do not create hidden or duplicate routes for the same integration.

## 4. Coding Standards

Formatting is defined by `.editorconfig` and `pint.json`:

- Use UTF-8.
- Use LF line endings.
- Use 4 spaces for indentation.
- Insert a final newline.
- Trim trailing whitespace except in Markdown.
- Use 2 spaces for YAML files, except `docker-compose.yml` uses 4 spaces.
- Use Laravel Pint preset `laravel`.
- Keep `=>` alignment according to the existing Pint rule.

Naming conventions shown in the repository:

- Namespaces and classes use PascalCase, for example `Triatek\MultiChannelSync\Services\ShopeeService`.
- Methods use camelCase, for example `createProduct`, `updateProduct`, `syncStock`, `getRedirectUrl`, `findMapping`, and `markSynced`.
- Variables use camelCase, for example `$bagistoProductId`, `$channelProductId`, `$snapToken`, `$shippingAddress`, and `$newQty`.
- Config keys and database columns use snake_case, for example `channel_product_id`, `last_synced_at`, `partner_key`, and `sandbox_mode`.
- Queue names and channels use lowercase string values such as `marketplace-sync`, `shopee`, and `tiktok`.

Implementation style:

- Prefer typed return values where the existing code uses them, such as `register(): void`, `boot(): void`, `createProduct(): array`, and `syncStock(): bool`.
- Use constructor property promotion for injected dependencies where existing code does, such as `CancelOrderCommand` and `OnepageController`.
- Use Laravel facades consistently with the current files: `Event`, `Log`, `Http`, `Cache`, `Cart`, `Payment`, `Shipping`, and marketplace facades.
- Use concise comments only when they explain business logic or integration behavior.

## 5. Do's and Don'ts

Do:

- Register custom functionality through service providers, as done by `MidtransServiceProvider`, `RajaOngkirServiceProvider`, and `MultiChannelSyncServiceProvider`.
- Merge package config with `mergeConfigFrom`, as done for payment methods, shipping carriers, system config, and multichannel config.
- Use `Event::listen` for Bagisto product and order hooks, as done in `MultiChannelSyncServiceProvider`.
- Dispatch marketplace sync work onto the configured `marketplace-sync` queue instead of calling Shopee or TikTok APIs directly inside admin/product save requests.
- Store marketplace mapping state in `channel_products`.
- Store stock sync results in `channel_stock_logs`.
- Store marketplace order payloads in `channel_orders`.
- Use `core()->getConfigData(...)` and environment fallback for admin-configurable credentials, following the Midtrans and RajaOngkir implementations.
- Keep payment behavior inside `Triatek\Midtrans\Payment\Midtrans` and payment config inside `packages/Triatek/Midtrans/src/Config`.
- Keep shipping behavior inside `Triatek\RajaOngkir\Carriers\RajaOngkir` and shipping config inside `packages/Triatek/RajaOngkir/src/Config`.
- Put new multichannel API logic inside `Triatek\MultiChannelSync\Services`.
- Put new multichannel async tasks inside `Triatek\MultiChannelSync\Jobs`, one class per file.
- Put new multichannel event reactions inside `Triatek\MultiChannelSync\Listeners`.
- If a custom view is required, place it in `resources/views` or in a package `Resources/views` folder loaded by a service provider; do not edit Webkul Blade files directly.

Don't:

- Do not add new feature logic directly into `packages/Webkul` when a provider, config merge, route, listener, service, job, or app-level controller can be used.
- Do not modify Bagisto core views under `packages/Webkul/*/src/Resources/views` for visual overrides.
- Do not put Shopee or TikTok API calls in controllers, routes, or listeners; use `ShopeeService`, `TikTokService`, and jobs.
- Do not bypass `ChannelProduct` mapping helpers when syncing products or stock.
- Do not create marketplace order records without preserving the raw webhook payload.
- Do not expose marketplace webhook routes without signature verification.
- Do not store API secrets inside source files. Use `.env`, package config, and Bagisto admin config fields.
- Do not create another catch-all stock sync route if the existing `catalog.product.update.after` event flow can handle the change.
- Do not add multiple classes to one PHP file for PSR-4 autoloaded classes; follow the current `Jobs` structure.
- Do not rely on external assumptions about Bagisto internals; verify the actual class, provider, route, config, and view paths in this repository first.
