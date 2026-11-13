# Testing

## API tests (Postman)

The collection `postman/collections/Beacon Mart API.postman_collection.json` checks the backend end to end. It only reads data and sends invalid requests on purpose, so it does not create products, orders or users. The only records it leaves are the admin login and logout in the audit trail and one failed login for `nobody@example.test`.

### Run in the Postman app

1. Import the collection and `postman/environments/Beacon Mart local.postman_environment.json`.
2. Select the **Beacon Mart local** environment and fill in `adminEmail` and `adminPassword` with an admin account.
3. Open the collection and choose **Run**.

### Run from the command line (Postman CLI)

```bash
cd postman
postman collection run "collections/Beacon Mart API.postman_collection.json" -e "environments/Beacon Mart local.postman_environment.json" --env-var "adminEmail=you@example.com" --env-var "adminPassword=your-password"
```

### What is covered

| Folder | Checks |
|---|---|
| 1. Public store | Shop settings (6% SST, delivery rules), branches with opening hours, products with price and stock, time slots, reviews |
| 2. Access control without login | Admin, dashboard, user management, checkout quote and attendance return 401; login rejects a bad email format and unknown accounts |
| 3. Admin | Login and session, orders paging (10 per page) and search, sales summary, dashboard, sales history and CSV, low stock, warehouse, audit trail, users, attendance QR code (6 characters, under 2 minutes) and records, pickup quote (SST and total), validation errors for postcode, negative price, unknown stock change, bad email, staff without branch, logout ends the session |

Every request also checks that the response arrives in under 5 seconds.

### Last run

| Date | Requests | Assertions | Failed | Average response |
|---|---|---|---|---|
| 13 Nov 2026 | 35 | 144 | 0 | 20 ms |

## Frontend unit tests

```bash
cd ../frontend
npm test
```

`src/cartStorage.test.js` checks the per-branch cart, the old cart format, rebuilding a cart against current stock and Malaysian phone number validation (6 tests).

## Manual test cases

These flows were tested by hand in the browser with test accounts and Stripe test mode:

| Flow | Expected result |
|---|---|
| Card payment with test card 4242 4242 4242 4242 | Order becomes Pending and Paid, stock is deducted once even if the success page is opened twice |
| Item sells out before the card payment arrives | Order is cancelled and the payment is refunded automatically |
| Unpaid card order older than 30 minutes | Order is cancelled automatically, the customer can no longer pay |
| Admin cancels a paid card order | Stock returns and the payment is refunded through Stripe |
| Cash on delivery or pay at store | Stock is deducted when the order is placed, payment is marked Paid when completed |
| 5 wrong passwords | Login is blocked for 15 minutes |
| Staff account | Sees only Orders and Inventory of their branch, other branches and admin pages return 403 |
| Disabled account | Current session ends at once and login is refused |
| Attendance QR | Staff clock in with the current code, wrong or expired codes are refused, late minutes follow the branch opening hours |
