<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect_if_logged_in();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Invalid form submission. Please reload the page and try again.';
    } else {
        $error = attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
        if ($error === null) {
            redirect(dashboard_path(current_user()['role']));  // role-based redirect
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <h2 class="mb-3">Login</h2>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="email">Email</label>
      <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus></div>
    <div class="mb-3"><label class="form-label" for="password">Password</label>
      <input class="form-control" type="password" id="password" name="password" required></div>
    <button class="btn btn-primary" type="submit">Login</button>
    <a class="btn btn-link" href="<?= e(url('register_seeker.php')) ?>">Create an account</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?> 
