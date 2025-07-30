-- Create question_topics table for many-to-many relationship
CREATE TABLE question_topics (
    id VARCHAR(36) PRIMARY KEY,
    question_id VARCHAR(36) NOT NULL,
    topic_id VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_question_topic (question_id, topic_id)
);

-- Add indexes for common queries
CREATE INDEX idx_question_topics_question_id ON question_topics(question_id);
CREATE INDEX idx_question_topics_topic_id ON question_topics(topic_id);
CREATE INDEX idx_question_topics_created_at ON question_topics(created_at); 