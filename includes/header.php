<?php
/** Shared page header. Set $pageTitle before including. */
$user = current_user();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ?? 'Home') . ' | ' . APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/theme.css')) ?>" rel="stylesheet">
  <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-md navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e(APP_NAME) ?> logo"
           height="36" style="height:36px; width:auto; max-height:36px;">
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?php if ($user): ?>
        <span class="navbar-text me-2"><?= e($user['name']) ?></span>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url(dashboard_path($user['role']))) ?>">Dashboard</a>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="m-0">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-dark" type="submit">Logout</button>
        </form>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('login.php')) ?>">Login</a>
        <a class="btn btn-sm btn-dark" href="<?= e(url('register_seeker.php')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container my-4 flex-grow-1">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>
