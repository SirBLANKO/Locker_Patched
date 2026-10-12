<?php
declare(strict_types=1);

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_user(string $email, string $display, string $password, string $role): int
{
    $stmt = db()->prepare("INSERT INTO users (email, password_hash, display_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, hash_password($password), $display, $role]);
    return (int)db()->lastInsertId();
}

function set_user_password(string $email, string $password): void
{
    $stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([hash_password($password), $email]);
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION["user_id"] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

function current_user(): ?array
{
    if (empty($_SESSION["user_id"])) {
        return null;
    }
    $stmt = db()->prepare("SELECT id, email, display_name, role FROM users WHERE id = ?");
    $stmt->execute([(int)$_SESSION["user_id"]]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect("/login");
    }
}
