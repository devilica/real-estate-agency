<?php

declare(strict_types=1);

$robots = 'noindex, nofollow';
$bodyClass = trim(($bodyClass ?? '') . ' is-admin');
require __DIR__ . '/document-start.php';
$adminCurrent = $adminCurrent ?? '';
$flash = take_flash();
?>
<header class="admin-bar">
  <div class="wrap header-bar">
    <a class="brand" href="<?= e(url('admin/index.php')) ?>">
      <?= logo_img() ?>
      <span>Admin</span>
    </a>
    <nav id="admin-nav" class="nav" aria-label="Admin">
      <a href="<?= e(url('admin/index.php')) ?>"<?= $adminCurrent === 'oglasi' ? ' aria-current="page"' : '' ?>>Listings</a>
      <a href="<?= e(url('admin/stranice.php')) ?>"<?= $adminCurrent === 'tekst' ? ' aria-current="page"' : '' ?>>Content</a>
      <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View site</a>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>">
        <?= csrf_field() ?>
        <button class="linkish linkish-danger" type="submit"><?= icon('logout') ?>Log out</button>
      </form>
    </nav>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="admin-nav">Menu</button>
  </div>
</header>
<main id="sadrzaj" class="wrap">
<?php if (!empty(config()['debug'])): ?>
  <p class="banner warn">Local setup. Before uploading to hosting, set a new password in config.php and set debug to false.</p>
<?php endif; ?>
<?php if ($flash): ?>
  <p class="flash <?= $flash['type'] === 'err' ? 'err' : 'ok' ?>" role="<?= $flash['type'] === 'err' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
<?php endif; ?>
