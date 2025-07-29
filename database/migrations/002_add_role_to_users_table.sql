-- Add role column to users table
ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash;

-- Add index for role queries
CREATE INDEX idx_users_role ON users(role);

-- Update existing users to have 'user' role (if any exist)
UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''; 