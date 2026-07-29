-- Add delivery fields to orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS delivery_status ENUM('not_delivered','delivered') DEFAULT 'not_delivered' AFTER payment_status,
ADD COLUMN IF NOT EXISTS delivery_date TIMESTAMP NULL DEFAULT NULL AFTER delivery_status,
ADD COLUMN IF NOT EXISTS delivered_by VARCHAR(100) DEFAULT NULL AFTER delivery_date,
ADD COLUMN IF NOT EXISTS script_file VARCHAR(255) DEFAULT NULL AFTER delivered_by,
ADD COLUMN IF NOT EXISTS admin_notes TEXT AFTER script_file,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL DEFAULT NULL AFTER admin_notes;
