-- UrbanEats Component C: Ordering, Payment and Tracking
-- Run after urbaneats_db.sql and the menu/inventory migrations.

USE `urbaneats_db`;

CREATE TABLE IF NOT EXISTS coupons (
    coupon_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(40) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_order_value DECIMAL(10,2) NOT NULL DEFAULT 0,
    usage_limit INT UNSIGNED DEFAULT NULL,
    times_used INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (coupon_id),
    UNIQUE KEY coupon_code (code),
    KEY coupon_lookup (code, is_active, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    order_number VARCHAR(24) NOT NULL,
    fulfillment_type ENUM('delivery', 'pickup') NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(30) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    coupon_id INT UNSIGNED DEFAULT NULL,
    status ENUM('Placed', 'Preparing', 'Ready', 'Out for Delivery', 'Collected', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Placed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id),
    UNIQUE KEY order_number (order_number),
    KEY order_user_date (user_id, created_at),
    KEY order_status_date (status, created_at),
    CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE RESTRICT,
    CONSTRAINT orders_coupon_fk FOREIGN KEY (coupon_id) REFERENCES coupons (coupon_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    item_id INT(11) DEFAULT NULL,
    item_name VARCHAR(120) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (order_item_id),
    KEY order_items_order (order_id),
    KEY order_items_item (item_id),
    CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
    CONSTRAINT order_items_item_fk FOREIGN KEY (item_id) REFERENCES food_items (item_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    payment_method ENUM('cash_on_delivery', 'card', 'bank_transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Recorded', 'Failed') NOT NULL DEFAULT 'Pending',
    paid_at DATETIME DEFAULT NULL,
    PRIMARY KEY (payment_id),
    UNIQUE KEY payment_order (order_id),
    CONSTRAINT payments_order_fk FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    status ENUM('Placed', 'Preparing', 'Ready', 'Out for Delivery', 'Collected', 'Completed', 'Cancelled') NOT NULL,
    changed_by INT(11) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (history_id),
    KEY status_history_order (order_id, created_at),
    CONSTRAINT status_history_order_fk FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
    CONSTRAINT status_history_user_fk FOREIGN KEY (changed_by) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO coupons (code, discount_type, discount_value, minimum_order_value, usage_limit, expires_at)
VALUES ('WELCOME10', 'percentage', 10, 2500, 100, DATE_ADD(NOW(), INTERVAL 90 DAY));
