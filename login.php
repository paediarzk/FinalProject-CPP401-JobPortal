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

$pageTitle = 'Log in';
require __DIR__ . '/includes/auth_header.php';
?>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-sub">Log in to continue to <?= e(APP_NAME) ?>.</p>

      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>"
                 autocomplete="email" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="pw-field">
            <input class="form-control" type="password" id="password" name="password"
                   autocomplete="current-password" required>
            <button type="button" class="pw-toggle" data-toggle-password="password" aria-pressed="false">Show</button>
          </div>
        </div>
        <button class="btn btn-brand w-100" type="submit">Log in</button>
      </form>

      <p class="auth-switch">New to <?= e(APP_NAME) ?>? <a href="<?= e(url('register_seeker.php')) ?>">Create an account</a></p>
<?php require __DIR__ . '/includes/auth_footer.php'; ?>
