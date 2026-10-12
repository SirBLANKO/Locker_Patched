<section class="narrow">
  <h1>Store a secret</h1>
  <?php if (!empty($error)): ?><p class="flash"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/items" class="stack">
    <label><span>Label</span><input type="text" name="label" value="<?= e($values["label"] ?? "") ?>" required></label>
    <label><span>Secret</span><input type="text" name="secret" value="<?= e($values["secret"] ?? "") ?>" required></label>
    <label><span>Notes</span><textarea name="notes"><?= e($values["notes"] ?? "") ?></textarea></label>
    <button type="submit">Save to my shelf</button>
  </form>
</section>
