<?php
// Email/password + Google account handling, session lifecycle, and the dashboard guard.

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => normalize_email($email)]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_google_sub(string $sub): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE google_sub = :sub');
    $stmt->execute(['sub' => $sub]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function auth_signup(string $email, string $password): array
{
    $email = normalize_email($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (find_user_by_email($email) !== null) {
        return ['ok' => false, 'error' => 'An account with this email already exists. Try logging in instead.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO users (email, password_hash) VALUES (:email, :hash) RETURNING id'
    );
    $stmt->execute([
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $id = (int) $stmt->fetchColumn();

    login_user($id);
    return ['ok' => true];
}

function auth_login(string $email, string $password): array
{
    $generic = 'Invalid email or password.';
    $user = find_user_by_email($email);

    if ($user === null) {
        return ['ok' => false, 'error' => $generic];
    }
    if ($user['password_hash'] === null) {
        return ['ok' => false, 'error' => 'This account uses Google Sign-In. Continue with Google instead.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => $generic];
    }

    login_user((int) $user['id']);
    return ['ok' => true];
}

function auth_login_with_google(array $profile): array
{
    $sub = $profile['sub'];
    $email = normalize_email($profile['email']);

    $user = find_user_by_google_sub($sub);
    if ($user !== null) {
        login_user((int) $user['id']);
        return ['ok' => true];
    }

    if (empty($profile['email_verified'])) {
        return ['ok' => false, 'error' => 'Your Google email is not verified.'];
    }

    $user = find_user_by_email($email);
    if ($user !== null) {
        $stmt = db()->prepare('UPDATE users SET google_sub = :sub WHERE id = :id');
        $stmt->execute(['sub' => $sub, 'id' => $user['id']]);
        login_user((int) $user['id']);
        return ['ok' => true];
    }

    $stmt = db()->prepare(
        'INSERT INTO users (email, name, google_sub) VALUES (:email, :name, :sub) RETURNING id'
    );
    $stmt->execute([
        'email' => $email,
        'name' => $profile['name'] ?? null,
        'sub' => $sub,
    ]);
    login_user((int) $stmt->fetchColumn());
    return ['ok' => true];
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

function current_user(): ?array
{
    static $user = false; // false = not looked up yet, null = looked up, no user

    if ($user === false) {
        if (empty($_SESSION['user_id'])) {
            $user = null;
        } else {
            $stmt = db()->prepare('SELECT id, email, name FROM users WHERE id = :id');
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }

    return $user;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('/login.php');
    }
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
