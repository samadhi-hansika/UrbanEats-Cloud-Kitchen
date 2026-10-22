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
- Manage food items and stock
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
7. Open the project in your browser.

## Run the Project
Open:

`http://localhost/urbaneats/UrbanEats-Cloud-Kitchen/`

To open the administrator dashboard, sign in with an administrator account and visit:

`http://localhost/urbaneats/UrbanEats-Cloud-Kitchen/admin/dashboard.php`

Public registration creates Customer accounts only. After signing in, an Administrator can create Kitchen Staff and additional Administrator accounts from **Users & Roles**. All account passwords are stored as password hashes, and Customer, Kitchen Staff, and Administrator accounts use the same sign-in page.

The SQL dump does not include a default Administrator account. Provision the first Administrator through your trusted database setup process; after that, use **Users & Roles** to create staff accounts.
