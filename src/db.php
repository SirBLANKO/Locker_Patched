<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = getenv("DATABASE_PATH") ?: (dirname(__DIR__) . "/data/locker.db");
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    $pdo = new PDO("sqlite:" . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode = WAL");
    $pdo->exec("PRAGMA busy_timeout = 5000");
    return $pdo;
}

function init_db(): void
{
    db()->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            display_name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'member',
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS vault_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id),
            label TEXT NOT NULL,
            secret TEXT NOT NULL,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS reset_tokens (
            email TEXT PRIMARY KEY,
            token TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
    ");
}

function seed(): void
{
    $pdo = db();
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $maya = create_user("maya@campus.edu", "Maya Chen", "campus123", "member");
        create_user("devon@campus.edu", "Devon Alvarez", "campus123", "member");
        $advisor = create_user("advisor@campus.edu", "Club Advisor", "not-in-the-readme", "advisor");

        add_vault_item($maya, "Shop door code", "4421#", "Works on the east stair after 6pm.");
        add_vault_item($maya, "Instagram login", "robotics.club / tape-measure", "2FA is Maya's phone.");
        add_vault_item(
            $advisor,
            "Advisor hold — do not share on the wall",
            "LOCKER-4410",
            "Emergency disbursement PIN for the student-org card. Rotate after spring banquet."
        );
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
