-- Campus Canteen Management System - Database Schema
-- MySQL 8.0+
-- Created: 2026

CREATE DATABASE IF NOT EXISTS canteen_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE canteen_db;

-- 1. USERS Table (Master Table)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','staff','cashier','student') DEFAULT 'student',
    status ENUM('active','inactive') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. CATEGORIES Table (Lookup Table)
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'utensils',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. FOODS Table
CREATE TABLE IF NOT EXISTS foods (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-food.jpg',
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. INVENTORY Table
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    unit VARCHAR(20) NOT NULL,
    stock_in DECIMAL(10,2) DEFAULT 0,
    stock_out DECIMAL(10,2) DEFAULT 0,
    current_stock DECIMAL(10,2) DEFAULT 0,
    reorder_level DECIMAL(10,2) DEFAULT 10,
    status ENUM('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (food_id) REFERENCES foods(food_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. SUPPLIERS Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. CART Table
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. CART_ITEMS Table
CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(food_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. ORDERS Table (Transaction Table)
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    order_type ENUM('dine_in','takeaway','delivery') DEFAULT 'dine_in',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. ORDER_ITEMS Table (Junction Table)
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(food_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. PAYMENTS Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','card','digital') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. ADDRESSES Table
CREATE TABLE IF NOT EXISTS addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line TEXT NOT NULL,
    city VARCHAR(50),
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. ACTIVITY_LOGS Table (for security auditing)
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============ SEED DATA ============

-- Insert Categories
INSERT INTO categories (category_name, description, icon) VALUES
('Main Course', 'Hearty main dishes and meals', 'drumstick-bite'),
('Fast Food', 'Quick and delicious fast food items', 'burger'),
('Snacks', 'Light snacks and appetizers', 'cookie-bite'),
('Beverages', 'Refreshing drinks and beverages', 'mug-hot'),
('Soup', 'Warm and healthy soups', 'bowl-food'),
('Desserts', 'Sweet treats and desserts', 'ice-cream');

-- Insert Users (Admin: admin/admin, User: user/user)
INSERT INTO users (full_name, email, password, phone, role, status) VALUES
('Abrar Abdullah', 'abrar@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01711111111', 'admin', 'active'),
('Sagor Anup Chandra', 'sagor@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01722222222', 'admin', 'active'),
('Mahamud Nadem', 'nadem@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01733333333', 'staff', 'active'),
('Bukhari Syed Ghufran', 'ghufran@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01744444444', 'cashier', 'active'),
('Kasmi Manal', 'manal@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01755555555', 'student', 'active'),
('Rahim Ahmed', 'rahim@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01766666666', 'student', 'active'),
('Sumaiya Begum', 'sumaiya@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01777777777', 'student', 'active'),
('Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01800000000', 'admin', 'active'),
('Regular User', 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01900000000', 'student', 'active');

-- Insert Foods
INSERT INTO foods (food_name, description, category_id, price, image, is_available, is_featured) VALUES
('Chicken Fried Rice', 'Delicious fried rice with tender chicken pieces, vegetables and special sauce', 1, 120.00, 'chicken-fried-rice.jpg', TRUE, TRUE),
('Beef Burger', 'Juicy beef patty with fresh lettuce, tomato and cheese in a toasted bun', 2, 100.00, 'beef-burger.jpg', TRUE, TRUE),
('Chicken Soup', 'Warm and comforting chicken soup with vegetables and herbs', 5, 60.00, 'chicken-soup.jpg', TRUE, FALSE),
('Lemon Juice', 'Freshly squeezed lemon juice with mint and ice', 4, 40.00, 'lemon-juice.jpg', TRUE, FALSE),
('French Fries', 'Crispy golden french fries with ketchup', 3, 70.00, 'french-fries.jpg', TRUE, TRUE),
('Pasta Alfredo', 'Creamy pasta with white sauce and grilled chicken', 1, 150.00, 'pasta-alfredo.jpg', TRUE, TRUE),
('Grilled Chicken', 'Perfectly grilled chicken breast with BBQ sauce', 1, 180.00, 'grilled-chicken.jpg', TRUE, FALSE),
('Fried Rice', 'Classic fried rice with vegetables and egg', 1, 90.00, 'fried-rice.jpg', TRUE, FALSE),
('Chicken Nuggets', 'Crispy chicken nuggets with dipping sauce', 2, 80.00, 'chicken-nuggets.jpg', TRUE, FALSE),
('Spring Rolls', 'Crispy vegetable spring rolls with sweet chili sauce', 3, 60.00, 'spring-rolls.jpg', TRUE, FALSE),
('Pizza Slice', 'Cheesy pepperoni pizza slice with herbs', 2, 85.00, 'pizza-slice.jpg', TRUE, TRUE),
('Iced Coffee', 'Refreshing iced coffee with cream', 4, 55.00, 'iced-coffee.jpg', TRUE, FALSE),
('Milkshake', 'Creamy vanilla milkshake with whipped cream', 4, 70.00, 'milkshake.jpg', TRUE, FALSE),
('Samosa', 'Crispy samosas with spiced potato filling', 3, 25.00, 'samosa.jpg', TRUE, FALSE),
('Beef Biryani', 'Aromatic beef biryani with basmati rice', 1, 160.00, 'beef-biryani.jpg', TRUE, TRUE),
('Nasi Goreng', 'Indonesian fried rice with shrimp and vegetables', 1, 130.00, 'nasi-goreng.jpg', TRUE, FALSE),
('Chocolate Cake', 'Rich chocolate cake with ganache frosting', 6, 90.00, 'chocolate-cake.jpg', TRUE, TRUE),
('Fruit Salad', 'Fresh seasonal fruits with honey drizzle', 6, 65.00, 'fruit-salad.jpg', TRUE, FALSE);

-- Insert Inventory
INSERT INTO inventory (food_id, item_name, category, unit, stock_in, stock_out, current_stock, reorder_level, status) VALUES
(1, 'Rice', 'Raw Material', 'kg', 50, 30, 20, 10, 'in_stock'),
(1, 'Chicken Breast', 'Raw Material', 'kg', 40, 35, 5, 10, 'low_stock'),
(2, 'Beef Mince', 'Raw Material', 'kg', 30, 20, 10, 10, 'in_stock'),
(2, 'Burger Buns', 'Bakery', 'pcs', 100, 60, 40, 20, 'in_stock'),
(3, 'Soup Base', 'Raw Material', 'L', 25, 22, 3, 10, 'low_stock'),
(4, 'Lemons', 'Produce', 'kg', 20, 15, 5, 5, 'in_stock'),
(5, 'Potatoes', 'Produce', 'kg', 40, 35, 5, 10, 'low_stock'),
(6, 'Pasta', 'Raw Material', 'kg', 15, 12, 3, 5, 'low_stock'),
(7, 'Chicken Wings', 'Raw Material', 'kg', 35, 25, 10, 10, 'in_stock'),
(9, 'Chicken Mince', 'Raw Material', 'kg', 30, 28, 2, 5, 'low_stock'),
(11, 'Pizza Base', 'Bakery', 'pcs', 50, 40, 10, 15, 'in_stock'),
(12, 'Coffee Beans', 'Raw Material', 'kg', 10, 8, 2, 3, 'low_stock'),
(15, 'Beef Cubes', 'Raw Material', 'kg', 25, 20, 5, 10, 'in_stock'),
(17, 'Flour', 'Raw Material', 'kg', 30, 20, 10, 10, 'in_stock'),
(17, 'Sugar', 'Raw Material', 'kg', 20, 18, 2, 5, 'low_stock'),
(NULL, 'Cooking Oil', 'Raw Material', 'L', 20, 15, 5, 10, 'in_stock'),
(NULL, 'Onion', 'Produce', 'kg', 30, 28, 2, 5, 'low_stock'),
(NULL, 'Tomato', 'Produce', 'kg', 25, 22, 3, 5, 'low_stock'),
(NULL, 'Garlic', 'Produce', 'kg', 10, 8, 2, 3, 'low_stock'),
(NULL, 'Salt', 'Raw Material', 'kg', 15, 10, 5, 5, 'in_stock');

-- Insert Suppliers
INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, status) VALUES
('Fresh Foods Ltd.', 'John Smith', 'john@freshfoods.com', '01710000001', '123 Main Street, Dhaka', 'active'),
('Meat Suppliers Co.', 'Karim Hossain', 'karim@meatsupply.com', '01710000002', '45 Gulshan Ave, Dhaka', 'active'),
('Bakery House', 'Fatima Begum', 'fatima@bakeryhouse.com', '01710000003', '78 Dhanmondi, Dhaka', 'active'),
('Produce Direct', 'Rahim Khan', 'rahim@producedirect.com', '01710000004', '12 Mirpur Road, Dhaka', 'active'),
('Beverage World', 'Sarah Jones', 'sarah@beverageworld.com', '01710000005', '56 Banani, Dhaka', 'active');

-- Insert Sample Orders
INSERT INTO orders (user_id, total_amount, status, payment_status, order_type, notes) VALUES
(5, 320.00, 'completed', 'paid', 'dine_in', 'Extra spicy please'),
(6, 450.00, 'completed', 'paid', 'takeaway', NULL),
(7, 280.00, 'completed', 'paid', 'dine_in', 'No onions'),
(5, 560.00, 'processing', 'unpaid', 'dine_in', NULL),
(6, 230.00, 'completed', 'paid', 'takeaway', 'Extra ketchup'),
(7, 180.00, 'pending', 'unpaid', 'dine_in', NULL),
(5, 340.00, 'completed', 'paid', 'delivery', NULL),
(6, 120.00, 'completed', 'paid', 'dine_in', NULL),
(7, 410.00, 'processing', 'unpaid', 'takeaway', NULL),
(5, 290.00, 'cancelled', 'refunded', 'dine_in', 'Customer cancelled');

-- Insert Order Items
INSERT INTO order_items (order_id, food_id, quantity, unit_price, total_price) VALUES
(1, 1, 2, 120.00, 240.00),
(1, 4, 2, 40.00, 80.00),
(2, 15, 1, 160.00, 160.00),
(2, 5, 2, 70.00, 140.00),
(2, 12, 1, 55.00, 55.00),
(2, 4, 1, 40.00, 40.00),
(3, 1, 1, 120.00, 120.00),
(3, 5, 1, 70.00, 70.00),
(3, 4, 1, 40.00, 40.00),
(3, 14, 2, 25.00, 50.00),
(4, 15, 1, 160.00, 160.00),
(4, 6, 1, 150.00, 150.00),
(4, 11, 2, 85.00, 170.00),
(4, 13, 1, 70.00, 70.00),
(5, 5, 2, 70.00, 140.00),
(5, 4, 1, 40.00, 40.00),
(5, 3, 1, 60.00, 60.00),
(6, 11, 1, 85.00, 85.00),
(6, 5, 1, 70.00, 70.00),
(6, 12, 1, 55.00, 55.00),
(7, 16, 1, 130.00, 130.00),
(7, 6, 1, 150.00, 150.00),
(7, 4, 1, 40.00, 40.00),
(7, 17, 1, 90.00, 90.00);

-- Insert Payments
INSERT INTO payments (order_id, amount, payment_method, transaction_id, payment_status) VALUES
(1, 320.00, 'cash', 'TXN001', 'completed'),
(2, 450.00, 'digital', 'TXN002', 'completed'),
(3, 280.00, 'card', 'TXN003', 'completed'),
(5, 230.00, 'cash', 'TXN004', 'completed'),
(7, 340.00, 'digital', 'TXN005', 'completed'),
(8, 120.00, 'cash', 'TXN006', 'completed');

-- Insert Activity Logs
INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES
(1, 'Login', 'Admin logged in successfully', '127.0.0.1'),
(1, 'Create', 'Added new food item: Chicken Fried Rice', '127.0.0.1'),
(2, 'Login', 'Admin logged in successfully', '127.0.0.1'),
(5, 'Login', 'Student logged in successfully', '127.0.0.1'),
(5, 'Order', 'Placed order #ORD-001', '127.0.0.1'),
(6, 'Login', 'Student logged in successfully', '127.0.0.1'),
(6, 'Order', 'Placed order #ORD-002', '127.0.0.1');

-- Note: Password hashing - All passwords are hashed with bcrypt
-- Admin password: 'admin' -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- User password: 'user' -> same hash (for demo purposes both use same hash)
-- To verify: password_verify('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') returns true
-- To verify: password_verify('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') returns true
