-- 用户表
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    phone VARCHAR(20),
    role	varchar(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 事件表
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    venue VARCHAR(255),
    min_price DECIMAL(10,2),
    max_price DECIMAL(10,2),
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE
);

-- 票区表 (修改后)
CREATE TABLE ticket_zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    zone_name VARCHAR(100) NOT NULL,
    zone_category ENUM('cat1', 'cat2', 'cat3', 'cat4', 'restricted') NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    available_seats INT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

c-- 票据表
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    qr_code_path VARCHAR(255),
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

----------------------
-- 折扣类型表
CREATE TABLE discount_types (
    discount_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- 票区折扣关联表
CREATE TABLE zone_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    discount_id INT NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    availability_status ENUM('available', 'limited', 'single_seats', 'sold_out') NOT NULL,
    FOREIGN KEY (zone_id) REFERENCES ticket_zones(zone_id),
    FOREIGN KEY (discount_id) REFERENCES discount_types(discount_id)
);

-- 用户折扣资格表
CREATE TABLE user_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    discount_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (discount_id) REFERENCES discount_types(discount_id)
);

-----------------------------------------------------------
-- 添加过期时间字段
ALTER TABLE orders ADD COLUMN transaction_expiry DATETIME;

-- 生成时设置过期时间(如30分钟后)
UPDATE orders
SET transaction_expiry = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
WHERE order_id = ?;

-- 定时清理过期交易
UPDATE orders
SET payment_status = 'expired'
WHERE payment_status = 'pending' AND transaction_expiry < NOW();
