-- Inspectable MySQL schema

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    acronym VARCHAR(20) NULL,
    total_assets INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(191) PRIMARY KEY,
    staff_id VARCHAR(4) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    personal_email VARCHAR(191),
    phone VARCHAR(50),
    department_id INT,
    photo_url VARCHAR(255),
    password_hash VARCHAR(255) NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64) NULL,
    verification_token_expires TIMESTAMP NULL,
    reset_token VARCHAR(64) NULL,
    reset_token_expires TIMESTAMP NULL,
    status ENUM('Verified', 'Unverified') NOT NULL DEFAULT 'Unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_roles (
    user_id VARCHAR(191) NOT NULL,
    role ENUM('Admin', 'Asset Officer', 'Auditor', 'Viewer') NOT NULL,
    PRIMARY KEY (user_id, role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    supervisor VARCHAR(255),
    contact_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    status ENUM('Pending', 'Complete') NOT NULL DEFAULT 'Pending',
    auditor1_id VARCHAR(191),
    auditor2_id VARCHAR(191),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (auditor1_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (auditor2_id) REFERENCES users(id) ON DELETE SET NULL,
    CHECK (auditor1_id IS NULL OR auditor2_id IS NULL OR auditor1_id != auditor2_id)
);
