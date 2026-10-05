<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect_if_logged_in();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    } else {
        $errors = register_employer($_POST);
        if (!$errors) {
            flash_set('success', 'Company registered successfully. Please log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register as Employer';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <h2 class="mb-3">Employer Registration</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="company_name">Company / organisation name</label>
      <input class="form-control" id="company_name" name="company_name" value="<?= old('company_name') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="email">Email</label>
      <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="industry_sector">Industry sector</label>
      <input class="form-control" id="industry_sector" name="industry_sector" value="<?= old('industry_sector') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="description">Company description (optional)</label>
      <textarea class="form-control" id="description" name="description" rows="3"><?= old('description') ?></textarea></div>
    <div class="mb-3"><label class="form-label" for="password">Password</label>
      <input class="form-control" type="password" id="password" name="password" required>
      <div class="form-text">Minimum 8 characters, with letters and numbers.</div></div>
    <div class="mb-3"><label class="form-label" for="password_confirm">Confirm password</label>
      <input class="form-control" type="password" id="password_confirm" name="password_confirm" required></div>
    <button class="btn btn-primary" type="submit">Create account</button>
    <a class="btn btn-link" href="<?= e(url('register_seeker.php')) ?>">Register as job seeker instead</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
