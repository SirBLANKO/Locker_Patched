<?php
declare(strict_types=1);

require dirname(__DIR__) . "/src/bootstrap.php";

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($path === "/health") {
    json_out(["ok" => true]);
}

if ($path === "/" && $method === "GET") {
    render("home", ["title" => "Locker", "items" => current_user() ? vault_items_for(current_user()) : []]);
}

if ($path === "/login" && $method === "GET") {
    render("login", ["title" => "Sign in", "email" => "", "error" => null]);
}

if ($path === "/login" && $method === "POST") {
    $email = strtolower(trim((string)($_POST["email"] ?? "")));
    $password = (string)($_POST["password"] ?? "");
    $user = find_user_by_email($email);
    if (!$user || !verify_password($password, $user["password_hash"])) {
        render("login", ["title" => "Sign in", "email" => $email, "error" => "Those credentials do not match a Locker account."], 401);
    }
    login_user((int)$user["id"]);
    redirect("/");
}

if ($path === "/register" && $method === "GET") {
    render("register", ["title" => "Create account", "values" => [], "error" => null]);
}

if ($path === "/register" && $method === "POST") {
    $email = strtolower(trim((string)($_POST["email"] ?? "")));
    $display = trim((string)($_POST["display_name"] ?? ""));
    $password = (string)($_POST["password"] ?? "");
    $values = ["email" => $email, "display_name" => $display];
    if (!str_ends_with($email, ".edu")) {
        render("register", ["title" => "Create account", "values" => $values, "error" => "Use a .edu email so the locker stays on campus."]);
    }
    if (strlen($display) < 2 || strlen($password) < 8) {
        render("register", ["title" => "Create account", "values" => $values, "error" => "Name and an 8+ character password are required."]);
    }
    if (find_user_by_email($email)) {
        render("register", ["title" => "Create account", "values" => $values, "error" => "That email already has an account."]);
    }
    $id = create_user($email, $display, $password, "member");
    login_user($id);
    redirect("/");
}

if ($path === "/logout" && $method === "POST") {
    logout_user();
    redirect("/");
}

if ($path === "/forgot" && $method === "GET") {
    render("forgot", ["title" => "Reset password", "notice" => null]);
}

if ($path === "/forgot" && $method === "POST") {
    $email = strtolower(trim((string)($_POST["email"] ?? "")));
    if (find_user_by_email($email)) {
        issue_reset_token($email);
    }
    render("forgot", [
        "title" => "Reset password",
        "notice" => "If that address is in Locker, a reset link was queued for campus mail. Tokens expire at the end of the calendar day.",
    ]);
}

if ($path === "/reset" && $method === "GET") {
    render("reset", [
        "title" => "Choose a new password",
        "email" => (string)($_GET["email"] ?? ""),
        "token" => (string)($_GET["token"] ?? ""),
        "error" => null,
    ]);
}

if ($path === "/reset" && $method === "POST") {
    $email = strtolower(trim((string)($_POST["email"] ?? "")));
    $token = trim((string)($_POST["token"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    if (strlen($password) < 8) {
        render("reset", [
            "title" => "Choose a new password",
            "email" => $email,
            "token" => $token,
            "error" => "Password needs at least eight characters."
        ]);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if (!consume_reset_token($email, $token)) {
            $pdo->rollBack();
            render("reset", [
                "title" => "Choose a new password",
                "email" => $email,
                "token" => $token,
                "error" => "That reset token is not valid for this address today."
            ]);
        }

        set_user_password($email, $password);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    render("login", [
        "title" => "Sign in",
        "email" => $email,
        "error" => "Password updated. Sign in."
    ]);
}

if ($path === "/items/new" && $method === "GET") {
    require_login();
    render("new_item", ["title" => "Store a secret", "error" => null, "values" => []]);
}

if ($path === "/items" && $method === "POST") {
    require_login();
    $user = current_user();
    $label = trim((string)($_POST["label"] ?? ""));
    $secret = trim((string)($_POST["secret"] ?? ""));
    $notes = trim((string)($_POST["notes"] ?? ""));
    if (strlen($label) < 2 || strlen($secret) < 2) {
        render("new_item", ["title" => "Store a secret", "error" => "Label and secret are required.", "values" => compact("label", "secret", "notes")]);
    }
    add_vault_item((int)$user["id"], $label, $secret, $notes);
    redirect("/");
}

http_response_code(404);
render("error", ["title" => "Missing", "message" => "That page is not in Locker."], 404);
