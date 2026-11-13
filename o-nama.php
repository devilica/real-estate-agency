<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$site = site();
$pageTitle = 'About';
$current = 'o-nama';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="pill">About</p>
    <h1>Our story and <span class="accent">approach</span></h1>
    <p class="lede"><?= e($site['uvod']) ?></p>
  </div>
</section>

<section class="wrap section">
  <div class="split">
    <div class="prose about-body"><?= paragraphs($site['o_nama']) ?></div>
    <aside class="info-panel">
      <h2><?= e($site['naziv']) ?></h2>
      <dl class="specs">
        <?php if ($site['adresa'] !== ''): ?>
          <div><dt><?= icon('pin') ?>Address</dt><dd><?= e($site['adresa']) ?></dd></div>
        <?php endif; ?>
        <?php if ($site['telefon'] !== ''): ?>
          <div><dt><?= icon('phone') ?>Phone</dt><dd><a href="<?= e(tel_href($site['telefon'])) ?>"><?= e($site['telefon']) ?></a></dd></div>
        <?php endif; ?>
        <?php if ($site['email'] !== ''): ?>
          <div><dt><?= icon('mail') ?>Email</dt><dd><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></dd></div>
        <?php endif; ?>
        <?php if ($site['radno_vrijeme'] !== ''): ?>
          <div><dt><?= icon('clock') ?>Hours</dt><dd><?= e($site['radno_vrijeme']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('kontakt.php')) ?>">Send an inquiry</a>
        <a class="btn btn-ghost" href="<?= e(maps_href($site)) ?>" target="_blank" rel="noopener noreferrer"><?= icon('pin') ?>Map</a>
      </div>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
