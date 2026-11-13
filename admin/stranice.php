<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$values = site();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = site_defaults();
    foreach ($values as $key => $_) {
        $values[$key] = str_replace(["\r\n", "\r"], "\n", post_string($key));
    }
    if (!csrf_ok()) {
        $errors[] = 'Invalid request. Refresh the page and try again.';
    } else {
        if (mb_strlen($values['naziv']) < 2 || mb_strlen($values['naziv']) > 80) {
            $errors[] = 'The site name must be between 2 and 80 characters.';
        }
        if (mb_strlen($values['slogan']) < 2 || mb_strlen($values['slogan']) > 80) {
            $errors[] = 'The first headline line must be between 2 and 80 characters.';
        }
        if (mb_strlen($values['slogan_akcent']) > 80) {
            $errors[] = 'The blue headline line can have at most 80 characters.';
        }
        if (mb_strlen($values['uvod']) < 2 || mb_strlen($values['uvod']) > 600) {
            $errors[] = 'The introduction must be between 2 and 600 characters.';
        }
        if (mb_strlen($values['o_nama']) < 2 || mb_strlen($values['o_nama']) > 8000) {
            $errors[] = 'The About text must be between 2 and 8,000 characters.';
        }
        if (mb_strlen($values['telefon']) < 5 || mb_strlen($values['telefon']) > 40) {
            $errors[] = 'Enter a phone number.';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The email address is not valid.';
        }
        if (mb_strlen($values['adresa']) < 2 || mb_strlen($values['adresa']) > 160) {
            $errors[] = 'Enter an address.';
        }
        if (mb_strlen($values['radno_vrijeme']) < 2 || mb_strlen($values['radno_vrijeme']) > 120) {
            $errors[] = 'Enter the opening hours.';
        }
        if ($values['maps_url'] !== '' && (!filter_var($values['maps_url'], FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $values['maps_url']) || mb_strlen($values['maps_url']) > 300)) {
            $errors[] = 'The map link must start with https://, or stay empty.';
        }
        if ($errors === []) {
            save_site($values);
            flash('The content has been saved.');
            redirect('admin/stranice.php');
        }
    }
}

$site = site();
$pageTitle = 'Content';
$adminCurrent = 'tekst';
require __DIR__ . '/../includes/admin-header.php';
?>
<header class="page-head">
  <p class="eyebrow"><?= e($site['naziv']) ?></p>
  <h1>Content</h1>
  <p class="help">These changes appear on the site immediately. The phone, email and address are also used on the contact page.</p>
</header>

<?php if ($errors !== []): ?>
  <ul class="errors" role="alert">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form class="form form-narrow form-panel" method="post" action="<?= e(url('admin/stranice.php')) ?>">
  <?= csrf_field() ?>
  <label class="field">Site name
    <input name="naziv" required maxlength="80" value="<?= e($values['naziv']) ?>">
  </label>
  <div class="grid-2">
    <label class="field">Homepage headline, first line
      <input name="slogan" required maxlength="80" value="<?= e($values['slogan']) ?>">
    </label>
    <label class="field">Second line, in blue
      <input name="slogan_akcent" maxlength="80" value="<?= e($values['slogan_akcent']) ?>">
    </label>
  </div>
  <label class="field">Homepage introduction
    <textarea name="uvod" required maxlength="600" rows="4"><?= e($values['uvod']) ?></textarea>
  </label>
  <label class="field">About
    <textarea name="o_nama" required maxlength="8000" rows="10"><?= e($values['o_nama']) ?></textarea>
  </label>
  <div class="grid-2">
    <label class="field">Phone
      <input name="telefon" required maxlength="40" value="<?= e($values['telefon']) ?>">
    </label>
    <label class="field">Email
      <input name="email" type="email" required maxlength="120" value="<?= e($values['email']) ?>">
    </label>
  </div>
  <label class="field">Address
    <input name="adresa" required maxlength="160" value="<?= e($values['adresa']) ?>">
  </label>
  <label class="field">Opening hours
    <input name="radno_vrijeme" required maxlength="120" value="<?= e($values['radno_vrijeme']) ?>">
  </label>
  <label class="field">Map link
    <input name="maps_url" maxlength="300" value="<?= e($values['maps_url']) ?>" placeholder="https://">
  </label>
  <p class="help">If the link is left empty, the map opens a search for the address.</p>
  <button class="btn" type="submit">Save content</button>
</form>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
