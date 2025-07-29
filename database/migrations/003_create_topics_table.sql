-- Create topics table
CREATE TABLE topics (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
);

-- Add indexes for common queries
CREATE INDEX idx_topics_level ON topics(level);
CREATE INDEX idx_topics_active ON topics(is_active);
CREATE INDEX idx_topics_created_at ON topics(created_at);
CREATE INDEX idx_topics_deleted_at ON topics(deleted_at); 