-- Add manager_id column to restaurants table
ALTER TABLE restaurants
ADD COLUMN manager_id INT,
ADD CONSTRAINT fk_restaurant_manager
FOREIGN KEY (manager_id) REFERENCES users(user_id)
ON DELETE SET NULL; 