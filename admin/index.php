<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anchor = '';
    if (!csrf_ok()) {
        flash('Invalid request. Please try again.', 'err');
    } elseif (isset($_POST['redoslijed'])) {
        $ids = $_POST['redoslijed'];
        if (is_array($ids) && reorder_listings(array_values($ids))) {
            flash('The order has been saved.');
        } else {
            flash('The order was not saved.', 'err');
        }
    } elseif (post_string('pomjeri_id') !== '') {
        $id = post_string('pomjeri_id');
        $step = post_string('smjer') === 'gore' ? -1 : 1;
        if (valid_id($id) && move_listing($id, $step)) {
            $anchor = '#oglas-' . $id;
        }
    } elseif (post_string('obrisi_id') !== '') {
        delete_listing(post_string('obrisi_id'));
    } elseif (post_string('sakrij_id') !== '') {
        toggle_listing_hidden(post_string('sakrij_id'));
        if (valid_id(post_string('sakrij_id'))) {
            $anchor = '#oglas-' . post_string('sakrij_id');
        }
    }
    redirect('admin/index.php' . $anchor);
}

$site = site();
$oglasi = all_listings();
$total = count($oglasi);
$pageTitle = 'Listings';
$adminCurrent = 'oglasi';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="section-head">
  <div>
    <p class="pill"><?= e((string) $total) ?> <?= e(plural_en($total, 'listing', 'listings')) ?></p>
    <h1>Listings</h1>
    <?php if ($total > 1): ?>
      <p class="help">The order here is the order on the site. Drag a listing by the handle on the left, or use the arrows.</p>
    <?php endif; ?>
  </div>
  <a class="btn" href="<?= e(url('admin/nekretnina-forma.php')) ?>">New listing</a>
</div>

<!-- <?php if (has_example_listings()): ?>
  <p class="banner">Sample listings are here so you can see the layout. Delete them before the site goes live.</p>
<?php endif; ?> -->

<?php if ($oglasi === []): ?>
  <p class="empty">There are no listings yet. Add the first one.</p>
<?php else: ?>
  <ul class="admin-list" data-sortable>
    <?php foreach ($oglasi as $index => $oglas): ?>
      <?php
        $id = (string) $oglas['id'];
        $images = listing_images($oglas);
        $cover = $images[0] ?? null;
        $hidden = !empty($oglas['sakriveno']);
        $grad = trim((string) ($oglas['grad'] ?? ''));
      ?>
      <li class="admin-item<?= $hidden ? ' is-hidden' : '' ?>" id="oglas-<?= e($id) ?>" data-id="<?= e($id) ?>">
        <span class="sort-tools">
          <form class="move-form" method="post" action="<?= e(url('admin/index.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="pomjeri_id" value="<?= e($id) ?>">
            <input type="hidden" name="smjer" value="gore">
            <button class="move-btn" type="submit" aria-label="Move up"<?= $index === 0 ? ' disabled' : '' ?>><?= icon('chevron-up') ?></button>
          </form>
          <button class="drag-handle" type="button" aria-label="Drag to change the order" data-drag><?= icon('grip') ?></button>
          <span class="order-num" data-order-num><?= e((string) ($index + 1)) ?></span>
          <form class="move-form" method="post" action="<?= e(url('admin/index.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="pomjeri_id" value="<?= e($id) ?>">
            <input type="hidden" name="smjer" value="dolje">
            <button class="move-btn" type="submit" aria-label="Move down"<?= $index === $total - 1 ? ' disabled' : '' ?>><?= icon('chevron-down') ?></button>
          </form>
        </span>
        <span class="admin-thumb">
          <?php if ($cover): ?>
            <img src="<?= e(url($cover)) ?>" alt="" loading="lazy" draggable="false">
          <?php else: ?>
            <span class="photo-fallback"><?= mark_svg() ?></span>
          <?php endif; ?>
        </span>
        <span class="admin-copy">
          <span class="kicker"><?= e(transakcija_label((string) ($oglas['transakcija'] ?? 'prodaja'))) ?> · <?= e(tip_prikaz($oglas)) ?></span>
          <strong><?= e((string) $oglas['naslov']) ?></strong>
          <span class="meta"><?= e(format_cijena($oglas)) ?><?= $grad !== '' ? ' · ' . e($grad) : '' ?></span>
        </span>
        <span class="admin-actions">
          <?php if ($hidden || !empty($oglas['istaknuto']) || status_label($oglas) !== ''): ?>
            <span class="badges badges-inline">
              <?php if ($hidden): ?>
                <span class="badge badge-muted">Hidden</span>
              <?php endif; ?>
              <?php if (!empty($oglas['istaknuto'])): ?>
                <span class="badge badge-featured"><?= icon('star') ?>Featured</span>
              <?php endif; ?>
              <?php if (status_label($oglas) !== ''): ?>
                <span class="badge badge-status"><?= e(status_label($oglas)) ?></span>
              <?php endif; ?>
            </span>
          <?php endif; ?>
          <a class="btn btn-small btn-ghost" href="<?= e(url('admin/nekretnina-forma.php?id=' . rawurlencode($id))) ?>"><?= icon('pencil') ?>Edit</a>
          <form method="post" action="<?= e(url('admin/index.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="sakrij_id" value="<?= e($id) ?>">
            <button class="btn btn-small btn-ghost" type="submit"><?= $hidden ? icon('eye') . 'Publish' : icon('eye-off') . 'Hide' ?></button>
          </form>
          <form method="post" action="<?= e(url('admin/index.php')) ?>" data-confirm="Delete this listing? Its images will be deleted with it.">
            <?= csrf_field() ?>
            <input type="hidden" name="obrisi_id" value="<?= e($id) ?>">
            <button class="btn btn-small btn-danger" type="submit"><?= icon('trash') ?>Delete</button>
          </form>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="sort-bar" data-sort-bar hidden>
    <p>The order has changed.</p>
    <form method="post" action="<?= e(url('admin/index.php')) ?>" data-sort-form>
      <?= csrf_field() ?>
      <a class="btn btn-small btn-ghost" href="<?= e(url('admin/index.php')) ?>">Cancel</a>
      <button class="btn btn-small" type="submit">Save order</button>
    </form>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
