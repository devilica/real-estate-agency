<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$site = site();
$oglasi = public_listings();
$istaknuti = array_values(array_filter($oglasi, static fn (array $oglas): bool => !empty($oglas['istaknuto'])));
$izdvojeno = array_slice($istaknuti !== [] ? $istaknuti : $oglasi, 0, 7);
$izdvojeniId = array_map(static fn (array $oglas): string => (string) $oglas['id'], $izdvojeno);
$ostali = array_values(array_filter($oglasi, static fn (array $oglas): bool => !in_array((string) $oglas['id'], $izdvojeniId, true)));
$popuna = $izdvojeno === [] ? 0 : (3 - (count($izdvojeno) - 1) % 3) % 3;
if ($popuna > 0) {
    $izdvojeno = array_merge($izdvojeno, array_splice($ostali, 0, $popuna));
}
$ostali = array_slice($ostali, 0, 6);

$broj = count($oglasi);
$prodaja = count(array_filter($oglasi, static fn (array $oglas): bool => ($oglas['transakcija'] ?? '') !== 'najam'));
$najam = $broj - $prodaja;
$gradovi = gradovi();
$grad = '';
if (preg_match('/,\s*([^,]+)$/u', $site['adresa'], $m)) {
    $grad = trim($m[1]);
}

$pageTitle = '';
$current = 'home';
$bodyClass = 'page-home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <img class="hero-bg" src="<?= e(url('assets/naslovna.png')) ?>" alt="" fetchpriority="high">
  <div class="hero-shade"></div>
  <div class="wrap hero-inner">
    <div class="hero-copy">
      <p class="hero-kicker"><?= icon('pin') ?><span class="hero-kicker-text"><?= e($site['naziv']) ?><?= $grad !== '' ? ' · ' . e($grad) : '' ?></span></p>
      <h1><?= accent_title($site['slogan'], $site['slogan_akcent']) ?></h1>
      <?php if ($site['uvod'] !== ''): ?>
        <p class="lede"><?= e($site['uvod']) ?></p>
      <?php endif; ?>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('nekretnine.php')) ?>">View listings <?= icon('arrow-right') ?></a>
        <a class="btn btn-ghost" href="<?= e(url('kontakt.php')) ?>">Contact us</a>
      </div>
    </div>

    <form class="quick-search" method="get" action="<?= e(url('nekretnine.php')) ?>" aria-label="Quick search" data-auto-submit>
      <label class="field">Sale or rent
        <select name="transakcija">
          <option value="">All</option>
          <option value="prodaja">Sale</option>
          <option value="najam">Rent</option>
        </select>
      </label>
      <label class="field">Property type
        <select name="tip">
          <option value="">All types</option>
          <?php foreach (tip_opcije_en() as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">City
        <select name="grad">
          <option value="">All cities</option>
          <?php foreach ($gradovi as $name): ?>
            <option value="<?= e($name) ?>"><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <noscript><button class="btn" type="submit"><?= icon('search') ?>Search</button></noscript>
    </form>
  </div>
</section>

<section class="wrap stats-band">
  <div class="stats">
    <div class="stat"><span class="stat-icon"><?= icon('home') ?></span><span class="stat-text"><span class="stat-num"><?= e((string) $broj) ?></span><span class="stat-label"><?= e(plural_en($broj, 'active listing', 'active listings')) ?></span></span></div>
    <div class="stat"><span class="stat-icon"><?= icon('tag') ?></span><span class="stat-text"><span class="stat-num"><?= e((string) $prodaja) ?></span><span class="stat-label">for sale</span></span></div>
    <div class="stat"><span class="stat-icon"><?= icon('key') ?></span><span class="stat-text"><span class="stat-num"><?= e((string) $najam) ?></span><span class="stat-label">for rent</span></span></div>
  </div>
</section>

<section class="wrap section">
  <div class="section-head">
    <div>
      <p class="pill"><?= icon('star') ?><?= $istaknuti !== [] ? 'Featured' : 'Listings' ?></p>
      <h2><?= $istaknuti !== [] ? 'Featured <span class="accent">listings</span>' : 'Current <span class="accent">listings</span>' ?></h2>
    </div>
    <a class="text-link" href="<?= e(url('nekretnine.php')) ?>">All listings <?= icon('arrow-right') ?></a>
  </div>
  <?php if ($izdvojeno === []): ?>
    <p class="empty">No listings published yet.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($izdvojeno as $index => $oglas): ?>
        <?php $lead = $index === 0; ?>
        <?php include __DIR__ . '/includes/card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($ostali !== []): ?>
  <section class="wrap section section-tight">
    <div class="section-head">
      <div>
        <p class="pill">From the portfolio</p>
        <h2>More <span class="accent">properties</span></h2>
      </div>
      <a class="text-link" href="<?= e(url('nekretnine.php')) ?>">View all <?= icon('arrow-right') ?></a>
    </div>
    <div class="cards">
      <?php foreach ($ostali as $oglas): ?>
        <?php include __DIR__ . '/includes/card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="wrap section">
  <div class="about-teaser">
    <div>
      <p class="pill">About</p>
      <h2><?= e($site['naziv']) ?></h2>
      <div class="prose"><?= paragraphs(clip($site['o_nama'], 420)) ?></div>
      <a class="text-link" href="<?= e(url('o-nama.php')) ?>">More about the agency <?= icon('arrow-right') ?></a>
    </div>
    <div class="contact-strip">
      <h3>Looking to buy or sell?</h3>
      <?php if ($site['telefon'] !== ''): ?>
        <a class="contact-line contact-big" href="<?= e(tel_href($site['telefon'])) ?>"><?= icon('phone') ?><?= e($site['telefon']) ?></a>
      <?php endif; ?>
      <?php if ($site['email'] !== ''): ?>
        <a class="contact-line" href="mailto:<?= e($site['email']) ?>"><?= icon('mail') ?><?= e($site['email']) ?></a>
      <?php endif; ?>
      <?php if ($site['adresa'] !== ''): ?>
        <a class="contact-line" href="<?= e(maps_href($site)) ?>" target="_blank" rel="noopener noreferrer"><?= icon('pin') ?><?= e($site['adresa']) ?></a>
      <?php endif; ?>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('kontakt.php')) ?>">Send an inquiry</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
