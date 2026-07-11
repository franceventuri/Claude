<?php
require_once __DIR__ . '/config.php';

function isLoggedIn(): bool
{
    return !empty($_SESSION['authenticated']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/') || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Sessione scaduta, effettua di nuovo il login.']);
            exit;
        }
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function requireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Token di sicurezza non valido, ricarica la pagina.']);
        exit;
    }
}
