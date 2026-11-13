<?php

const RATE_LIMIT_KNOWN_TTL = 2592000;
const RATE_LIMIT_LOG_RETENTION = 7776000;

function rl_policies() {
    static $policies = null;
    if ($policies === null) {
        $standard = [60, 120, 300, 900];
        $strict = [60, 300, 900, 1800];
        $policies = [
            'login_pair' => ['limit' => 5, 'window' => 900, 'decay' => 3600, 'cooldowns' => $standard],
            'login_account' => ['limit' => 15, 'window' => 900, 'decay' => 3600, 'cooldowns' => $standard],
            'login_ip' => ['limit' => 40, 'window' => 600, 'decay' => 3600, 'cooldowns' => $standard],
            'admin_pair' => ['limit' => 5, 'window' => 900, 'decay' => 7200, 'cooldowns' => $strict],
            'admin_account' => ['limit' => 10, 'window' => 1800, 'decay' => 7200, 'cooldowns' => $strict],
            'admin_ip' => ['limit' => 15, 'window' => 600, 'decay' => 7200, 'cooldowns' => $strict],
            'register_ip' => ['limit' => 30, 'window' => 3600, 'decay' => 7200, 'cooldowns' => [300, 900, 1800, 3600]],
            'message_pair' => ['limit' => 12, 'window' => 600, 'decay' => 1800, 'cooldowns' => [60, 300, 600, 900]],
            'message_ip' => ['limit' => 40, 'window' => 600, 'decay' => 1800, 'cooldowns' => [60, 300, 600, 900]],
            'preport_ip' => ['limit' => 10, 'window' => 3600, 'decay' => 7200, 'cooldowns' => [300, 900, 1800, 3600]],
            'preport_pair' => ['limit' => 3, 'window' => 3600, 'decay' => 7200, 'cooldowns' => [600, 1800, 3600, 3600]],
        ];
    }
    return $policies;
}

