<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$site = site();
$transakcija = query_string('transakcija');
if (!in_array($transakcija, ['', 'prodaja', 'najam'], true)) {
    $transakcija = '';
}
$tip = query_string('tip');
if (!isset(tip_opcije()[$tip]) && $tip !== '') {
    $tip = '';
}
$gradovi = gradovi();
$grad = query_string('grad');
if (!in_array($grad, $gradovi, true)) {
    $grad = '';
}

$sort = query_string('sort');
if (!in_array($sort, ['', 'cijena-rastuce', 'cijena-opadajuce'], true)) {
    $sort = '';
}

$filtered = public_listings([
    'transakcija' => $transakcija,
    'tip' => $tip,
    'grad' => $grad,
]);
if ($sort !== '') {
    $filtered = sort_by_price($filtered, $sort === 'cijena-opadajuce');
}
$perPage = 12;
$pages = max(1, (int) ceil(count($filtered) / $perPage));
$page = max(1, (int) query_string('stranica'));
if ($page > $pages) {
    $page = $pages;
}
$slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);
$count = count($filtered);
$countLabel = $count . ' ' . plural_en($count, 'property', 'properties');

$naslov = 'All';
$akcent = 'properties';
if ($transakcija === 'prodaja') {
    $naslov = 'Properties';
    $akcent = 'for sale';
} elseif ($transakcija === 'najam') {
    $naslov = 'Properties';
    $akcent = 'for rent';
}

$aktivniFilteri = count(array_filter([$transakcija, $tip, $grad, $sort], static fn (string $v): bool => $v !== ''));

$pageTitle = 'Properties';
$current = 'nekretnine';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="pill">Listings</p>
    <h1><?= accent_title($naslov, $akcent) ?></h1>
    <p class="count"><?= e($countLabel) ?><?= $grad !== '' ? ' · ' . e($grad) : '' ?></p>
  </div>
</section>

<details class="filters-bar" open data-filters data-active="<?= e((string) $aktivniFilteri) ?>">
  <summary class="filters-summary wrap">
    <span><?= icon('search') ?>Filters<?php if ($aktivniFilteri > 0): ?> <span class="filters-count"><?= e((string) $aktivniFilteri) ?></span><?php endif; ?></span>
    <?= icon('chevron-down') ?>
  </summary>
  <form class="wrap filters" method="get" action="<?= e(url('nekretnine.php')) ?>" data-auto-submit>
    <label class="field">Sale or rent
      <select name="transakcija">
        <option value="">All</option>
        <option value="prodaja"<?= selected($transakcija === 'prodaja') ?>>Sale</option>
        <option value="najam"<?= selected($transakcija === 'najam') ?>>Rent</option>
      </select>
    </label>
    <label class="field">Type
      <select name="tip">
        <option value="">All types</option>
        <?php foreach (tip_opcije_en() as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= selected($tip === $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">City
      <select name="grad">
        <option value="">All cities</option>
        <?php foreach ($gradovi as $name): ?>
          <option value="<?= e($name) ?>"<?= selected($grad === $name) ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">Sort
      <select name="sort">
        <option value="">Recommended</option>
        <option value="cijena-rastuce"<?= selected($sort === 'cijena-rastuce') ?>>Price: low to high</option>
        <option value="cijena-opadajuce"<?= selected($sort === 'cijena-opadajuce') ?>>Price: high to low</option>
      </select>
    </label>
    <div class="filter-actions">
      <noscript><button class="btn" type="submit"><?= icon('search') ?>Show</button></noscript>
      <?php if ($aktivniFilteri > 0): ?>
        <a class="btn btn-ghost" href="<?= e(url('nekretnine.php')) ?>">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</details>

<section class="wrap section section-tight">
  <?php if ($slice === []): ?>
    <p class="empty">There are no listings in this category right now.</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($slice as $oglas): ?>
        <?php include __DIR__ . '/includes/card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Listing pages">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e(query_url('nekretnine.php', [
            'transakcija' => $transakcija,
            'tip' => $tip,
            'grad' => $grad,
            'sort' => $sort,
            'stranica' => $i > 1 ? $i : '',
        ])) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= e((string) $i) ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
