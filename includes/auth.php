<?php

function isLoggedIn(): bool {
    return isset($_SESSION['userId']);
}

function isAdmin(): bool {
    return isset($_SESSION['userId']) && $_SESSION['userId'] === 1;
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => '仅管理员可操作']);
        exit;
    }
}
