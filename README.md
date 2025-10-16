<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Laravel Backend Assessment – Supplier Product Sync

Implements a clean, modular backend module to sync supplier products via CSV or API with a small admin UI.

### Features
- Products table with unique `sku` and fields for `incoming_stock`.
- Sync service supports CSV and API JSON feeds.
- Validation and error logging with duplicate SKU aggregation.
- Upsert logic (create new, update existing by `sku`).
- Artisan command `supplier:sync` and queued job to process feeds.
- Daily scheduling via env-configured source.
- Simple admin UI: list products, import CSV, fetch API.
- Sample CSV at `storage/app/sample/supplier_products.csv`.

### Setup
1. Configure database in `.env` (MySQL recommended):
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1` `DB_PORT=3306`
   - `DB_DATABASE=your_db` `DB_USERNAME=your_user` `DB_PASSWORD=your_pass`
   - For quick local testing, SQLite also works (default `database/database.sqlite`).
2. Migrate and seed:
   - `php artisan migrate`
   - `php artisan db:seed`
3. Optional scheduling (set one or both):
   - `SUPPLIER_CSV_PATH=storage/app/sample/supplier_products.csv`
   - `SUPPLIER_API_URL=https://supplier.example.com/feed`
   - `SUPPLIER_API_TOKEN=...`
4. Start dev server:
   - `php artisan serve`

### Usage
- Admin UI:
  - `http://127.0.0.1:8000/products` – list products
  - `http://127.0.0.1:8000/sync` – upload CSV or fetch API
- CLI:
  - CSV: `php artisan supplier:sync --csv="storage/app/sample/supplier_products.csv"`
  - API: `php artisan supplier:sync --api="https://supplier.example.com/feed" --token="..."`

### Structure
- Models: `App\Models\Product`
- Migrations: `create_products_table`
- Service: `App\Services\SupplierProductSyncService`
- Repository: `App\Repositories\ProductRepository`
- Job: `App\Jobs\ProcessSupplierFeed`
- Command: `App\Console\Commands\SupplierSyncCommand`
- Controllers: `ProductController`, `SupplierSyncController`
- Views: `resources/views/products/*`

### Notes
- Duplicate SKUs within a single feed are aggregated: last non-empty fields win, `incoming_stock` sums.
- Validation catches missing `sku` or `name`, and invalid `price` values.
- Queue driver defaults to `sync`; configure another driver if needed.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