function rl_schema_statements() {
    return [
        "CREATE TABLE IF NOT EXISTS rate_limits (
            scope VARCHAR(24) NOT NULL,
            rkey CHAR(64) NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            strikes TINYINT UNSIGNED NOT NULL DEFAULT 0,
            window_start INT UNSIGNED NOT NULL,
            locked_until INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at INT UNSIGNED NOT NULL,
            PRIMARY KEY (scope, rkey),
            KEY idx_rate_limits_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS security_events (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
}

function rl_create_schema($pdo) {
    try {
        foreach (rl_schema_statements() as $sql) {
            $pdo->exec($sql);
        }
        return true;
    } catch (Throwable $e) {
        error_log('Rate limit schema creation failed: ' . $e->getCode());
        return false;
    }
}

function rl_sqlstate($e) {
    if ($e instanceof PDOException && is_array($e->errorInfo) && isset($e->errorInfo[0])) {
        return (string) $e->errorInfo[0];
    }
    return (string) $e->getCode();
}

function rl_is_missing_table($e) {
    return rl_sqlstate($e) === '42S02';
}

function rl_is_deadlock($e) {
    $state = rl_sqlstate($e);
    return $state === '40001' || $state === '40P01';
}

function rl_hash($value) {
    return hash_hmac('sha256', (string) $value, APP_SECRET);
}

function rl_ip_bucket($ip) {
    $packed = @inet_pton((string) $ip);
    if ($packed === false) {
        return 'unknown';
    }
    if (strlen($packed) === 16) {
        $mappedPrefix = str_repeat("\0", 10) . "\xff\xff";
        if (substr($packed, 0, 12) === $mappedPrefix) {
            return inet_ntop(substr($packed, 12));
        }
        return bin2hex(substr($packed, 0, 8)) . '/64';
    }
    return inet_ntop($packed);
}

function rl_normalize_account($username) {
    return mb_strtolower(mb_substr(trim((string) $username), 0, 100, 'UTF-8'), 'UTF-8');
}

function rl_denied($retryAfter, $error = false) {
    return [
        'allowed' => false,
        'error' => $error,
        'locked_now' => false,
        'retry_after' => max(1, (int) $retryAfter),
        'items' => [],
    ];
}

function rl_begin_once($pdo, array $items, $now) {
    $count = count($items);
    $tuples = implode(',', array_fill(0, $count, '(?,?)'));
    $keyParams = [];
    foreach ($items as $item) {
        $keyParams[] = $item['scope'];
        $keyParams[] = $item['rkey'];
    }

    $stmt = $pdo->prepare("SELECT locked_until FROM rate_limits WHERE (scope, rkey) IN ($tuples) AND locked_until > ?");
    $stmt->execute(array_merge($keyParams, [$now]));
    $lockedRows = $stmt->fetchAll();
    if ($lockedRows) {
        $until = 0;
        foreach ($lockedRows as $row) {
            $until = max($until, (int) $row['locked_until']);
        }
        return rl_denied($until - $now);
    }

    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, $count, '(?,?,0,0,?,0,?)'));
    $insertParams = [];
    foreach ($items as $item) {
        $insertParams[] = $item['scope'];
        $insertParams[] = $item['rkey'];
        $insertParams[] = $now;
        $insertParams[] = $now;
    }
    $pdo->prepare("INSERT IGNORE INTO rate_limits (scope, rkey, attempts, strikes, window_start, locked_until, updated_at) VALUES $placeholders")
        ->execute($insertParams);

    $stmt = $pdo->prepare("SELECT scope, rkey, attempts, strikes, window_start, locked_until, updated_at FROM rate_limits WHERE (scope, rkey) IN ($tuples) ORDER BY scope, rkey FOR UPDATE");
    $stmt->execute($keyParams);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[$row['scope'] . '|' . $row['rkey']] = $row;
    }

    $states = [];
    $lockedUntil = 0;
    foreach ($items as $index => $item) {
        $row = $rows[$item['scope'] . '|' . $item['rkey']] ?? null;
        if ($row === null) {
            throw new RuntimeException('Rate limit row missing');
        }
        $policy = $item['policy'];
        $state = [
            'attempts' => (int) $row['attempts'],
            'strikes' => (int) $row['strikes'],
            'window_start' => (int) $row['window_start'],
            'locked_until' => (int) $row['locked_until'],
            'updated_at' => (int) $row['updated_at'],
        ];
        $items[$index]['prev'] = $state;
        if (($now - $state['updated_at']) > $policy['decay']) {
            $state['attempts'] = 0;
            $state['strikes'] = 0;
            $state['locked_until'] = 0;
            $state['window_start'] = $now;
        } elseif ($state['locked_until'] <= $now && ($now - $state['window_start']) >= $policy['window']) {
            $state['attempts'] = 0;
            $state['window_start'] = $now;
        }
        if ($state['locked_until'] > $now) {
            $lockedUntil = max($lockedUntil, $state['locked_until']);
        }
        $states[$index] = $state;
    }

    if ($lockedUntil > $now) {
        $pdo->commit();
        return rl_denied($lockedUntil - $now);
    }

    $retry = 0;
    $lockedNow = false;
    foreach ($items as $index => $item) {
        $policy = $item['policy'];
        $state = $states[$index];
        $state['attempts']++;
        $state['updated_at'] = $now;
        $items[$index]['set_lock'] = 0;
        if ($state['attempts'] >= $policy['limit']) {
            $state['strikes'] = min($state['strikes'] + 1, count($policy['cooldowns']));
            $cooldown = $policy['cooldowns'][$state['strikes'] - 1];
            $state['locked_until'] = $now + $cooldown;
            $state['attempts'] = 0;
            $state['window_start'] = $now;
            $items[$index]['set_lock'] = $state['locked_until'];
            $retry = max($retry, $cooldown);
            $lockedNow = true;
        }
        $states[$index] = $state;
    }

    $writePlaceholders = implode(',', array_fill(0, $count, '(?,?,?,?,?,?,?)'));
    $writeParams = [];
    foreach ($items as $index => $item) {
        $state = $states[$index];
        $writeParams[] = $item['scope'];
        $writeParams[] = $item['rkey'];
        $writeParams[] = $state['attempts'];
        $writeParams[] = $state['strikes'];
        $writeParams[] = $state['window_start'];
        $writeParams[] = $state['locked_until'];
        $writeParams[] = $state['updated_at'];
    }
    $pdo->prepare("INSERT INTO rate_limits (scope, rkey, attempts, strikes, window_start, locked_until, updated_at) VALUES $writePlaceholders ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), strikes = VALUES(strikes), window_start = VALUES(window_start), locked_until = VALUES(locked_until), updated_at = VALUES(updated_at)")
        ->execute($writeParams);

    $pdo->commit();

    return [
        'allowed' => true,
        'error' => false,
        'locked_now' => $lockedNow,
        'retry_after' => $retry,
        'items' => $items,
    ];
}

function rate_limit_begin($pdo, array $checks, $failOpen = false) {
    $policies = rl_policies();
    $items = [];
    foreach ($checks as $check) {
        if (!empty($check['exempt'])) {
            continue;
        }
        $items[] = [
            'scope' => $check['policy'],
            'rkey' => rl_hash($check['key']),
            'policy' => $policies[$check['policy']],
        ];
    }

    if (empty($items)) {
        return ['allowed' => true, 'error' => false, 'locked_now' => false, 'retry_after' => 0, 'items' => []];
    }

    usort($items, function ($a, $b) {
        return strcmp($a['scope'] . $a['rkey'], $b['scope'] . $b['rkey']);
    });

    $schemaTried = false;
    $deadlocks = 0;
    while (true) {
        try {
            $result = rl_begin_once($pdo, $items, time());
            if ($result['allowed'] && random_int(1, 50) === 1) {
                rate_limit_cleanup($pdo);
            }
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (Throwable $ignored) {
                }
            }
            if (!$schemaTried && rl_is_missing_table($e)) {
                $schemaTried = true;
                if (rl_create_schema($pdo)) {
                    continue;
                }
            }
            if (rl_is_deadlock($e) && $deadlocks < 3) {
                $deadlocks++;
                usleep(random_int(5000, 40000));
                continue;
            }
            error_log('Rate limit store failure: ' . rl_sqlstate($e));
            if ($failOpen) {
                return ['allowed' => true, 'error' => true, 'locked_now' => false, 'retry_after' => 0, 'items' => []];
            }
            return rl_denied(30, true);
        }
    }
}

