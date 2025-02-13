-- First, create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS GizmoGalaxy;
USE GizmoGalaxy;

-- Create the cart table
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(100)
);

-- Create the cart_items table
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    PRIMARY KEY (cart_id, product_id),
    FOREIGN KEY (cart_id) REFERENCES cart(cart_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Insert a test product
INSERT INTO products (product_name, price, image_url, category) 
VALUES ('Test Phone', 999.99, 'img/phone.jpg', 'Phones')
ON DUPLICATE KEY UPDATE product_name=product_name;

-- Create a cart for user 1
INSERT INTO cart (user_id) 
VALUES (1)
ON DUPLICATE KEY UPDATE user_id=user_id;