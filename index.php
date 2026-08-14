<?php
session_name('shortlink_sid');
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 系统保留字，不允许用作短链代码
$reserved = ['api', 'index', 'index.php', 'favicon.ico', 'robots.txt', '.well-known', 'login', 'logout'];

// ==================== API Routes ====================

if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    $path = substr($uri, 5);

    // Match parameterized routes
    $linkIdMatch = null;
    $userIdMatch = null;
    if (preg_match('#^links/(\d+)$#', $path, $linkIdMatch)) {
        $routeType = 'link';
    } elseif (preg_match('#^links/(\d+)/owner$#', $path, $linkIdMatch)) {
        $routeType = 'linkOwner';
    } elseif (preg_match('#^users/(\d+)$#', $path, $userIdMatch)) {
        $routeType = 'user';
    } elseif (preg_match('#^users/(\d+)/reset-password$#', $path, $userIdMatch)) {
        $routeType = 'resetPassword';
    } else {
        $routeType = $path;
    }

    try {
        switch (true) {
            // ======== Auth ========
            case $routeType === 'login' && $method === 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                if (!$username || !$password) {
                    http_response_code(400);
                    echo json_encode(['error' => '请输入用户名和密码']);
                    break;
                }
                $user = getUserByUsername($username);
                if (!$user || !verifyPassword($password, $user['password_hash'])) {
                    http_response_code(401);
                    echo json_encode(['error' => '用户名或密码错误']);
                    break;
                }
                $_SESSION['userId'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                echo json_encode(['success' => true, 'username' => $user['username']]);
                break;

            case $routeType === 'logout' && $method === 'POST':
                session_destroy();
                echo json_encode(['success' => true]);
                break;

            case $routeType === 'me' && $method === 'GET':
                requireAuth();
                echo json_encode([
                    'username' => $_SESSION['username'],
                    'userId' => $_SESSION['userId'],
                    'isAdmin' => isAdmin(),
                ]);
                break;

            // ======== Links ========
            case $routeType === 'links' && $method === 'GET':
                requireAuth();
                $baseUrl = getBaseUrl();
                $links = isAdmin() ? getAllLinks() : getUserLinks($_SESSION['userId']);
                $result = array_map(function($l) use ($baseUrl) {
                    $l['short_url'] = "$baseUrl/{$l['short_code']}";
                    return $l;
                }, $links);
                echo json_encode($result);
                break;

            case $routeType === 'links' && $method === 'POST':
                requireAuth();
                $input = json_decode(file_get_contents('php://input'), true);
                $originalUrl = trim($input['original_url'] ?? '');
                if (!$originalUrl || !filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                    http_response_code(400);
                    echo json_encode(['error' => '请输入有效的 URL']);
                    break;
                }
                $shortCode = $input['custom_code'] ?? '';
                if ($shortCode !== '' && $shortCode !== null) {
                    if (!preg_match('/^[a-zA-Z0-9_-]{2,20}$/', $shortCode)) {
                        http_response_code(400);
                        echo json_encode(['error' => '自定义短链只能包含字母、数字、下划线和连字符，长度 2-20']);
                        break;
                    }
                    if (checkShortCodeExists($shortCode)) {
                        http_response_code(400);
                        echo json_encode(['error' => '该短链代码已被占用']);
                        break;
                    }
                    if (in_array(strtolower($shortCode), $reserved)) {
                        http_response_code(400);
                        echo json_encode(['error' => '该代码为系统保留字，不可使用']);
                        break;
                    }
                } else {
                    $shortCode = generateUniqueCode();
                }
                $expiresAt = !empty($input['expires_at']) ? $input['expires_at'] : null;
                $maxVisits = isset($input['max_visits']) && $input['max_visits'] !== '' ? (int)$input['max_visits'] : null;
                $result = createLink($_SESSION['userId'], $originalUrl, $shortCode, $expiresAt, $maxVisits);
                echo json_encode([
                    'success' => true,
                    'link' => [
                        'id' => $result['lastInsertRowid'],
                        'short_code' => $shortCode,
                        'short_url' => getBaseUrl() . "/$shortCode",
                        'original_url' => $originalUrl,
                        'expires_at' => $expiresAt,
                        'max_visits' => $maxVisits,
                        'visit_count' => 0,
                    ],
                ]);
                break;

            case $routeType === 'link' && $method === 'DELETE':
                requireAuth();
                $id = (int)$linkIdMatch[1];
                $ok = isAdmin() ? deleteLinkAdmin($id) : deleteLink($id, $_SESSION['userId']);
                if (!$ok) {
                    http_response_code(404);
                    echo json_encode(['error' => '链接不存在']);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case $routeType === 'link' && $method === 'PUT':
                requireAuth();
                $id = (int)$linkIdMatch[1];
                $input = json_decode(file_get_contents('php://input'), true);
                $updates = [];
                if (isset($input['original_url'])) $updates['original_url'] = trim($input['original_url']);
                if (isset($input['short_code'])) {
                    if (!preg_match('/^[a-zA-Z0-9_-]{2,20}$/', $input['short_code'])) {
                        http_response_code(400);
                        echo json_encode(['error' => '短链代码格式无效']);
                        break;
                    }
                    if (in_array(strtolower($input['short_code']), $reserved)) {
                        http_response_code(400);
                        echo json_encode(['error' => '该代码为系统保留字，不可使用']);
                        break;
                    }
                    $updates['short_code'] = $input['short_code'];
                }
                if (array_key_exists('expires_at', $input)) $updates['expires_at'] = $input['expires_at'] ?: null;
                if (array_key_exists('max_visits', $input)) $updates['max_visits'] = $input['max_visits'] ? (int)$input['max_visits'] : null;
                if (array_key_exists('disabled', $input)) $updates['disabled'] = $input['disabled'] ? 1 : 0;
                if (array_key_exists('remark', $input)) $updates['remark'] = $input['remark'] ?? '';
                $ok = updateLink($id, $_SESSION['userId'], $updates);
                if (!$ok) {
                    http_response_code(404);
                    echo json_encode(['error' => '链接不存在']);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case $routeType === 'linkOwner' && $method === 'PUT':
                requireAuth();
                requireAdmin();
                $id = (int)$linkIdMatch[1];
                $input = json_decode(file_get_contents('php://input'), true);
                $newUserId = (int)($input['user_id'] ?? 0);
                if (!$newUserId || !getUserById($newUserId)) {
                    http_response_code(404);
                    echo json_encode(['error' => '目标用户不存在']);
                    break;
                }
                $ok = transferLinkOwnership($id, $newUserId);
                if (!$ok) {
                    http_response_code(404);
                    echo json_encode(['error' => '链接不存在']);
                } else {
                    $targetUser = getUserById($newUserId);
                    echo json_encode(['success' => true, 'newOwner' => $targetUser['username']]);
                }
                break;

            // ======== Users ========
            case $routeType === 'users' && $method === 'GET':
                requireAuth();
                requireAdmin();
                $users = getAllUsers();
                $result = array_map(function($u) {
                    $u['link_count'] = getUserLinkCount($u['id']);
                    return $u;
                }, $users);
                echo json_encode($result);
                break;

            case $routeType === 'users' && $method === 'POST':
                requireAuth();
                requireAdmin();
                $input = json_decode(file_get_contents('php://input'), true);
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                if (!$username || !$password) {
                    http_response_code(400);
                    echo json_encode(['error' => '请输入用户名和密码']);
                    break;
                }
                if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20}$/u', $username)) {
                    http_response_code(400);
                    echo json_encode(['error' => '用户名格式无效']);
                    break;
                }
                if (strlen($password) < 3) {
                    http_response_code(400);
                    echo json_encode(['error' => '密码至少 3 位']);
                    break;
                }
                if (getUserByUsername($username)) {
                    http_response_code(400);
                    echo json_encode(['error' => '用户名已存在']);
                    break;
                }
                $newId = createUser($username, $password);
                echo json_encode(['success' => true, 'userId' => $newId, 'username' => $username]);
                break;

            case $routeType === 'user' && $method === 'DELETE':
                requireAuth();
                requireAdmin();
                $id = (int)$userIdMatch[1];
                if ($id === 1) {
                    http_response_code(400);
                    echo json_encode(['error' => '不能删除管理员账号']);
                    break;
                }
                if (!deleteUser($id)) {
                    http_response_code(404);
                    echo json_encode(['error' => '用户不存在']);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case $routeType === 'resetPassword' && $method === 'POST':
                requireAuth();
                requireAdmin();
                $id = (int)$userIdMatch[1];
                $input = json_decode(file_get_contents('php://input'), true);
                $newPwd = $input['newPassword'] ?? '';
                if (strlen($newPwd) < 3) {
                    http_response_code(400);
                    echo json_encode(['error' => '密码至少 3 位']);
                    break;
                }
                if (!resetPassword($id, $newPwd)) {
                    http_response_code(404);
                    echo json_encode(['error' => '用户不存在']);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            // ======== Password ========
            case $routeType === 'me/password' && $method === 'PUT':
                requireAuth();
                $input = json_decode(file_get_contents('php://input'), true);
                $oldPwd = $input['oldPassword'] ?? '';
                $newPwd = $input['newPassword'] ?? '';
                if (!$oldPwd || !$newPwd) {
                    http_response_code(400);
                    echo json_encode(['error' => '请输入原密码和新密码']);
                    break;
                }
                if (strlen($newPwd) < 3) {
                    http_response_code(400);
                    echo json_encode(['error' => '新密码至少 3 位']);
                    break;
                }
                $result = changePassword($_SESSION['userId'], $oldPwd, $newPwd);
                if (!$result['success']) {
                    http_response_code(400);
                    echo json_encode(['error' => $result['error']]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            // ======== Check Code ========
            case $routeType === 'check-code' && $method === 'GET':
                requireAuth();
                $code = $_GET['code'] ?? '';
                if (!$code || !preg_match('/^[a-zA-Z0-9_-]{2,20}$/', $code)) {
                    echo json_encode(['available' => false, 'message' => '格式无效']);
                } else {
                    $exists = checkShortCodeExists($code);
                    echo json_encode(['available' => !$exists, 'message' => $exists ? '该代码已被占用' : '可用']);
                }
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => '接口不存在']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '服务器内部错误: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== Short Link Redirect ====================
// 放在 API 之后以免吃掉 /api 路径。
if ($uri !== '/' && preg_match('#^/([a-zA-Z0-9_-]{2,20})$#', $uri, $m) && !in_array(strtolower($m[1]), $reserved)) {
    try {
        $link = getLinkByCode($m[1]);
        if (!$link) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>链接不存在</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}.box{text-align:center;background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.1)}h1{color:#e53e3e;margin:0 0 10px}p{color:#666}</style></head><body><div class="box"><h1>404</h1><p>短链接不存在或已被删除</p></div></body></html>';
            exit;
        }
        if (!empty($link['disabled'])) {
            http_response_code(410);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>已失效</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}.box{text-align:center;background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.1)}h1{color:#e53e3e;margin:0 0 10px}p{color:#666}</style></head><body><div class="box"><h1>已失效</h1><p>该短链接已被停用</p></div></body></html>';
            exit;
        }
        if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
            http_response_code(410);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>已过期</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}.box{text-align:center;background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.1)}h1{color:#e53e3e;margin:0 0 10px}p{color:#666}</style></head><body><div class="box"><h1>已过期</h1><p>该短链接已超过有效期</p></div></body></html>';
            exit;
        }
        if ($link['max_visits'] !== null && $link['visit_count'] >= $link['max_visits']) {
            http_response_code(410);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>已达上限</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}.box{text-align:center;background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.1)}h1{color:#e53e3e;margin:0 0 10px}p{color:#666}</style></head><body><div class="box"><h1>已达上限</h1><p>该短链接已达到最大访问次数</p></div></body></html>';
            exit;
        }
        incrementVisitCount($link['id']);
        header('Location: ' . $link['original_url']);
        exit;
    } catch (Exception $e) {
        error_log('[ShortLink] Redirect error: ' . $e->getMessage());
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>服务异常</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}.box{text-align:center;background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.1)}h1{color:#e53e3e;margin:0 0 10px}p{color:#666;font-size:14px}</style></head><body><div class="box"><h1>500</h1><p>服务器暂时无法处理请求，请稍后重试</p></div></body></html>';
        exit;
    }
}

// ==================== HTML Pages ====================

if (isLoggedIn()) {
    require __DIR__ . '/templates/dashboard.php';
} else {
    require __DIR__ . '/templates/login.php';
}

// ==================== Helpers ====================

function getBaseUrl(): string {
    // 优先使用反向代理传递的真实 Host（兼容 Nginx/Apache/CDN）
    $host = $_SERVER['HTTP_X_FORWARDED_HOST']
         ?? $_SERVER['HTTP_HOST']
         ?? $_SERVER['SERVER_NAME']
         ?? 'localhost';

    // 去掉端口号，避免出现 example.com:8080 这样的短链
    if (($pos = strpos($host, ':')) !== false) {
        $host = substr($host, 0, $pos);
    }

    // 优先使用反向代理传递的真实协议
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }

    return "$scheme://$host";
}

function generateUniqueCode(int $length = 4): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $attempts = 0;
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $attempts++;
        if ($attempts > 50) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            break;
        }
    } while (checkShortCodeExists($code));
    return $code;
}
