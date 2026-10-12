<?php
declare(strict_types=1);

function vault_items_for(array $user): array
{
    $stmt = db()->prepare(
        "SELECT id, label, secret, notes, created_at FROM vault_items WHERE user_id = ? ORDER BY id"
    );
    $stmt->execute([(int)$user["id"]]);
    return $stmt->fetchAll();
}

function add_vault_item(int $userId, string $label, string $secret, string $notes): void
{
    $stmt = db()->prepare("INSERT INTO vault_items (user_id, label, secret, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $label, $secret, $notes]);
}
