<section class="narrow">
  <h1>Reset a password</h1>
  <p class="lede">We send a same-day token to campus mail. If you already have the token, open the reset form from that message.</p>
  <?php if (!empty($notice)): ?><p class="flash ok"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/forgot" class="stack">
    <label><span>Email</span><input type="email" name="email" required></label>
    <button type="submit">Request reset</button>
  </form>
  <p class="lede"><a href="/reset">I already have a token</a></p>
</section>
