# UrbanEats Cloud Kitchen Platform

UrbanEats is a web-based cloud kitchen system developed using HTML, CSS, PHP, and MySQL.

## Technologies Used
- HTML
- CSS
- PHP 8
- MySQL
- XAMPP

## Main Features
- Customer registration and login
- Administrator-managed Kitchen Staff and Administrator accounts
- Browse food items
- Public menu with food categories, search, sorting, and pagination
- Add, edit, and delete food items; update prices, upload food images, and manage menu availability
- Track inventory quantities and low-stock thresholds, review out-of-stock items, and view stock movement history
- Add items to cart and place orders
- Track order status
- Manage users
- View sales reports

## Installation
1. Install and open XAMPP.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Create the database `urbaneats_db`.
5. Import the project SQL file.
6. Configure the database connection in `config/db.php`.
7. Open the project in your browser. The application detects its URL path from its location under Apache's document root, so the folder name can differ between team members.

Kitchen Staff and Administrators can manage food items from the kitchen dashboard. Food image uploads accept JPG, PNG, and WebP files up to 5 MB; the web server must be able to create files under `public/images/menu/`.

For an existing database, run `database/menu_migration.sql` against `urbaneats_db` to add the public menu table and sample dishes without replacing existing application data.

For an existing installation, run `database/inventory_migration.sql` once after the menu migration to add stock quantities and the inventory movement ledger. Existing dishes start at zero stock; record their opening quantities from the Kitchen **Inventory** page.

For ordering, payment and tracking, run `database/orders_migration.sql` after the menu and inventory migrations. This adds the `orders`, `order_items`, `payments`, `order_status_history`, and `coupons` tables. Customers can add dishes from the public menu, complete a transactional checkout, and track orders; Kitchen Staff can process the order queue; Administrators can review date-filtered sales reports.

## Run the Project
Open:

`http://localhost/urbaneats/UrbanEats-Cloud-Kitchen/`

If you cloned the project into a different folder, replace `urbaneats/UrbanEats-Cloud-Kitchen` with that folder's path under `htdocs`.

Public registration creates Customer accounts only. After signing in, an Administrator can create Kitchen Staff and additional Administrator accounts from **Users & Roles**. All account passwords are stored as password hashes, and Customer, Kitchen Staff, and Administrator accounts use the same sign-in page.

The SQL dump does not include a default Administrator account. Provision the first Administrator through your trusted database setup process; after that, use **Users & Roles** to create staff accounts.

After signing in, each role is sent to its own dashboard: Customers use `customer/dashboard.php`, Kitchen Staff use `kitchen/dashboard.php`, and Administrators use `admin/dashboard.php`.