function rate_limit_succeed($pdo, array $begin, array $resetScopes, array $refundScopes) {
    try {
        foreach ($begin['items'] as $item) {
            if (in_array($item['scope'], $resetScopes, true)) {
                $pdo->prepare("DELETE FROM rate_limits WHERE scope = ? AND rkey = ?")
                    ->execute([$item['scope'], $item['rkey']]);
            } elseif (in_array($item['scope'], $refundScopes, true)) {
                if ($item['set_lock'] > 0) {
                    $pdo->prepare("UPDATE rate_limits SET attempts = ?, strikes = ?, window_start = ?, locked_until = ? WHERE scope = ? AND rkey = ? AND locked_until = ?")
                        ->execute([
                            $item['prev']['attempts'],
                            $item['prev']['strikes'],
                            $item['prev']['window_start'],
                            $item['prev']['locked_until'],
                            $item['scope'],
                            $item['rkey'],
                            $item['set_lock'],
                        ]);
                } else {
                    $pdo->prepare("UPDATE rate_limits SET attempts = GREATEST(attempts - 1, 0) WHERE scope = ? AND rkey = ?")
                        ->execute([$item['scope'], $item['rkey']]);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Rate limit success update failed: ' . rl_sqlstate($e));
    }
}

function rate_limit_known_key($kind, $ipBucket, $accountKey) {
    return rl_hash('known|' . $kind . '|' . $ipBucket . '|' . $accountKey);
}

function rate_limit_is_known($pdo, $kind, $ipBucket, $accountKey) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM rate_limits WHERE scope = 'known' AND rkey = ? AND updated_at >= ?");
        $stmt->execute([rate_limit_known_key($kind, $ipBucket, $accountKey), time() - RATE_LIMIT_KNOWN_TTL]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function rate_limit_mark_known($pdo, $kind, $ipBucket, $accountKey) {
    try {
        $now = time();
        $pdo->prepare("INSERT INTO rate_limits (scope, rkey, attempts, strikes, window_start, locked_until, updated_at) VALUES ('known', ?, 0, 0, ?, 0, ?) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)")
            ->execute([rate_limit_known_key($kind, $ipBucket, $accountKey), $now, $now]);
    } catch (Throwable $e) {
        error_log('Rate limit known marker failed: ' . rl_sqlstate($e));
    }
}

function rate_limit_cleanup($pdo) {
    try {
        $now = time();
        $pdo->prepare("DELETE FROM rate_limits WHERE scope <> 'known' AND locked_until <= ? AND updated_at < ? LIMIT 500")
            ->execute([$now, $now - 7200]);
        $pdo->prepare("DELETE FROM rate_limits WHERE scope = 'known' AND updated_at < ? LIMIT 500")
            ->execute([$now - RATE_LIMIT_KNOWN_TTL]);
        $pdo->prepare("DELETE FROM security_events WHERE created_at < ? LIMIT 500")
            ->execute([date('Y-m-d H:i:s', $now - RATE_LIMIT_LOG_RETENTION)]);
    } catch (Throwable $e) {
        error_log('Rate limit cleanup failed: ' . rl_sqlstate($e));
    }
}

function rate_limit_message($seconds) {
    $seconds = max(1, (int) $seconds);
    if ($seconds < 60) {
        $label = $seconds . ' second' . ($seconds === 1 ? '' : 's');
    } else {
        $minutes = (int) ceil($seconds / 60);
        $label = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
    }
    return 'Too many attempts. Please try again in ' . $label . '.';
}

function security_log($pdo, $eventType, $actorType = 'none', $actorId = null, $detail = null) {
    $params = [
        substr((string) $eventType, 0, 40),
        $actorType,
        $actorId === null ? null : (int) $actorId,
        substr((string) client_ip(), 0, 45),
        $detail === null ? null : substr((string) $detail, 0, 255),
    ];
    $schemaTried = false;
    while (true) {
        try {
            $pdo->prepare("INSERT INTO security_events (event_type, actor_type, actor_id, ip_address, detail) VALUES (?, ?, ?, ?, ?)")
                ->execute($params);
            return;
        } catch (Throwable $e) {
            if (!$schemaTried && rl_is_missing_table($e)) {
                $schemaTried = true;
                if (rl_create_schema($pdo)) {
                    continue;
                }
            }
            error_log('Security log write failed: ' . rl_sqlstate($e));
            return;
        }
    }
}
