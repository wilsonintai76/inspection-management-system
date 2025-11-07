-- Add asset inspection tables to schema

-- Upload batches: track each file upload session
CREATE TABLE IF NOT EXISTS asset_upload_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploaded_by VARCHAR(191) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    total_records INT NOT NULL DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asset inspection records: uninspected assets from uploaded files
CREATE TABLE IF NOT EXISTS asset_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    label VARCHAR(255) NOT NULL COMMENT 'Asset label/ID from file',
    jenis_aset VARCHAR(255) COMMENT 'Asset type',
    pegawai_penempatan VARCHAR(255) COMMENT 'Officer assignment',
    bahagian VARCHAR(255) COMMENT 'Department/Division name from file',
    lokasi_terkini VARCHAR(255) COMMENT 'Current location',
    department_id INT NULL COMMENT 'Mapped department from bahagian',
    is_inspected TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Mark as inspected',
    inspected_date DATE NULL,
    inspected_by VARCHAR(191) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES asset_upload_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (inspected_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_batch (batch_id),
    INDEX idx_department (department_id),
    INDEX idx_inspected (is_inspected),
    INDEX idx_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
