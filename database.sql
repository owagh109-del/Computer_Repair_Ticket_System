-- ============================================================
-- COMPUTER REPAIR TICKET SYSTEM
-- Database SQL file
-- ============================================================

CREATE DATABASE IF NOT EXISTS computer_repair_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE computer_repair_db;

CREATE TABLE IF NOT EXISTS repair_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    problem_description TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data
INSERT INTO repair_tickets
(customer_name, email, device_type, problem_description, priority, status)
SELECT 'Rahul Patil', 'rahul@example.com', 'Laptop',
       'Laptop is overheating frequently.', 'High', 'Pending'
WHERE NOT EXISTS (SELECT 1 FROM repair_tickets);

INSERT INTO repair_tickets
(customer_name, email, device_type, problem_description, priority, status)
SELECT 'Amit Sharma', 'amit@example.com', 'Desktop',
       'Computer does not start properly.', 'Critical', 'In Progress'
WHERE (SELECT COUNT(*) FROM repair_tickets) = 1;

INSERT INTO repair_tickets
(customer_name, email, device_type, problem_description, priority, status)
SELECT 'Priya Joshi', 'priya@example.com', 'Printer',
       'Printer is showing paper jam error.', 'Medium', 'Completed'
WHERE (SELECT COUNT(*) FROM repair_tickets) = 2;

INSERT INTO repair_tickets
(customer_name, email, device_type, problem_description, priority, status)
SELECT 'Sneha Deshmukh', 'sneha@example.com', 'Monitor',
       'Monitor display is flickering.', 'Low', 'Pending'
WHERE (SELECT COUNT(*) FROM repair_tickets) = 3;

INSERT INTO repair_tickets
(customer_name, email, device_type, problem_description, priority, status)
SELECT 'Vikas More', 'vikas@example.com', 'Laptop',
       'Laptop battery is not charging.', 'High', 'In Progress'
WHERE (SELECT COUNT(*) FROM repair_tickets) = 4;
