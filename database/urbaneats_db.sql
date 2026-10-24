-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 01:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `urbaneats_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `item_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(80) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `low_stock_threshold` int(10) unsigned NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(120) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(10) unsigned NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`item_id`, `name`, `description`, `category`, `price`, `image_path`, `is_available`) VALUES
(1, 'Classic Smash Burger', 'Juicy grilled beef patty, melted cheddar cheese, crisp lettuce, tomato, pickles, and our signature secret sauce on a toasted brioche bun.', 'Burgers', 1850.00, 'public/images/menu/classic-smash-burger.jpg', 1),
(2, 'Crispy Chicken Burger', 'Golden breaded chicken breast topped with fresh romaine lettuce, pickled onions, and creamy herb ranch dressing.', 'Burgers', 1650.00, 'public/images/menu/Crispy-Chicken-Burger.png', 1),
(3, 'Loaded Garden Bowl', 'A vibrant medley of crisp seasonal greens, roasted chickpeas, cherry tomatoes, cucumbers, quinoa, and honey-dijon vinaigrette.', 'Bowls', 1450.00, 'public/images/menu/Loaded-Garden-Bowl.jpg', 1),
(4, 'Spiced Chicken Rice', 'Fragrant seasoned basmati rice served with tender roasted spiced chicken thigh, house gravy, and refreshing cucumber sambal.', 'Rice & Meals', 1950.00, 'public/images/menu/Spiced Chicken Rice.png', 1),
(5, 'Crispy Golden Fries', 'Hand-cut russet potatoes fried to golden perfection and tossed with our signature smoked paprika and herb sea salt blend.', 'Sides', 650.00, 'public/images/menu/Crispy-Golden-Fries.jpg', 1),
(6, 'Chocolate Brownie Bites', 'Decadent Belgian dark chocolate fudge brownies baked fresh, cut into bite-sized morsels, and dusted with premium Dutch cocoa.', 'Desserts', 850.00, 'public/images/menu/chocolate-brownie-bites.jpg', 1),
(7, 'Fresh Lime Cooler', 'Freshly squeezed Sri Lankan key limes blended with crushed ice, aromatic mint leaves, and pure cane sugar syrup.', 'Drinks', 550.00, 'public/images/menu/Fresh-Lime-Cooler.jpg', 1),
(8, 'Street-Style Chicken Wrap', 'Flame-grilled marinated chicken tenders, shredded cabbage, sliced peppers, and creamy garlic aioli rolled in warm flatbread.', 'Wraps', 1350.00, 'public/images/menu/Street-Style Chicken Wrap.jpg', 1),
(9, 'Roasted Veggie Flatbread', 'Artisan stone-baked flatbread topped with grilled zucchini, bell peppers, Kalamata olives, melted mozzarella, and fresh basil.', 'Sides', 1250.00, 'public/images/menu/Roasted-Veggie-Flatbread.jpg', 1),
(10, 'Coconut Curry Bowl', 'Rich and aromatic Sri Lankan coconut milk curry simmered with garden vegetables, sweet potatoes, and served over steamed jasmine rice.', 'Bowls', 1550.00, 'public/images/menu/Coconut-Curry-Bowl.jpg', 1),
(11, 'BBQ Bacon Cheeseburger', 'Thick grilled beef patty basted with smoky BBQ sauce, crispy beef bacon strips, melted cheddar cheese, and crispy onion strings.', 'Burgers', 2150.00, 'public/images/menu/bbq-bacon-cheeseburger.jpg', 1),
(12, 'Spicy Peri-Peri Burger', 'Succulent chicken breast marinated in fiery African bird\'s eye chilli sauce, topped with crunchy slaw and jalapeño aioli.', 'Burgers', 1750.00, 'public/images/menu/spicy-peri-peri-burger.jpg', 1),
(13, 'Mushroom Swiss Melt Burger', 'Flame-broiled beef patty smothered with sautéed garlic button mushrooms, melted imported Swiss cheese, and truffle mayo.', 'Burgers', 1950.00, 'public/images/menu/mushroom-swiss-melt-burger.jpg', 1),
(14, 'Double Beef Monster Burger', 'Two 150g prime beef patties, double American cheddar cheese, caramelized onions, crisp pickles, and smoky burger relish.', 'Burgers', 2550.00, 'public/images/menu/double-beef-monster-burger.jpg', 1),
(15, 'Crispy Fish Fillet Burger', 'Panko-crusted white fish fillet fried golden, accompanied by crisp shredded iceberg lettuce and tangy caper tartar sauce.', 'Burgers', 1700.00, 'public/images/menu/crispy-fish-fillet-burger.jpg', 1),
(16, 'Teriyaki Chicken Rice Bowl', 'Glazed pan-seared chicken thighs in sweet teriyaki reduction, steamed Japanese rice, broccoli florets, and toasted sesame seeds.', 'Bowls', 1850.00, 'public/images/menu/teriyaki-chicken-rice-bowl.jpg', 1),
(17, 'Grilled Salmon Poke Bowl', 'Atlantic salmon grilled to medium, served over seasoned sushi rice with sliced avocado, edamame, pickled ginger, and ponzu drizzle.', 'Bowls', 2650.00, 'public/images/menu/grilled-salmon-poke-bowl.jpg', 1),
(18, 'Mexican Fiesta Burrito Bowl', 'Zesty cilantro-lime rice, seasoned black beans, sweet corn, fresh pico de gallo, guacamole, and grilled chicken tenders.', 'Bowls', 1750.00, 'public/images/menu/mexican-fiesta-burrito-bowl.jpg', 1),
(19, 'Paneer Tikka Quinoa Bowl', 'Tandoori-spiced cottage cheese cubes charred in tandoor, served over warm fluffy quinoa, roasted peppers, and mint chutney.', 'Bowls', 1600.00, 'public/images/menu/paneer-tikka-quinoa-bowl.jpg', 1),
(20, 'Avocado Edamame Green Bowl', 'Baby spinach, nutrient-dense edamame beans, ripe avocado slices, shredded kale, chia seeds, and green goddess tahini dressing.', 'Bowls', 1500.00, 'public/images/menu/avocado-edamame-green-bowl.jpg', 1),
(21, 'Sri Lankan Seafood Nasi Goreng', 'Indonesian-Sri Lankan fusion wok-fried rice tossed with prawns, calamari, fried egg ribbon, chicken satay skewer, and chilli paste.', 'Rice & Meals', 2250.00, 'public/images/menu/sri-lankan-seafood-nasi-goreng.jpg', 1),
(22, 'Thai Basil Chicken Fried Rice', 'Aromatic jasmine rice flash-fried with minced chicken, Thai bird chillies, sweet dark soy sauce, and fragrant holy basil leaves.', 'Rice & Meals', 1850.00, 'public/images/menu/thai-basil-chicken-fried-rice.jpg', 1),
(23, 'Butter Chicken with Jeera Rice', 'Tender tandoor chicken pieces enveloped in a velvety tomato-cream sauce with fenugreek, paired with fragrant cumin basmati rice.', 'Rice & Meals', 2100.00, 'public/images/menu/butter-chicken-with-jeera-rice.jpg', 1),
(24, 'Mongolian Beef Fried Rice', 'Marinated flank beef slices flash-fried with green spring onions, bell peppers, wok-tossed spicy rice, and toasted sesame oil.', 'Rice & Meals', 2350.00, 'public/images/menu/mongolian-beef-fried-rice.jpg', 1),
(25, 'Sri Lankan Village Chicken Rice', 'Authentic village-style red rice with spicy country chicken curry, dhal curry, tempered potato baduma, and pol sambol.', 'Rice & Meals', 1750.00, 'public/images/menu/sri-lankan-village-chicken-rice.jpg', 1),
(26, 'Kothu Roti Supreme Chicken', 'Finely chopped Godamba roti tempered with curry chicken, fresh eggs, green chillies, leeks, and fragrant roasted spices.', 'Rice & Meals', 1650.00, 'public/images/menu/kothu-roti-supreme-chicken.jpg', 1),
(27, 'Cheesy Chicken Shawarma Wrap', 'Slow-roasted chicken shawarma slivers with melted mozzarella, french fries inside, garlic toum, and pickled gherkins in Lebanese pita.', 'Wraps', 1450.00, 'public/images/menu/cheesy-chicken-shawarma-wrap.jpg', 1),
(28, 'Falafel & Creamy Hummus Wrap', 'Crispy spiced chickpea falafel patties, homemade garlic hummus, fresh mint, diced tomatoes, and tangy tahini dressing.', 'Wraps', 1250.00, 'public/images/menu/falafel-creamy-hummus-wrap.jpg', 1),
(29, 'Spicy Tandoori Paneer Wrap', 'Smoky tandoori marinated paneer strips, crunchy bell peppers, pickled red onions, and zesty coriander mint cream rolled in a paratha.', 'Wraps', 1350.00, 'public/images/menu/spicy-tandoori-paneer-wrap.jpg', 1),
(30, 'BBQ Pulled Beef Wrap', 'Slow-cooked shredded tender beef glazed with sweet hickory BBQ sauce, creamy coleslaw, and melted cheddar wrapped in tortilla.', 'Wraps', 1650.00, 'public/images/menu/bbq-pulled-beef-wrap.jpg', 1),
(31, 'Garlic Parmesan Chicken Wings', 'Crispy fried jumbo wings tossed in rich roasted garlic butter sauce, finished with grated aged parmesan and chopped parsley.', 'Sides', 1450.00, 'public/images/menu/garlic-parmesan-chicken-wings.jpg', 1),
(32, 'Cheesy Mozzarella Sticks', 'Stretchy whole milk mozzarella battered in seasoned Italian breadcrumbs, fried crisp, and served with warm marinara dipping sauce.', 'Sides', 950.00, 'public/images/menu/cheesy-mozzarella-sticks.jpg', 1),
(33, 'Crispy Golden Onion Rings', 'Thick slices of sweet yellow onions dipped in light beer batter and fried to a crunch, paired with house spicy mayo.', 'Sides', 750.00, 'public/images/menu/crispy-golden-onion-rings.jpg', 1),
(34, 'Loaded Beef Chilli Fries', 'Crispy fries smothered in slow-simmered minced beef chili con carne, melted cheddar cheese sauce, jalapeño rings, and sour cream.', 'Sides', 1350.00, 'public/images/menu/loaded-beef-chilli-fries.jpg', 1),
(35, 'Creamy Tuscan Chicken Fettuccine', 'Al dente fettuccine ribbons tossed in a velvety sun-dried tomato and baby spinach parmesan cream sauce with grilled chicken breast.', 'Pasta', 2150.00, 'public/images/menu/creamy-tuscan-chicken-fettuccine.jpg', 1),
(36, 'Spicy Arrabiata Penne', 'Penne pasta simmered in robust garlic and crushed red pepper tomato sauce, Kalamata olives, fresh basil, and extra virgin olive oil.', 'Pasta', 1650.00, 'public/images/menu/spicy-arrabiata-penne.jpg', 1),
(37, 'New York Baked Cheesecake', 'Classic dense and silky cream cheese cake on a golden buttery graham cracker crust, finished with a fresh strawberry coulis.', 'Desserts', 1150.00, 'public/images/menu/new-york-baked-cheesecake.jpg', 1),
(38, 'Molten Chocolate Lava Cake', 'Warm individual chocolate cake with a rich liquid ganache center that flows out when cut, served with cocoa dusting.', 'Desserts', 1250.00, 'public/images/menu/molten-chocolate-lava-cake.jpg', 1),
(39, 'Traditional Watalappan Cup', 'Authentic steamed Sri Lankan coconut milk custard sweetened with dark kithul jaggery, scented with nutmeg, cardamom, and roasted cashews.', 'Desserts', 650.00, 'public/images/menu/traditional-watalappan-cup.jpg', 1),
(40, 'Mango Passion Fruit Mocktail', 'Tropical puree of sweet Alphonso mango and tangy passion fruit pulp shaken with sparkling soda water and crushed mint ice.', 'Drinks', 650.00, 'public/images/menu/mango-passion-fruit-mocktail.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `is_active`) VALUES
(1, 'Bowls', 'Fresh and wholesome grain bowls, seasonal greens, and protein-packed nutrient blends.', 1),
(2, 'Burgers', 'Artisan gourmet smash burgers, crispy chicken fillets, and brioche specialties.', 1),
(3, 'Desserts', 'Sweet treats, velvety cheesecakes, chocolate lava cakes, and authentic puddings.', 1),
(4, 'Drinks', 'Freshly pressed fruit mocktails, chilled coolers, and signature thirst quenchers.', 1),
(5, 'Pasta', 'Handcrafted Italian pasta ribbons, creamy sauces, and fiery arrabiata seasonings.', 1),
(6, 'Rice & Meals', 'Aromatic seasoned basmati, wok-fried specialties, and authentic Sri Lankan spiced rice.', 1),
(7, 'Sides', 'Crispy golden fries, battered onion rings, mozzarella sticks, and savoury appetizers.', 1),
(8, 'Wraps', 'Warm flatbread rolls, authentic shawarmas, tandoori fillings, and toasted burritos.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:50:12'),
(2, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:50:23'),
(3, 2, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:52:23'),
(4, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:52:45'),
(5, 3, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:54:38'),
(6, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:55:19'),
(7, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:58:16'),
(8, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 19:14:29'),
(9, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:25:03'),
(10, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:25:50'),
(11, 1, 'PASSWORD_CHANGE', 'User changed account password', '::1', '2026-10-22 03:26:40'),
(12, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:28:56'),
(13, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:29:40'),
(14, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:30:57'),
(15, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:38:11'),
(16, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:54:32'),
(17, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:57:32'),
(18, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:58:58'),
(19, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 04:25:33'),
(20, 1, 'USER_STATUS_TOGGLE', 'Toggled status for user ID: 3', '::1', '2026-10-22 04:25:59'),
(21, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 08:38:49'),
(22, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 08:39:43'),
(23, 7, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 11:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(100) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `ip_address`, `email`, `attempt_time`) VALUES
(1, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:28:41'),
(2, '::1', 'nurani@gmail.com', '2026-10-21 18:29:18'),
(3, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:29:20'),
(4, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:29:22'),
(5, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:33:48'),
(6, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:50:15'),
(7, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:58:02'),
(8, '::1', 'kulasuriyanurani@gmail.com', '2026-10-22 03:25:36'),
(9, '::1', 'kulasuriyanurani@gmail.com', '2026-10-22 03:57:14'),
(10, '::1', 'samadhi@urbaneats.com', '2026-10-22 10:43:38'),
(11, '::1', 'samadhi@urbaneats.com', '2026-10-22 10:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'Administrator'),
(1, 'Customer'),
(2, 'Kitchen Staff');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'nurani', 'kulasuriyanurani@gmail.com', '0778905771', '$2y$10$TaUt38EGjIzdh2xyNzKMlOBbGC7SqZM6ajjjULGKXDdR3orw92uSq', 3, 1, '2026-10-21 18:50:12'),
(2, 'test', 'test@gmail.com', '0778674534', '$2y$10$eq/ZhsVTiOLj0w2nH5xheOulCokdiaXSRPp1gChRw1VRM3iY.K1o6', 1, 1, '2026-10-21 18:52:23'),
(3, 'vishmi', 'vishmi@gmail.com', '0774568790', '$2y$10$ooTj1Jkcd9iocReaJMjGtOy3GFc0UOshJF3p7tgtor.6hpOyWGSKO', 2, 0, '2026-10-21 18:54:38'),
(7, 'Samadhi Hansika', 'samadhi@urbaneats.com', '', '$2y$10$iNNtDQewNDDbIGKguRi6F.smX5s2ZUaIXbW6VXv6E2XJqaKA9l0BS', 2, 1, '2026-10-22 11:07:54'),
(8, 'Sanduni Thilanka', 'sanduni@urbaneats.com', '', '$2y$10$ODiiU4a1.BV17ZRbZyApp.zKrDOIV1e7IFDyQ0XccWpJThdKxWEGG', 2, 1, '2026-10-22 11:07:54'),
(9, 'Sasuni Ishanga', 'sasuni@urbaneats.com', '', '$2y$10$QYEY9EIYx46yyJPN8GNUnOnV7YRzSsC.uFeo/e7F1fWZHL6w0cyOa', 2, 1, '2026-10-22 11:07:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `available_category` (`is_available`, `category`);

ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `item_movement_history` (`item_id`, `movement_id`),
  ADD KEY `movement_created_at` (`created_at`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `food_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

ALTER TABLE `inventory_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
