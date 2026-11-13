<?php

declare(strict_types=1);
?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <a href="<?= e(url()) ?>"><?= logo_img() ?></a>
      <p><?= e(trim($site['slogan'] . ' ' . $site['slogan_akcent'])) ?></p>
    </div>
    <div>
      <p class="footer-title">Contact</p>
      <?php if ($site['telefon'] !== ''): ?>
        <p><a class="footer-line" href="<?= e(tel_href($site['telefon'])) ?>"><?= icon('phone') ?><?= e($site['telefon']) ?></a></p>
      <?php endif; ?>
      <?php if ($site['email'] !== ''): ?>
        <p><a class="footer-line" href="mailto:<?= e($site['email']) ?>"><?= icon('mail') ?><?= e($site['email']) ?></a></p>
      <?php endif; ?>
      <?php if ($site['adresa'] !== ''): ?>
        <p><a class="footer-line" href="<?= e(maps_href($site)) ?>" target="_blank" rel="noopener noreferrer"><?= icon('pin') ?><?= e($site['adresa']) ?></a></p>
      <?php endif; ?>
    </div>
    <div>
      <p class="footer-title">Listings</p>
      <p><a href="<?= e(url('nekretnine.php?transakcija=prodaja')) ?>">For sale</a></p>
      <p><a href="<?= e(url('nekretnine.php?transakcija=najam')) ?>">For rent</a></p>
      <?php if ($site['radno_vrijeme'] !== ''): ?>
        <p class="footer-line"><?= icon('clock') ?><?= e($site['radno_vrijeme']) ?></p>
      <?php endif; ?>
    </div>
  </div>
  <div class="wrap footer-note">
    <p>© <?= e(date('Y')) ?> <?= e($site['naziv']) ?></p>
    <p><a href="<?= e(url('admin/login.php')) ?>">Log in</a></p>
  </div>
</footer>
<?php require __DIR__ . '/document-end.php'; ?>
