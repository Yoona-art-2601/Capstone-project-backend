# Testing

## API tests (Postman)

The collection `postman/collections/Beacon Mart API.postman_collection.json` checks the backend end to end. It only reads data and sends invalid requests on purpose, so it does not create products, orders or users. The only records it leaves are the admin login and logout in the audit trail, one failed login for `nobody@example.test` and one password reset request for a random email that does not exist. Run it at most a few times in 15 minutes, because forgot password allows 10 requests per IP.

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
| 2. Access control without login | Admin, dashboard, user management, checkout quote and attendance return 401; login rejects a bad email format and unknown accounts; forgot password gives the same answer for any email; a fake reset link is rejected |
| 3. Admin | Login and session, orders paging (10 per page) and search, sales summary, dashboard, sales history and CSV, low stock, warehouse, audit trail, users, attendance QR code (6 characters, under 2 minutes) and records, pickup quote (SST and total), validation errors for postcode, negative price, unknown stock change, bad email, staff without branch, branch closing before opening, logout ends the session |

Every request also checks that the response arrives in under 5 seconds.

### Last run

| Date | Requests | Assertions | Failed | Average response |
|---|---|---|---|---|
| 13 Nov 2026 | 35 | 144 | 0 | 20 ms |
| 21 Nov 2026 | 38 | 157 | 0 | 42 ms |

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
| Staff account | Sees only Orders, Inventory and Incoming stock of their branch, other branches and admin pages return 403 |
| Disabled account | Current session ends at once and login is refused |
| Attendance QR | Staff clock in with the current code, wrong or expired codes are refused, late minutes follow the branch opening hours |
| Branch edit | New opening days and hours change the time slots; a new address is located on the map automatically |
| Incoming stock (staff) | Staff see and receive only shipments for their branch, cannot cancel them, branch stock goes up on receipt |
| Forgot password | Reset email saved in mail_outbox, link works once, old password stops working, unknown emails get the same answer |
| Two accounts in one browser | The admin panel notices the other login and asks to log in again |
