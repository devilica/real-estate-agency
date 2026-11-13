<?php

declare(strict_types=1);

require __DIR__ . '/document-start.php';
$current = $current ?? '';
$flash = take_flash();
?>
<a class="skip" href="#sadrzaj">Skip to content</a>
<header class="site-header">
  <div class="wrap header-bar">
    <a class="brand" href="<?= e(url()) ?>">
      <?= logo_img() ?>
    </a>
    <nav id="site-nav" class="nav" aria-label="Main menu">
      <a href="<?= e(url()) ?>"<?= $current === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
      <a href="<?= e(url('nekretnine.php')) ?>"<?= $current === 'nekretnine' ? ' aria-current="page"' : '' ?>>Properties</a>
      <a href="<?= e(url('o-nama.php')) ?>"<?= $current === 'o-nama' ? ' aria-current="page"' : '' ?>>About</a>
      <a href="<?= e(url('kontakt.php')) ?>"<?= $current === 'kontakt' ? ' aria-current="page"' : '' ?>>Contact</a>
    </nav>
    <div class="header-end">
      <?php if ($site['telefon'] !== ''): ?>
        <a class="phone-btn" href="<?= e(tel_href($site['telefon'])) ?>" aria-label="Call <?= e($site['telefon']) ?>">
          <?= icon('phone') ?><span><?= e($site['telefon']) ?></span>
        </a>
      <?php endif; ?>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
    </div>
  </div>
</header>
<main id="sadrzaj">
<?php if ($flash): ?>
  <div class="wrap">
    <p class="flash <?= $flash['type'] === 'err' ? 'err' : 'ok' ?>" role="<?= $flash['type'] === 'err' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
  </div>
<?php endif; ?>
