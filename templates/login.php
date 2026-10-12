<section class="narrow">
  <h1>Sign in</h1>
  <?php if (!empty($error)): ?><p class="flash"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/login" class="stack">
    <label><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label><span>Password</span><input type="password" name="password" required></label>
    <button type="submit">Sign in</button>
  </form>
  <p class="lede"><a href="/forgot">Forgot password</a> · <a href="/register">Create account</a></p>
</section>
