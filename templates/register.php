<section class="narrow">
  <h1>Create a Locker account</h1>
  <?php if (!empty($error)): ?><p class="flash"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/register" class="stack">
    <label><span>Campus email</span><input type="email" name="email" value="<?= e($values["email"] ?? "") ?>" required></label>
    <label><span>Display name</span><input type="text" name="display_name" value="<?= e($values["display_name"] ?? "") ?>" required></label>
    <label><span>Password</span><input type="password" name="password" minlength="8" required></label>
    <button type="submit">Create account</button>
  </form>
</section>
