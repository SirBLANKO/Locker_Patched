<section class="hero">
  <h1>One ring for the codes the group chat keeps losing.</h1>
  <p class="lede">Door pins, social logins, and the binder that used to live in the advisor's desk. Each account only sees its own shelf.</p>
</section>

<?php if (!$currentUser): ?>
  <p class="lede">Sign in to open your shelf. Demo accounts are in the README.</p>
<?php elseif (!$items): ?>
  <p class="lede">Your shelf is empty. <a href="/items/new">Store a secret</a>.</p>
<?php else: ?>
  <ul class="cards">
    <?php foreach ($items as $item): ?>
      <li class="card">
        <h2><?= e($item["label"]) ?></h2>
        <p class="secret"><?= e($item["secret"]) ?></p>
        <?php if ($item["notes"]): ?>
          <p class="notes"><?= e($item["notes"]) ?></p>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
