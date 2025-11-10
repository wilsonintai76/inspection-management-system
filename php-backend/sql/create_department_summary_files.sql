-- Create table for department summary files

CREATE TABLE IF NOT EXISTS department_summary_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filesize INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_department (department_id)
);

-- Verify table was created
SHOW CREATE TABLE department_summary_files;

SELECT 'Department summary files table created successfully!' AS status;
