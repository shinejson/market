# MarketHub — Phase 1

Multi-tenant marketplace SaaS: Laravel API + Angular storefront, seller console, and super-admin.

## Stack

- Backend: Laravel 12, Sanctum, SQLite (swap to MySQL in `.env`)
- Frontend: Angular 19 standalone components
- Payments: `PaymentGateway` interface with a mock driver

## Demo accounts

Password for all: `password`

- Customer: `customer@markethub.test`
- Seller (Northstar): `seller1@markethub.test`
- Seller (Kente Home): `seller2@markethub.test`
- Super admin: `admin@markethub.test`

## Run locally

```bash
# Backend
cd backend
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000

# Frontend
cd frontend
npm install
npm start -- --host 0.0.0.0 --port 4200
```

The Angular dev server proxies `/api` and `/storage` to the Laravel app.

## Phase 1 coverage

- Tenant registration, stores, categories, products, variants, images, inventory
- Marketplace search/filter, multi-store cart, checkout split (master + seller orders)
- Mock payment intent + signed webhook + inventory reservation
- Seller fulfilment state machine and dashboard
- Super admin tenant approval, metrics, audit log
- Row-level `tenant_id` isolation (Eloquent global scope + policies)
