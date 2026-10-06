<?php
/**
 * Layout for guest pages (login / register): split-screen.
 * Set these BEFORE including this file:
 *   $pageTitle  (required)  - text in the browser tab
 *   $heroTitle  (optional)  - headline on the left panel
 *   $heroText   (optional)  - short paragraph under the headline
 */
$flash     = flash_get();
$heroTitle = $heroTitle ?? 'Take the first step toward your career.';
$heroText  = $heroText  ?? 'Internships and entry-level jobs for students and fresh graduates, all in one place.';
$heroImage = 'assets/img/auth-hero.png';                       // the picture YOU prepare
$hasHero   = is_file(__DIR__ . '/../' . $heroImage);           // page still works if it is missing
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ?? 'Welcome') . ' | ' . APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/theme.css')) ?>" rel="stylesheet">
  <link href="<?= e(url('assets/css/auth.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="auth-layout">

  <!-- LEFT: hero (desktop only) -->
  <aside class="auth-hero">
    <a class="auth-hero__logo" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e(APP_NAME) ?>">
    </a>
    <div class="auth-hero__copy">
      <p class="auth-hero__title"><?= e($heroTitle) ?></p>
      <p class="auth-hero__text"><?= e($heroText) ?></p>
    </div>
    <div class="auth-hero__art" aria-hidden="true">
      <span class="art-disc art-disc--orange"></span>
      <span class="art-disc art-disc--blue"></span>
      <?php if ($hasHero): ?>
        <img class="art-person" src="<?= e(url($heroImage)) ?>" alt="">
      <?php endif; ?>
      <div class="art-chip art-chip--a">
        <span class="art-chip__dot"></span>
        <div><strong>Internships &amp; first jobs</strong><span class="sub">in one place</span></div>
      </div>
      <div class="art-chip art-chip--b">
        <span class="art-chip__dot"></span>
        <div><strong>Open all year</strong><span class="sub">no batch or quota limits</span></div>
      </div>
    </div>
  </aside>

  <!-- RIGHT: form -->
  <main class="auth-panel">
    <div class="auth-box">
      <a class="auth-mobile-logo" href="<?= e(url('index.php')) ?>">
        <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e(APP_NAME) ?>">
      </a>
<?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>
