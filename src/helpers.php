<?php
// Small cross-cutting helpers used by every page.

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $envPath = env('APP_BASE_PATH');
    if ($envPath !== null) {
        $base = rtrim($envPath, '/');
        return $base;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*?/offpaper)#', $scriptName, $m)) {
        $base = $m[1];
        return $base;
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#^(.*?/offpaper)#', strtok($requestUri, '?'), $m)) {
        $base = $m[1];
        return $base;
    }

    $base = '';
    return $base;
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $base = base_path();
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return $base !== '' ? $base . '/' : '/';
    }
    return $base . $path;
}

function asset(string $path): string
{
    $relativePath = '/assets/' . ltrim($path, '/');
    $fullPath = APP_ROOT . '/public' . $relativePath;
    $v = file_exists($fullPath) ? filemtime($fullPath) : '1';
    return url($relativePath . '?v=' . $v);
}

function redirect(string $path): never
{
    $target = url($path);
    header('Location: ' . $target, true, 303);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool
{
    $submitted = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    return $expected !== '' && $submitted !== '' && hash_equals($expected, $submitted);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function generate_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

