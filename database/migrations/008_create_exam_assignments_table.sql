-- Create exam_assignments table
CREATE TABLE exam_assignments (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    assigned_by VARCHAR(36) NOT NULL,
    assigned_at DATETIME NOT NULL,
    due_date DATETIME NULL,
    is_completed BOOLEAN DEFAULT FALSE NOT NULL,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY unique_user_exam_assignment (user_id, exam_id)
);

-- Add indexes for common queries
CREATE INDEX idx_exam_assignments_user_id ON exam_assignments(user_id);
CREATE INDEX idx_exam_assignments_exam_id ON exam_assignments(exam_id);
CREATE INDEX idx_exam_assignments_assigned_by ON exam_assignments(assigned_by);
CREATE INDEX idx_exam_assignments_assigned_at ON exam_assignments(assigned_at);
CREATE INDEX idx_exam_assignments_due_date ON exam_assignments(due_date);
CREATE INDEX idx_exam_assignments_completed ON exam_assignments(is_completed);
CREATE INDEX idx_exam_assignments_deleted_at ON exam_assignments(deleted_at); 