CREATE TABLE IF NOT EXISTS issue_upvotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_issue_user (issue_id, user_id),
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
