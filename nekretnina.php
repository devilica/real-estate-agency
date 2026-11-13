<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$site = site();
$id = query_string('id');
$oglas = find_listing($id);
$current = 'nekretnine';

if (!$oglas) {
    http_response_code(404);
    $pageTitle = 'Listing not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <p class="pill">404</p>
        <h1>Listing not <span class="accent">found</span></h1>
        <p class="lede">It may have been removed, or the link is incomplete.</p>
        <p><a class="btn" href="<?= e(url('nekretnine.php')) ?>">All properties</a></p>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$images = listing_images($oglas);
$status = status_label_en($oglas);
$lokacija = trim((string) ($oglas['lokacija'] ?? ''));
$grad = trim((string) ($oglas['grad'] ?? ''));
$mjesto = $lokacija !== '' && $grad !== '' ? $lokacija . ', ' . $grad : ($lokacija !== '' ? $lokacija : $grad);
$m2 = format_m2_en($oglas['povrsina'] ?? null);
$sobe = format_sobe_en($oglas['sobe'] ?? null);
$pageTitle = (string) $oglas['naslov'];
$metaDescription = clip(preg_replace('/\s+/', ' ', (string) ($oglas['opis'] ?? '')) ?? '', 160);

require __DIR__ . '/includes/header.php';
?>
<section class="wrap section section-tight">
  <p class="back"><a href="<?= e(url('nekretnine.php')) ?>"><?= icon('arrow-left') ?>All properties</a></p>
  <article class="detail">
    <div class="detail-media">
      <?php if ($images !== []): ?>
        <div class="gallery" data-gallery>
          <div class="gallery-frame">
            <img class="gallery-main" src="<?= e(url($images[0])) ?>" alt="<?= e((string) $oglas['naslov']) ?>">
            <?php if (count($images) > 1): ?>
              <button class="gal-btn gal-prev" type="button" data-gal="prev" aria-label="Previous image"><?= icon('arrow-left') ?></button>
              <button class="gal-btn gal-next" type="button" data-gal="next" aria-label="Next image"><?= icon('arrow-right') ?></button>
              <span class="gal-count" aria-live="polite"><span data-gal-index>1</span> / <?= e((string) count($images)) ?></span>
            <?php endif; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <div class="thumbs">
              <?php foreach ($images as $index => $image): ?>
                <button type="button" data-src="<?= e(url($image)) ?>" class="<?= $index === 0 ? 'is-current' : '' ?>" aria-label="Image <?= e((string) ($index + 1)) ?>">
                  <img src="<?= e(url($image)) ?>" alt="" loading="lazy">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="gallery-frame photo-fallback"><?= mark_svg() ?></div>
      <?php endif; ?>
    </div>
    <aside class="detail-aside">
      <ul class="badges badges-inline">
        <li class="badge <?= ($oglas['transakcija'] ?? '') === 'najam' ? 'badge-rent' : 'badge-sale' ?>"><?= e(transakcija_label_en((string) ($oglas['transakcija'] ?? 'prodaja'))) ?></li>
        <li class="badge"><?= e(tip_prikaz_en($oglas)) ?></li>
        <?php if ($status !== ''): ?>
          <li class="badge badge-status"><?= e($status) ?></li>
        <?php endif; ?>
      </ul>
      <h1><?= e((string) $oglas['naslov']) ?></h1>
      <p class="price-lg"><?= e(format_cijena_en($oglas)) ?></p>
      <dl class="specs">
        <?php if ($mjesto !== ''): ?>
          <div><dt><?= icon('pin') ?>Location</dt><dd><?= e($mjesto) ?></dd></div>
        <?php endif; ?>
        <?php if ($m2 !== ''): ?>
          <div><dt><?= icon('area') ?>Area</dt><dd><?= e($m2) ?></dd></div>
        <?php endif; ?>
        <?php if ($sobe !== ''): ?>
          <div><dt><?= icon('bed') ?>Rooms</dt><dd><?= e($sobe) ?></dd></div>
        <?php endif; ?>
      </dl>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('kontakt.php?oglas=' . rawurlencode((string) $oglas['id']))) ?>">Send an inquiry</a>
        <?php if ($site['telefon'] !== ''): ?>
          <a class="btn btn-ghost" href="<?= e(tel_href($site['telefon'])) ?>"><?= icon('phone') ?>Call</a>
        <?php endif; ?>
      </div>
      <?php if ($site['telefon'] !== ''): ?>
        <p class="aside-note"><?= e($site['naziv']) ?> · <?= e($site['telefon']) ?></p>
      <?php endif; ?>
    </aside>
    <div class="detail-copy">
      <h2>Description</h2>
      <div class="prose"><?= paragraphs((string) ($oglas['opis'] ?? '')) ?></div>
    </div>
  </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
