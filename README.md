# Decision-Delta

Decision-Delta is a role-based business analysis application with a Laravel API backend and a Next.js frontend.

## Project structure

```text
back-end/   Laravel 13 API, Sanctum authentication, SQLite database
front-end/  Next.js frontend with React and shadcn/ui
```

## Requirements

- PHP 8.3+
- Composer
- Bun 1.3+
- SQLite PHP extension

## Installation

### 1. Install backend dependencies

```bash
cd back-end
composer install
```

Create the local environment file and application key:

```bash
# macOS/Linux/Git Bash
cp .env.example .env
php artisan key:generate
```

On PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

The default backend configuration uses SQLite. Create the database file if it does not exist, then run migrations and seed the sample data:

```bash
php artisan migrate --seed
```

Start the API server:

```bash
php artisan serve
```

The API will be available at:

```text
http://localhost:8000/api
```

### 2. Install frontend dependencies

Open another terminal from the project root:

```bash
cd front-end
bun install
```

Optional: create `front-end/.env.local` if the API is not running at the default URL:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

Start the frontend:

```bash
bun run dev
```

The frontend will be available at:

```text
http://localhost:3000
```

## Seeded login accounts

All seeded users use this password:

```text
password
```

| Email | Role |
|---|---|
| `user@example.com` | No role |
| `sales@example.com` | Sales |
| `operations@example.com` | Operations |
| `finance@example.com` | Finance |
| `admin@example.com` | Admin |

Public registration can assign `sales`, `operations`, or `finance`. The `admin` role cannot be assigned through public registration.

## Seeded business data

### Products

| Product | Base price | Unit cost | Physical inventory | Safety stock | Install time |
|---|---:|---:|---:|---:|---:|
| Product 100 | 1,250.00 | 760.00 | 34 | 10 | 25 minutes/unit |
| Product 200 | 2,100.00 | 1,390.00 | 27 | 8 | 50 minutes/unit |
| Product 300 | 3,700.00 | 2,680.00 | 11 | 5 | 90 minutes/unit |

### Customers

Five customers are seeded for sales opportunity selection:

- Acme Corporation
- Northwind Traders
- Globex Industries
- Soylent Manufacturing
- Initech Solutions

### Opportunities

Five sample opportunities are seeded across the `New`, `Quoted`, and `Lost` stages. They are linked to products and can optionally be linked to customers through the sales API.

### Finance settings

The admin settings seeder creates defaults for:

- Target margin: `20.00%`
- Minimum operating cash: `10,000.00`
- Fixed shipping cost: `0.00`
- Per-unit shipping cost: `0.00`
- Available capacity: `40.00` hours
- Capacity info/risk/critical thresholds: `70.00%`, `85.00%`, and `100.00%`
- Inventory, cash, margin, and capacity alert toggles

## Useful API endpoints

All protected endpoints require a Sanctum bearer token.

```text
POST  /api/auth/register
POST  /api/auth/login
GET   /api/products
GET   /api/customers                 Sales role
GET   /api/opportunities              Sales role
POST  /api/opportunities              Sales role
PATCH /api/opportunities/{id}         Sales role
POST  /api/inventory-adjustments      Operations role
POST  /api/capacity-adjustments       Operations role
POST  /api/receipts                   Finance role
POST  /api/payments                   Finance role
POST  /api/expenses                   Finance role
GET   /api/cash-summary               Finance role
GET   /api/settings                   Admin role
PATCH /api/settings                   Admin role
GET   /api/dashboard                  Admin role
```

## Testing

### Backend tests

```bash
cd back-end
php artisan test
```

The backend test environment is isolated through `.env.testing` and uses an in-memory SQLite database.

### Frontend checks

```bash
cd front-end
bun run lint
bun run build
```
