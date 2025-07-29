-- Create exams table
CREATE TABLE exams (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    duration_minutes INT NOT NULL,
    passing_score_percentage INT NOT NULL,
    topic_id VARCHAR(36) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);

-- Add indexes for common queries
CREATE INDEX idx_exams_topic_id ON exams(topic_id);
CREATE INDEX idx_exams_active ON exams(is_active);
CREATE INDEX idx_exams_created_at ON exams(created_at);
CREATE INDEX idx_exams_deleted_at ON exams(deleted_at); 