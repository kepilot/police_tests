-- Create questions table
CREATE TABLE questions (
    id VARCHAR(36) PRIMARY KEY,
    text TEXT NOT NULL,
    type ENUM('multiple_choice', 'true_false', 'single_choice') NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    options JSON NOT NULL,
    correct_option INT NOT NULL,
    points INT DEFAULT 1 NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Add indexes for common queries
CREATE INDEX idx_questions_exam_id ON questions(exam_id);
CREATE INDEX idx_questions_active ON questions(is_active);
CREATE INDEX idx_questions_type ON questions(type);
CREATE INDEX idx_questions_created_at ON questions(created_at);
CREATE INDEX idx_questions_deleted_at ON questions(deleted_at); 