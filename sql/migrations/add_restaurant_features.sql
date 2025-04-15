-- Add business hours and contact information
ALTER TABLE restaurants
ADD COLUMN opening_time TIME,
ADD COLUMN closing_time TIME,
ADD COLUMN website VARCHAR(255),
ADD COLUMN facebook_url VARCHAR(255),
ADD COLUMN instagram_url VARCHAR(255),
ADD COLUMN minimum_order_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN delivery_radius INT COMMENT 'in meters' DEFAULT 5000,
ADD COLUMN is_verified TINYINT(1) DEFAULT 0,
ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending'; 