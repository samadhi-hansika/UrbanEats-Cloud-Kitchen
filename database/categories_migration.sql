USE `urbaneats_db`;

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name_unique` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Initial seed data for `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `is_active`) VALUES
(1, 'Bowls', 'Fresh and wholesome grain bowls, seasonal greens, and protein-packed nutrient blends.', 1),
(2, 'Burgers', 'Artisan gourmet smash burgers, crispy chicken fillets, and brioche specialties.', 1),
(3, 'Desserts', 'Sweet treats, velvety cheesecakes, chocolate lava cakes, and authentic puddings.', 1),
(4, 'Drinks', 'Freshly pressed fruit mocktails, chilled coolers, and signature thirst quenchers.', 1),
(5, 'Pasta', 'Handcrafted Italian pasta ribbons, creamy sauces, and fiery arrabiata seasonings.', 1),
(6, 'Rice & Meals', 'Aromatic seasoned basmati, wok-fried specialties, and authentic Sri Lankan spiced rice.', 1),
(7, 'Sides', 'Crispy golden fries, battered onion rings, mozzarella sticks, and savoury appetizers.', 1),
(8, 'Wraps', 'Warm flatbread rolls, authentic shawarmas, tandoori fillings, and toasted burritos.', 1)
ON DUPLICATE KEY UPDATE 
  `description` = VALUES(`description`),
  `is_active` = VALUES(`is_active`);
