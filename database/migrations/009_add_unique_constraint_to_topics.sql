-- Add unique constraint to topics table to prevent duplicates
-- This ensures that no two active topics can have the same title and level

-- First, let's clean up existing duplicates by keeping only the most recent one for each title/level combination
DELETE t1 FROM topics t1
INNER JOIN topics t2 
WHERE t1.id > t2.id 
AND t1.title = t2.title 
AND t1.level = t2.level 
AND t1.deleted_at IS NULL 
AND t2.deleted_at IS NULL;

-- Add unique constraint for active topics (title + level combination)
ALTER TABLE topics 
ADD CONSTRAINT unique_active_topic_title_level 
UNIQUE (title, level, is_active, deleted_at);

-- Add index for better performance on title lookups
CREATE INDEX idx_topics_title_level ON topics(title, level); 