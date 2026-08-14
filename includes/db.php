<?php

date_default_timezone_set('Asia/Shanghai');

$DB_PATH = __DIR__ . '/../database/shortlink.db';
$pdo = null;

function getPDO(): PDO {
    global $pdo, $DB_PATH;
    if ($pdo === null) {
        // 确保数据库目录存在且可写
        $dbDir = dirname($DB_PATH);
        if (!is_dir($dbDir)) {
            if (!mkdir($dbDir, 0755, true)) {
                throw new RuntimeException("无法创建数据库目录: $dbDir，请手动创建并设置权限 chmod 755");
            }
        }
        if (!is_writable($dbDir)) {
            throw new RuntimeException("数据库目录不可写: $dbDir，请执行 chown -R www:www $dbDir");
        }
        $pdo = new PDO('sqlite:' . $DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
    return $pdo;
}

function initDatabase(): void {
    $db = getPDO();

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        original_url TEXT NOT NULL,
        short_code TEXT UNIQUE NOT NULL,
        expires_at DATETIME,
        disabled TINYINT NOT NULL DEFAULT 0,
        remark TEXT,
        max_visits INTEGER,
        visit_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_short_code ON links(short_code)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON links(user_id)");

    // Seed admin user if not exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    if (!$stmt->fetch()) {
        $hash = password_hash('admin', PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')
           ->execute(['admin', $hash]);
        error_log('[DB] Default admin account created: admin / admin');
    }

    // 兼容旧库：确保 links 表存在 disabled 字段（手动失效功能）
    $cols = $db->query('PRAGMA table_info(links)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('disabled', $cols, true)) {
        $db->exec('ALTER TABLE links ADD COLUMN disabled TINYINT NOT NULL DEFAULT 0');
        error_log('[DB] links.disabled column added');
    }

    // 兼容旧库：确保 links 表存在 remark 字段（备注功能）
    if (!in_array('remark', $cols, true)) {
        $db->exec('ALTER TABLE links ADD COLUMN remark TEXT');
        error_log('[DB] links.remark column added');
    }

    error_log('[DB] Database initialized');
}

// ==================== User operations ====================

function getUserByUsername(string $username): ?array {
    $stmt = getPDO()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getUserById(int $id): ?array {
    $stmt = getPDO()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function createUser(string $username, string $password): int {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = getPDO()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$username, $hash]);
    return (int) getPDO()->lastInsertId();
}

function getAllUsers(): array {
    return getPDO()->query('SELECT id, username, created_at FROM users ORDER BY id')->fetchAll();
}

function getUserLinkCount(int $userId): int {
    $stmt = getPDO()->prepare('SELECT COUNT(*) as c FROM links WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetch()['c'];
}

function deleteUser(int $userId): bool {
    $db = getPDO();
    // Transfer all links to admin before deletion
    $db->prepare('UPDATE links SET user_id = 1 WHERE user_id = ?')->execute([$userId]);
    $stmt = $db->prepare('DELETE FROM users WHERE id = ? AND id != 1');
    $stmt->execute([$userId]);
    return $stmt->rowCount() > 0;
}

function resetPassword(int $userId, string $newPassword): bool {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = getPDO()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);
    return $stmt->rowCount() > 0;
}

function changePassword(int $userId, string $oldPassword, string $newPassword): array {
    $user = getUserById($userId);
    if (!$user) return ['success' => false, 'error' => '用户不存在'];
    if (!password_verify($oldPassword, $user['password_hash'])) {
        return ['success' => false, 'error' => '原密码错误'];
    }
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    getPDO()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
    return ['success' => true];
}

// ==================== Link operations ====================

function createLink(int $userId, string $originalUrl, string $shortCode, ?string $expiresAt, ?int $maxVisits): array {
    $db = getPDO();
    $stmt = $db->prepare('INSERT INTO links (user_id, original_url, short_code, expires_at, max_visits) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $originalUrl, $shortCode, $expiresAt, $maxVisits]);
    return [
        'lastInsertRowid' => (int) $db->lastInsertId(),
    ];
}

function getLinkByCode(string $code): ?array {
    $stmt = getPDO()->prepare('SELECT * FROM links WHERE short_code = ?');
    $stmt->execute([$code]);
    $link = $stmt->fetch();
    return $link ?: null;
}

function incrementVisitCount(int $linkId): void {
    getPDO()->prepare('UPDATE links SET visit_count = visit_count + 1 WHERE id = ?')->execute([$linkId]);
}

function getUserLinks(int $userId): array {
    $stmt = getPDO()->prepare('SELECT * FROM links WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllLinks(): array {
    return getPDO()->query('
        SELECT l.*, u.username as created_by
        FROM links l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
    ')->fetchAll();
}

function deleteLink(int $linkId, int $userId): bool {
    $stmt = getPDO()->prepare('DELETE FROM links WHERE id = ? AND user_id = ?');
    $stmt->execute([$linkId, $userId]);
    return $stmt->rowCount() > 0;
}

function deleteLinkAdmin(int $linkId): bool {
    $stmt = getPDO()->prepare('DELETE FROM links WHERE id = ?');
    $stmt->execute([$linkId]);
    return $stmt->rowCount() > 0;
}

function updateLink(int $linkId, int $userId, array $updates): bool {
    $allowed = ['original_url', 'short_code', 'expires_at', 'max_visits', 'disabled', 'remark'];
    $sets = [];
    $values = [];
    foreach ($updates as $key => $value) {
        if (in_array($key, $allowed)) {
            $sets[] = "$key = ?";
            $values[] = $value;
        }
    }
    if (empty($sets)) return false;
    
    $values[] = $linkId;
    $sql = 'UPDATE links SET ' . implode(', ', $sets) . ' WHERE id = ?';
    if ($userId !== 1) {
        $sql .= ' AND user_id = ?';
        $values[] = $userId;
    }
    $stmt = getPDO()->prepare($sql);
    $stmt->execute($values);
    return $stmt->rowCount() > 0;
}

function transferLinkOwnership(int $linkId, int $newUserId): bool {
    $stmt = getPDO()->prepare('UPDATE links SET user_id = ? WHERE id = ?');
    $stmt->execute([$newUserId, $linkId]);
    return $stmt->rowCount() > 0;
}

function checkShortCodeExists(string $code): bool {
    $stmt = getPDO()->prepare('SELECT id FROM links WHERE short_code = ?');
    $stmt->execute([$code]);
    return (bool) $stmt->fetch();
}

// Initialize on first load
initDatabase();
