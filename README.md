# Batlee Housing Solution – Online Shop

## Live demo (front-end only)

The `docs/` folder is a complete **front-end version** of the shop (HTML, CSS and JavaScript only, no PHP and no SQL). It runs on GitHub Pages:

**https://gogoiarnab57-design.github.io/Batlee-housing-/**

It has the same pages as the full shop: products, cart, checkout, payment options, customer login and account, and the admin panel (`@admin` / `12345`). Because there is no server, everything (products, orders, accounts) is saved only in the visitor's own browser. Use it to show the design; for a real shop that takes orders from many customers, use the PHP + MySQL version in `public_html/`.

To run it on your own computer, open `docs/index.html` in a browser.

---

A PHP + MySQL e-commerce site for bathroom products, kitchen accessories, hardware and home utilities.
Customers browse, add to cart, enter delivery details and choose a payment option. The admin manages everything from the Admin Panel.

## What is in this package

| Folder | What it is |
|---|---|
| `public_html/` | The website. Upload the **contents** of this folder to your hosting's web root. |
| `database/schema.sql` | The database tables and starting data. Import it once, then **do not upload it to the website**. |

## Setup on shared hosting (cPanel / Hostinger / GoDaddy etc.)

1. In your hosting panel create a **MySQL database** and a **database user** with a password. Give the user full access to that database.
2. Open **phpMyAdmin**, select the new database, choose **Import**, and upload `database/schema.sql`.
3. Open `public_html/includes/config.php` and fill in `DB_NAME`, `DB_USER`, `DB_PASS` (and `DB_HOST` if your host says it is not `localhost`).
4. Upload everything inside `public_html/` to your site's web root (usually `public_html` on the server). Keep the folder structure and the hidden `.htaccess` files.
5. Make sure the `uploads/` folder on the server is writable (permission 755, or 775 if your host needs it).
6. Open your site, then go to **Login** and sign in as admin (below). **Change the password immediately.**
7. Install an SSL certificate (free "Let's Encrypt" in most hosting panels). Then remove the `#` in front of the three HTTPS lines in `.htaccess` to force secure connections.

### Trying it on your own computer first
Install XAMPP (Windows/Mac), start Apache + MySQL, create a database in phpMyAdmin, import `schema.sql`, copy `public_html` into `htdocs/batlee`, edit `config.php` (user `root`, empty password) and open `http://localhost/batlee/`.

## First login

- **Username:** `@admin`
- **Password:** `12345`

The dashboard shows a warning until you change it. Go to **Admin Panel → My login & password** to change both username and password (new passwords must be at least 8 characters).

## What the admin can do

- **Products** – add/edit/hide/delete, price, old price (MRP shows a % off), stock, several photos.
- **Categories** – the four starting categories can be renamed, and new ones added.
- **Orders** – full customer name, phone, address, optional GPS location (opens in Google Maps), items, payment choice. Update order status and payment status. Cancelling an order puts the items back in stock.
- **Sales records** – any date range, day-by-day totals, top products, and **CSV download** for Excel.
- **Reviews** – customer reviews wait for your approval before they appear on the site.
- **Helpline messages** – messages customers send from the Helpline box.
- **Site & contact settings** – shop name, home page text, helpline phone, WhatsApp number, email, address, hours, delivery charge / free-delivery limit, UPI ID, and which payment options show. Changes appear on the site immediately.

## How the customer side works

Browse → add to cart (totals are calculated by the server from the database prices) → checkout (name, contact number, address, landmark, city, state, PIN, optional "share my location") → payment page → order placed. Customers can create an account to see their own order history and write reviews.

## Payment (no gateway connected)

As requested, the payment page only offers options: **Cash on Delivery, UPI, Card, Net Banking**. Nothing is charged online.
- The chosen option is saved with the order; the admin marks it **Paid** once money is received.
- UPI shows the UPI ID you set in settings and lets the customer enter a reference number.
- The site **never asks for or stores card numbers or bank passwords** – this is deliberate and keeps you safe from card-data rules. When you later want real card/net-banking payments, connect a gateway (Razorpay, PayU, Cashfree, etc.) at that point.

## Security – what is built in

- Passwords are stored hashed (bcrypt), never as plain text. Login is rate-limited (5 wrong tries → 15-minute lock).
- All database access uses prepared statements (protects against SQL injection). All output is escaped (protects against XSS).
- Every form has a CSRF token. Logout is also protected.
- Customer and payment details are only readable by the admin account. Customers can only open their own orders. Admin pages are blocked for everyone else and are never cached. Admin is logged out after 30 minutes of inactivity.
- Image uploads are checked and **re-encoded** so hidden code cannot be smuggled in; the `uploads/` folder cannot run scripts.
- `includes/` is blocked from the web; security headers (CSP, anti-clickjacking, etc.) are sent on every page; error details are hidden from visitors.

**About "hiding the code":** the PHP (and SQL) code runs on the server and visitors can never see it. The page's HTML, CSS and browser JavaScript can always be viewed by anyone in their browser – that is true of every website and cannot be hidden – so nothing secret is kept there.

## Good habits for the owner

1. Change the admin password on day one; use a long, unique password.
2. Turn on HTTPS (step 7). Without it, passwords travel unencrypted.
3. Take regular **database backups** (phpMyAdmin → Export) and keep the `uploads/` folder backed up.
4. Keep PHP updated through your hosting panel (PHP 8.0 or newer is required).
5. Delete the sample products before launch and add your own.
6. If your host uses **Nginx** instead of Apache, the `.htaccess` files do nothing; ask the host to deny web access to `includes/` and to disable script execution in `uploads/`.

## Requirements
PHP 8.0+ with PDO MySQL and GD (standard on almost all hosts), MySQL 5.7+/MariaDB 10.3+.

## Note on testing
The full flow (register, cart, checkout, payment choice, orders, admin, uploads, reviews, helpline, lockout, CSRF, privacy) was tested on PHP 8.3 using an equivalent local database. Please do a quick test order on your real MySQL hosting after setup before going live.
