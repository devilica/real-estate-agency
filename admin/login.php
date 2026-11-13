<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    redirect('admin/index.php');
}

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$error = '';
$notice = take_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Invalid request. Refresh the page and try again.';
    } else {
        $error = attempt_login(post_string('lozinka')) ?? '';
        if ($error === '') {
            redirect('admin/index.php');
        }
    }
}

$site = site();
$pageTitle = 'Log in';
$bodyClass = 'is-auth';
$robots = 'noindex, nofollow';
require __DIR__ . '/../includes/document-start.php';
?>
<main class="auth-shell" id="sadrzaj">
  <img class="auth-bg" src="<?= e(url('assets/naslovna.png')) ?>" alt="">
  <div class="auth-shade"></div>
  <form class="auth-card" method="post" action="<?= e(url('admin/login.php')) ?>">
    <a class="brand" href="<?= e(url()) ?>">
      <?= logo_img() ?>
    </a>
    <h1>Log in</h1>
    <p class="help">Sign in to manage the properties.</p>
    <p class="auth-hint">This is a test site. Password: <strong>admin</strong></p>
    <?php if (query_string('odjava') === '1'): ?>
      <p class="flash ok" role="status">You have been logged out.</p>
    <?php elseif ($notice): ?>
      <p class="flash <?= $notice['type'] === 'err' ? 'err' : 'ok' ?>" role="<?= $notice['type'] === 'err' ? 'alert' : 'status' ?>"><?= e($notice['message']) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <p class="flash err" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <?= csrf_field() ?>
    <label class="field">Password
      <span class="pass-field">
        <span class="pass-icon"><?= icon('lock') ?></span>
        <input name="lozinka" type="password" required autocomplete="current-password" autofocus placeholder="Enter your password">
        <button class="pass-toggle" type="button" data-pass-toggle aria-label="Show password" aria-pressed="false">
          <span data-eye><?= icon('eye') ?></span>
          <span data-eye-off hidden><?= icon('eye-off') ?></span>
        </button>
      </span>
    </label>
    <button class="btn" type="submit"><?= icon('login') ?>Log in</button>
    <a class="auth-back" href="<?= e(url()) ?>"><?= icon('home') ?>ELITE ESTATE Real Estate Agency · Admin</a>
  </form>
</main>
<?php require __DIR__ . '/../includes/document-end.php'; ?>
