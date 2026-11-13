<?php

declare(strict_types=1);

/** @var array $oglas */
$images = listing_images($oglas);
$cover = $images[0] ?? null;
$closed = (($oglas['status'] ?? '') === 'prodato');
$status = status_label_en($oglas);
$featured = !empty($oglas['istaknuto']);
$lead = !empty($lead);
$grad = trim((string) ($oglas['grad'] ?? ''));
$lokacija = trim((string) ($oglas['lokacija'] ?? ''));
$mjesto = $lokacija !== '' && $grad !== '' ? $lokacija . ', ' . $grad : ($lokacija !== '' ? $lokacija : $grad);
$m2 = format_m2_en($oglas['povrsina'] ?? null);
$sobe = format_sobe_en($oglas['sobe'] ?? null);
$classes = 'card' . ($featured ? ' is-featured' : '') . ($closed ? ' is-closed' : '') . ($lead ? ' is-lead' : '');
?>
<article class="<?= e($classes) ?>">
  <a class="card-link" href="<?= e(url('nekretnina.php?id=' . rawurlencode((string) $oglas['id']))) ?>">
    <span class="card-media">
      <?php if ($cover): ?>
        <img src="<?= e(url($cover)) ?>" alt="<?= e((string) $oglas['naslov']) ?>" loading="<?= $lead ? 'eager' : 'lazy' ?>">
      <?php else: ?>
        <span class="photo-fallback"><?= mark_svg() ?></span>
      <?php endif; ?>
      <span class="badges">
        <span class="badge <?= ($oglas['transakcija'] ?? '') === 'najam' ? 'badge-rent' : 'badge-sale' ?>"><?= e(transakcija_label_en((string) ($oglas['transakcija'] ?? 'prodaja'))) ?></span>
        <?php if ($status !== ''): ?>
          <span class="badge badge-status"><?= e($status) ?></span>
        <?php endif; ?>
        <?php if ($featured): ?>
          <span class="badge badge-featured"><?= icon('star') ?>Featured</span>
        <?php endif; ?>
      </span>
      <span class="price-chip"><?= e(format_cijena_en($oglas)) ?></span>
    </span>
    <div class="card-body">
      <span class="kicker"><?= e(tip_prikaz_en($oglas)) ?></span>
      <h3><?= e((string) $oglas['naslov']) ?></h3>
      <?php if ($mjesto !== ''): ?>
        <span class="loc"><?= icon('pin') ?><?= e($mjesto) ?></span>
      <?php endif; ?>
      <?php if ($lead && trim((string) ($oglas['opis'] ?? '')) !== ''): ?>
        <p class="card-desc"><?= e(clip(preg_replace('/\s+/', ' ', (string) $oglas['opis']) ?? '', 220)) ?></p>
      <?php endif; ?>
      <?php if ($m2 !== '' || $sobe !== ''): ?>
        <span class="specs-row">
          <?php if ($m2 !== ''): ?>
            <span><?= icon('area') ?><?= e($m2) ?></span>
          <?php endif; ?>
          <?php if ($sobe !== ''): ?>
            <span><?= icon('bed') ?><?= e($sobe) ?></span>
          <?php endif; ?>
        </span>
      <?php endif; ?>
      <?php if ($lead): ?>
        <span class="card-cta">View listing <?= icon('arrow-right') ?></span>
      <?php endif; ?>
    </div>
  </a>
</article>
<?php $lead = false; ?>
