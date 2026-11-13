CREATE TABLE IF NOT EXISTS rate_limits (
    scope VARCHAR(24) NOT NULL,
    rkey CHAR(64) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    strikes TINYINT UNSIGNED NOT NULL DEFAULT 0,
    window_start INT UNSIGNED NOT NULL,
    locked_until INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (scope, rkey),
    KEY idx_rate_limits_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(40) NOT NULL,
    actor_type VARCHAR(10) NOT NULL DEFAULT 'none',
    actor_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    detail VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_security_events_created (created_at),
    KEY idx_security_events_type (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
