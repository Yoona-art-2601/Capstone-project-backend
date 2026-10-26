# Beacon Mart – Order and Inventory Management System

Capstone project: an online store and back office for **Beacon Mart**, a healthy food mart chain with branches in Klang and Shah Alam.

- **Backend (this repo):** plain PHP 8.2 REST-style JSON API + MariaDB, runs on XAMPP
- **Frontend:** React 19 (Create React App) – [Capstone-front](https://github.com/Yoona-art-2601/Capstone-front)

## Features

**Customers**
- Product grid per branch with live stock, promotions (with start/end dates), filters, sorting, product details and reviews
- Cart kept per branch, checkout with home delivery (fee by distance, OpenStreetMap geocoding) or self pickup, 6% SST, delivery/pickup time slots
- Payment by card (Stripe Checkout, test mode), cash on delivery or pay at store; unpaid card orders expire after 30 minutes and can be resumed with "Pay now"
- My Orders with order timeline, cancel unpaid orders, rate items after completion
- Notifications for order updates and back-in-stock alerts, My Account page with change password

**Admin**
- Orders with status flow (Pending → Processing → Out for Delivery / Ready for Pickup → Completed), automatic Stripe refund when a paid order is cancelled
- Inventory per branch (prices, promotions, images, descriptions, stock in/out, low-stock thresholds), stock transfers between branches
- Central warehouse: factory receipts and shipments to branches
- Dashboard with charts, sales history (10 per page) and CSV / PDF reports
- Admin alerts (new orders, cancellations, refunds, low stock) and a full audit trail

**Security**
- Password hashing, HttpOnly + SameSite session cookies, 2-hour idle logout
- Admin-only endpoints (`auth/check_admin.php`), admin registration code
- Failed login limit (5 per account / 20 per IP in 15 minutes), admin code limit, generic database error messages

## Requirements

| Tool | Version used |
|---|---|
| XAMPP (Apache + PHP + MariaDB) | PHP 8.2.12, MariaDB 10.4 |
| Node.js | 18 or newer (developed on 24) |
| Stripe account | test mode keys only |

PHP needs the `curl` and `pdo_mysql` extensions (enabled in XAMPP by default).

## Setup

### 1. Get the code

Both repos must sit in the XAMPP web folder with these exact names, because the frontend calls `http://localhost/beaconmart-project/backend`:

```
C:\xampp\htdocs\beaconmart-project\
├── backend\    ← this repo
└── frontend\   ← Capstone-front
```

```bash
cd C:/xampp/htdocs
mkdir beaconmart-project && cd beaconmart-project
git clone https://github.com/Yoona-art-2601/Capstone-project-backend.git backend
git clone https://github.com/Yoona-art-2601/Capstone-front.git frontend
```

### 2. Database

Start Apache and MySQL in the XAMPP Control Panel.

**New install:** open phpMyAdmin → Import → choose `database/schema.sql`. It creates the `beaconmart_db` database with all tables plus demo branches, products and stock (no users or orders). It refuses to run if the tables already exist, so it never overwrites data.

**Existing install:** import only the files in `database/` you have not run yet, in this order:

1. `users.sql`
2. `orders_user_id.sql`
3. `orders_address.sql`
4. `audit_logs.sql`
5. `seed_products.sql`
6. `orders_delivery_fee.sql`
7. `branches_info.sql`
8. `products_sale_price.sql`
9. `products_image_description.sql`
10. `products_descriptions.sql`
11. `phase1_checkout.sql`
12. `phase2_order_flow.sql`
13. `phase3_reviews.sql`
14. `phase4_promo_dates.sql`
15. `phase5_notifications.sql`
16. `phase6_warehouse.sql`
17. `fix_notification_links.sql`
18. `security_login_attempts.sql`

The database connection is in `config/database.php` (`root`, no password, the XAMPP default).

### 3. Secret config files (not in git)

```bash
cp config/stripe.example.php config/stripe.php
cp config/admin.example.php config/admin.php
```

- `config/stripe.php`: put your Stripe **test** secret key (`sk_test_...`, from the Stripe dashboard → Developers → API keys)
- `config/admin.php`: choose your own admin registration code

Never commit these two files (they are in `.gitignore`).

### 4. Frontend

```bash
cd ../frontend
npm install
npm start
```

The store opens at http://localhost:3000 and the admin panel at http://localhost:3000/admin.

### 5. Create the first admin

Customers register on the store page. Admins are created through the API with the code from `config/admin.php` (Postman or curl):

```bash
curl -X POST http://localhost/beaconmart-project/backend/auth/register_admin.php \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","email":"admin@example.com","password":"at-least-8-chars","admin_code":"YOUR_CODE"}'
```

### 6. Test payment

Use Stripe's test card `4242 4242 4242 4242`, any future expiry date and any CVC. No real money is charged in test mode.

## Product photos

Product photos in `uploads/products/` are not stored in git. `schema.sql` therefore sets `image_url` to empty and the store shows category icons instead. An admin can upload photos in **Inventory → Details** (JPG, PNG, WebP or GIF, max 3 MB).

## Project structure

| Folder | Contents |
|---|---|
| `auth/` | login, logout, me, register customer/admin, change password, admin check |
| `products/` | list, add, update, soft delete, upload image |
| `stock/` | stock in/out, thresholds, low stock, transfers between branches |
| `orders/` | admin order list and status changes, customer orders and cancel |
| `payment/` | create checkout (Stripe / COD / pay at store), confirm, cancel, resume payment |
| `shop/` | public settings, time slots, checkout quote |
| `warehouse/` | warehouse stock, factory receipts, shipments to branches |
| `stats/` | dashboard, sales summary, best sellers, sales history (CSV) |
| `reviews/`, `notifications/`, `audit/`, `branches/` | reviews, notifications, audit trail, branch list |
| `config/` | database, session, security, shop rules, checkout, payments, geocoding, notifications |
| `database/` | `schema.sql` and incremental migrations |
| `uploads/` | product photos (scripts blocked by `.htaccess`) |

## Troubleshooting

| Problem | Fix |
|---|---|
| "Cannot connect to the database" | Start MySQL in XAMPP and check that `beaconmart_db` exists |
| "Too many failed attempts" | Wait 15 minutes, or empty the `login_attempts` table in phpMyAdmin |
| Payment page error | Check the Stripe test key in `config/stripe.php` |
| Delivery fee cannot be calculated | The address lookup needs internet access (OpenStreetMap) |
| CORS or login not kept | Open the store at `http://localhost:3000`, not another host name |

## Tests

```bash
cd ../frontend
npm test
```
