<?php

declare(strict_types=1);

$site = $site ?? site();
$pageTitle = $pageTitle ?? '';
$slogan = trim($site['slogan'] . ' ' . $site['slogan_akcent']);
$fullTitle = $pageTitle === ''
    ? trim($site['naziv'] . ($slogan !== '' ? ' — ' . $slogan : ''))
    : $pageTitle . ' — ' . $site['naziv'];
$metaDescription = $metaDescription ?? clip($site['uvod'] !== '' ? $site['uvod'] : $slogan, 160);
$cssVersion = (string) (@filemtime(ROOT . '/assets/style.css') ?: '1');
$jsVersion = (string) (@filemtime(ROOT . '/assets/app.js') ?: '1');
$bodyClass = trim($bodyClass ?? '');
$robots = $robots ?? '';
?>
<!DOCTYPE html>
<html lang="<?= e($htmlLang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if ($robots !== ''): ?>
    <meta name="robots" content="<?= e($robots) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="#0a0c10">
  <link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preload" href="<?= e(url('assets/fonts/outfit-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(url('assets/style.css?v=' . $cssVersion)) ?>">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
