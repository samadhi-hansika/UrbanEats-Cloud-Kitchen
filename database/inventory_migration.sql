USE `urbaneats_db`;

ALTER TABLE `food_items`
  ADD COLUMN `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN `low_stock_threshold` int(10) unsigned NOT NULL DEFAULT 5;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(120) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(10) unsigned NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `item_movement_history` (`item_id`, `movement_id`),
  KEY `movement_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
