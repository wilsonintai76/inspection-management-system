
# MySQL Database Schema for Inspectable App

This document outlines a conceptual MySQL database schema equivalent to the Firestore data model used in this application. This schema is designed for a relational database and uses foreign keys to establish relationships between tables.

## 1. `departments` Table

Stores the different departments within the organization.

```sql
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    acronym VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 2. `users` Table

Stores application user information. In a real-world scenario, the `id` might be a UUID string to correspond with an external authentication provider's ID.

```sql
CREATE TABLE users (
    id VARCHAR(255) PRIMARY KEY, -- Corresponds to Firebase Auth UID
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    department_id INT,
    photo_url VARCHAR(255),
    status ENUM('Verified', 'Unverified') NOT NULL DEFAULT 'Unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);
```

---

## 3. `user_roles` Table

A linking table to manage the many-to-many relationship between users and roles, as a user can have multiple roles.

```sql
CREATE TABLE user_roles (
    user_id VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Asset Officer', 'Auditor', 'Viewer') NOT NULL,
    
    PRIMARY KEY (user_id, role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 4. `locations` Table

Represents the physical locations that require inspection.

```sql
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    supervisor VARCHAR(255),
    contact_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);
```

---

## 5. `inspections` Table

Stores the schedule for inspections. This table links users (auditors) to locations.

```sql
CREATE TABLE inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    status ENUM('Pending', 'Complete') NOT NULL DEFAULT 'Pending',
    auditor1_id VARCHAR(255),
    auditor2_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (auditor1_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (auditor2_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Ensures the two auditors are not the same person
    CHECK (auditor1_id IS NULL OR auditor2_id IS NULL OR auditor1_id != auditor2_id)
);
```
