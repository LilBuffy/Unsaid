CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT DEFAULT NULL,
    profile_id INT NOT NULL,
    reported_message TEXT NOT NULL,
    sender_ip VARCHAR(45) DEFAULT NULL,
    status ENUM('pending', 'reviewed') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reports_message (message_id),
    CONSTRAINT fk_reports_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS banned_senders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255) DEFAULT NULL,
    banned_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
