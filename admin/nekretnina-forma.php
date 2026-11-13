<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = query_string('id');
$existing = null;
if ($id !== '') {
    $existing = find_listing($id, true);
}

if ($id !== '' && !$existing) {
    http_response_code(404);
    $site = site();
    $pageTitle = 'Listing not found';
    $adminCurrent = 'oglasi';
    require __DIR__ . '/../includes/admin-header.php';
    echo '<header class="page-head"><h1>Listing not found</h1><a class="btn" href="' . e(url('admin/index.php')) . '">Back to listings</a></header>';
    require __DIR__ . '/../includes/admin-footer.php';
    exit;
}

$errors = [];
$form = $existing ? listing_to_form($existing) : blank_listing_form();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = form_from_post();
    if ($existing) {
        $form['id'] = (string) $existing['id'];
        $form['slike'] = listing_images($existing);
    }
    if (!csrf_ok()) {
        $errors[] = 'Invalid request. Refresh the page and try again.';
    } else {
        $errors = save_listing_from_post($existing);
    }
}

$site = site();
$pageTitle = $existing ? 'Edit listing' : 'New listing';
$adminCurrent = 'oglasi';
require __DIR__ . '/../includes/admin-header.php';
?>
<p class="back"><a href="<?= e(url('admin/index.php')) ?>">All listings</a></p>
<header class="page-head">
  <h1><?= e($pageTitle) ?></h1>
</header>

<?php if ($errors !== []): ?>
  <ul class="errors" role="alert">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form class="form form-panel" method="post" enctype="multipart/form-data" action="<?= e(url('admin/nekretnina-forma.php' . ($existing ? '?id=' . rawurlencode((string) $existing['id']) : ''))) ?>">
  <?= csrf_field() ?>
  <?php if ($existing): ?>
    <input type="hidden" name="id" value="<?= e((string) $existing['id']) ?>">
  <?php endif; ?>

  <label class="field">Title
    <input name="naslov" required maxlength="140" value="<?= e($form['naslov']) ?>">
  </label>

  <div class="grid-2">
    <label class="field">Type
      <select name="tip" required>
        <?php foreach (tip_opcije() as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= selected($form['tip'] === $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">Subtype
      <select name="podtip">
        <option value="">No subtype</option>
        <?php foreach (podtip_opcije() as $tip => $podtipovi): ?>
          <?php foreach ($podtipovi as $value => $label): ?>
            <option value="<?= e($value) ?>" data-tip="<?= e($tip) ?>"<?= selected($form['podtip'] === $value && $form['tip'] === $tip) ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <div class="grid-2">
    <label class="field">Sale or rent
      <select name="transakcija" required>
        <option value="prodaja"<?= selected($form['transakcija'] === 'prodaja') ?>>Sale</option>
        <option value="najam"<?= selected($form['transakcija'] === 'najam') ?>>Rent</option>
      </select>
    </label>
  </div>

  <div class="grid-2">
    <label class="field">Price
      <input name="cijena" inputmode="numeric" maxlength="15" value="<?= e($form['cijena']) ?>" placeholder="185000, or leave empty for On request">
    </label>
    <label class="field">Currency
      <select name="valuta">
        <option value="BAM"<?= selected($form['valuta'] === 'BAM') ?>>KM</option>
        <option value="EUR"<?= selected($form['valuta'] === 'EUR') ?>>EUR</option>
      </select>
    </label>
  </div>

  <div class="grid-2">
    <label class="field">City
      <input name="grad" required maxlength="80" value="<?= e($form['grad']) ?>">
    </label>
    <label class="field">Location
      <input name="lokacija" maxlength="120" value="<?= e($form['lokacija']) ?>" placeholder="Neighborhood or street">
    </label>
  </div>

  <div class="grid-2">
    <label class="field">Area (m²)
      <input name="povrsina" inputmode="decimal" maxlength="12" value="<?= e($form['povrsina']) ?>">
    </label>
    <label class="field">Number of rooms
      <input name="sobe" inputmode="numeric" maxlength="2" value="<?= e($form['sobe']) ?>">
    </label>
  </div>

  <label class="field">Description
    <textarea name="opis" required maxlength="5000" rows="8"><?= e($form['opis']) ?></textarea>
  </label>

  <div class="grid-2">
    <label class="field">Status
      <select name="status">
        <option value="aktivno"<?= selected($form['status'] === 'aktivno') ?>>Active</option>
        <option value="rezervisano"<?= selected($form['status'] === 'rezervisano') ?>>Reserved</option>
        <option value="prodato"<?= selected($form['status'] === 'prodato') ?>>Sold / rented</option>
      </select>
    </label>
    <div class="checks">
      <label class="check"><input type="checkbox" name="istaknuto"<?= checked((bool) $form['istaknuto']) ?>> Feature on the homepage</label>
      <label class="check"><input type="checkbox" name="sakriveno"<?= checked((bool) $form['sakriveno']) ?>> Hide from the site</label>
    </div>
  </div>

  <?php if ($form['slike'] !== []): ?>
    <fieldset class="photos">
      <legend>Current images</legend>
      <ul class="admin-photos">
        <?php foreach ($form['slike'] as $index => $image): ?>
          <li>
            <img src="<?= e(url($image)) ?>" alt="">
            <label class="check"><input type="radio" name="naslovna" value="<?= e($image) ?>"<?= checked($index === 0) ?>> Cover</label>
            <label class="check"><input type="checkbox" name="obrisi[]" value="<?= e($image) ?>"> Delete</label>
          </li>
        <?php endforeach; ?>
      </ul>
    </fieldset>
  <?php endif; ?>

  <label class="field">New images
    <input type="file" name="slike[]" accept="image/jpeg,image/png,image/webp" multiple data-upload data-existing="<?= e((string) count($form['slike'])) ?>">
  </label>
  <ul class="admin-photos upload-preview" data-upload-preview hidden></ul>
  <p class="upload-info" data-upload-info hidden></p>
  <p class="help">Up to 12 images per listing. JPG, PNG or WEBP, 8 MB each. Large images are resized automatically. If there are many, add them in two steps.</p>

  <div class="form-actions">
    <button class="btn" type="submit"><?= $existing ? 'Save changes' : 'Add listing' ?></button>
    <a class="btn btn-ghost" href="<?= e(url('admin/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
