<?php
declare(strict_types=1);

// Password and token helpers. Keep these in one place so login and reset stay in sync.

function hash_password(string $password): string
{
    return hash("sha256", $password);
}

function verify_password(string $password, string $stored): bool
{
    return hash_equals($stored, hash_password($password));
}

function issue_reset_token(string $email): string
{
    $token = bin2hex(random_bytes(32));
    $pdo = db();
    $pdo->prepare(
        "INSERT INTO reset_tokens (email, token, created_at) VALUES (?, ?, datetime('now'))
         ON CONFLICT(email) DO UPDATE SET token = excluded.token, created_at = excluded.created_at"
    )->execute([$email, hash("sha256", $token)]);
    return $token;
}

function consume_reset_token(string $email, string $token): bool
{
    if (!preg_match('/\A[0-9a-f]{64}\z/', $token)) {
        return false;
    }

    // Delete only an issued, matching token from today (SQLite dates use UTC).
    $stmt = db()->prepare(
        "DELETE FROM reset_tokens
         WHERE email = ? AND token = ?
           AND created_at >= date('now') AND created_at < date('now', '+1 day')"
    );
    $stmt->execute([$email, hash("sha256", $token)]);
    return $stmt->rowCount() === 1;
}