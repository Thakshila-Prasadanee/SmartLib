-- Add role column to users table for role-based login system
-- Run this SQL script in your database to enable role-based authentication

-- Add role column if it doesn't exist
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';

-- Set default role for existing users
UPDATE users SET role = 'user' WHERE role IS NULL OR role = '';

-- Optional: Set specific users as admin (replace 'admin@example.com' with actual admin email)
-- UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';

-- Add index for better performance
CREATE INDEX idx_users_role ON users(role);

-- Add constraint to ensure valid roles
ALTER TABLE users ADD CONSTRAINT chk_user_role CHECK (role IN ('user', 'admin'));
