-- Migration script for teacher_payments table
-- Run this SQL script to create the teacher_payments table

CREATE TABLE IF NOT EXISTS teacher_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    month VARCHAR(7) NOT NULL,
    is_paid TINYINT(1) DEFAULT 0,
    paid_date DATETIME NULL,
    paid_amount DECIMAL(10,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_month (teacher_id, month)
);

-- Add index for better query performance
CREATE INDEX idx_teacher_payments_teacher_id ON teacher_payments(teacher_id);
CREATE INDEX idx_teacher_payments_month ON teacher_payments(month);
