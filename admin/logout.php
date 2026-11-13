<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
    redirect(is_admin() ? 'admin/index.php' : 'admin/login.php');
}

logout_admin();
header('Location: ' . url('admin/login.php?odjava=1'));
exit;
