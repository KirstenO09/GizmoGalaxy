CREATE DATABASE tables;
USE tables;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255) NOT NULL
);

-- Cart table
CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Cart items table to store items in each cart
CREATE TABLE cart_items (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

INSERT INTO products (product_id, name, category, price, image_url) VALUES
(1, 'Gizmo Phone', 'Smartphones', 799.99, 'img/genericsmartphone.jpg'),
(2, 'Gizmo Watch', 'Wearables', 399.99, 'img/mockup2.jpeg'),
(3, 'Gizmo Tablet', 'Tablets', 899.99, 'img/mockup3.png'),
(4, 'Gizmo Headphones', 'Audio', 69.99, 'img/mockup4.png'),
(5, 'Gizmo Buds', 'Audio', 39.99, 'img/mockup5.png'),
(6, 'Gizmo Speaker', 'Audio', 49.99, 'img/mockup6.webp'),
(7, 'Gizmo TV', 'Television', 1029.99, 'img/mockup8.png');

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10, 2), NOT NULL
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Order Items 
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Trade-In table
CREATE TABLE trade_in (
    trade_in_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    device_name VARCHAR(100) NOT NULL,
    trade_in_value DECIMAL(10, 2) NOT NULL,
    trade_in_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_order_user ON orders(user_id);
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_tradein_user ON trade_in(user_id);
