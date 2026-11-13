<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$site = site();
$values = [
    'ime' => '',
    'email' => '',
    'telefon' => '',
    'poruka' => '',
    'oglas' => '',
];
$oglas = null;
$requested = query_string('oglas');
if (valid_id($requested)) {
    $found = find_listing($requested);
    if ($found) {
        $oglas = $found;
        $values['oglas'] = (string) $found['id'];
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['ime'] = post_string('ime');
    $values['email'] = post_string('email');
    $values['telefon'] = post_string('telefon');
    $values['poruka'] = str_replace(["\r\n", "\r"], "\n", post_string('poruka'));
    $postedOglas = post_string('oglas');
    $oglas = valid_id($postedOglas) ? find_listing($postedOglas) : null;
    $values['oglas'] = $oglas ? (string) $oglas['id'] : '';

    if (!csrf_ok()) {
        $errors[] = 'Invalid request. Refresh the page and try again.';
    } elseif (post_string('company') !== '') {
        flash('Your message has been sent. We will be in touch soon.');
        redirect('kontakt.php');
    } elseif (mail_rate_limited()) {
        $errors[] = 'You have sent several messages. Call us at ' . $site['telefon'] . '.';
    } else {
        if (mb_strlen($values['ime']) < 2 || mb_strlen($values['ime']) > 80) {
            $errors[] = 'Enter your full name.';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || str_contains($values['email'], "\n")) {
            $errors[] = 'The email address is not valid.';
        }
        if ($values['telefon'] !== '' && !preg_match('/^[0-9+()\/\s.-]{5,40}$/', $values['telefon'])) {
            $errors[] = 'The phone number is not valid.';
        }
        if (mb_strlen($values['poruka']) < 5 || mb_strlen($values['poruka']) > 4000) {
            $errors[] = 'The message must be between 5 and 4,000 characters.';
        }
        if ($errors === []) {
            if (send_inquiry($site, $values, $oglas)) {
                mark_mail_sent();
                flash('Your message has been sent. We will be in touch soon.');
                redirect('kontakt.php');
            }
            $fallback = 'The message was not sent.';
            if ($site['telefon'] !== '') {
                $fallback .= ' Call us at ' . $site['telefon'] . '.';
            }
            if ($site['email'] !== '') {
                $fallback .= ' Or write to ' . $site['email'] . '.';
            }
            $errors[] = $fallback;
        }
    }
}

$pageTitle = 'Contact';
$current = 'kontakt';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <p class="pill">Contact</p>
    <h1>Get in <span class="accent">touch</span></h1>
    <?php if ($oglas): ?>
      <p class="lede">Inquiry about: <?= e((string) $oglas['naslov']) ?></p>
    <?php else: ?>
      <p class="lede">Write to us about a listing, a property valuation or a viewing, or call us directly.</p>
    <?php endif; ?>
  </div>
</section>

<section class="wrap section">
<div class="info-cards">
  <?php if ($site['adresa'] !== ''): ?>
    <div class="info-card">
      <span class="info-icon"><?= icon('pin') ?></span>
      <h3>Office</h3>
      <p><a href="<?= e(maps_href($site)) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['adresa']) ?></a></p>
    </div>
  <?php endif; ?>
  <?php if ($site['telefon'] !== ''): ?>
    <div class="info-card">
      <span class="info-icon"><?= icon('phone') ?></span>
      <h3>Phone</h3>
      <p><a href="<?= e(tel_href($site['telefon'])) ?>"><?= e($site['telefon']) ?></a></p>
    </div>
  <?php endif; ?>
  <?php if ($site['email'] !== ''): ?>
    <div class="info-card">
      <span class="info-icon"><?= icon('mail') ?></span>
      <h3>Email</h3>
      <p><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></p>
    </div>
  <?php endif; ?>
  <?php if ($site['radno_vrijeme'] !== ''): ?>
    <div class="info-card">
      <span class="info-icon"><?= icon('clock') ?></span>
      <h3>Hours</h3>
      <p><?= e($site['radno_vrijeme']) ?></p>
    </div>
  <?php endif; ?>
</div>

<div class="form-panel">
  <h2>Send an <span class="accent">inquiry</span></h2>
  <form class="form" method="post" action="<?= e(url('kontakt.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="oglas" value="<?= e($values['oglas']) ?>">
    <div class="hp" aria-hidden="true">
      <label>Company
        <input type="text" name="company" tabindex="-1" autocomplete="off" value="">
      </label>
    </div>
    <?php if ($errors !== []): ?>
      <ul class="errors" role="alert">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="grid-2">
      <label class="field">Full name
        <input name="ime" required maxlength="80" autocomplete="name" value="<?= e($values['ime']) ?>">
      </label>
      <label class="field">Email
        <input name="email" type="email" required maxlength="120" autocomplete="email" value="<?= e($values['email']) ?>">
      </label>
    </div>
    <label class="field">Phone
      <input name="telefon" maxlength="40" autocomplete="tel" value="<?= e($values['telefon']) ?>">
    </label>
    <label class="field">Message
      <textarea name="poruka" required maxlength="4000" rows="7"><?= e($values['poruka']) ?></textarea>
    </label>
    <div class="form-actions">
      <button class="btn" type="submit">Send message <?= icon('arrow-right') ?></button>
    </div>
  </form>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
