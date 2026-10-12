<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? "Locker") ?> · Locker</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <div class="shell">
    <header class="top">
      <a class="brand" href="/">
        <span class="mark">Lk</span>
        <span>
          <strong>Locker</strong>
          <em>Shared secrets for one student org</em>
        </span>
      </a>
      <nav>
        <a href="/">Vault</a>
        <?php if ($currentUser): ?>
          <a href="/items/new">Store</a>
          <form class="inline" method="post" action="/logout">
            <button type="submit">Sign out</button>
          </form>
        <?php else: ?>
          <a href="/login">Sign in</a>
          <a class="btn" href="/register">Create account</a>
        <?php endif; ?>
      </nav>
    </header>
    <main>
