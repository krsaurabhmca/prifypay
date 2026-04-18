CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'distributor', 'retailer') NOT NULL,
    parent_id INT DEFAULT NULL,
    wallet_balance DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    account_number VARCHAR(255) NOT NULL,
    ifsc VARCHAR(20) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    status ENUM('verified', 'unverified', 'failed') DEFAULT 'unverified',
    verification_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('payin', 'payout', 'verification', 'commission') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    fee DECIMAL(15, 2) DEFAULT 0.00,
    commission_distributor DECIMAL(15, 2) DEFAULT 0.00,
    commission_retailer DECIMAL(15, 2) DEFAULT 0.00,
    commission_admin DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    reference_id VARCHAR(100) UNIQUE,
    utr VARCHAR(100),
    payment_url TEXT,
    api_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('distributor', 'retailer') NOT NULL,
    transaction_type ENUM('payin', 'payout') NOT NULL,
    method ENUM('percentage', 'flat') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin Account (password: admin123)
INSERT INTO users (name, email, phone, password, role) 
VALUES ('System Admin', 'admin@prifypay.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Default Commissions
INSERT INTO commissions (role, transaction_type, method, value) VALUES 
('distributor', 'payin', 'percentage', 0.5),
('retailer', 'payin', 'percentage', 1.0),
('distributor', 'payout', 'flat', 5.0),
('retailer', 'payout', 'flat', 10.0);
