-- Create exam_attempts table
CREATE TABLE exam_attempts (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    score INT DEFAULT 0 NOT NULL,
    passed BOOLEAN DEFAULT FALSE NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Add indexes for common queries
CREATE INDEX idx_exam_attempts_user_id ON exam_attempts(user_id);
CREATE INDEX idx_exam_attempts_exam_id ON exam_attempts(exam_id);
CREATE INDEX idx_exam_attempts_started_at ON exam_attempts(started_at);
CREATE INDEX idx_exam_attempts_completed_at ON exam_attempts(completed_at);
CREATE INDEX idx_exam_attempts_passed ON exam_attempts(passed);
CREATE INDEX idx_exam_attempts_deleted_at ON exam_attempts(deleted_at); 