<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect_if_logged_in();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    } else {
        $errors = register_job_seeker($_POST);
        if (!$errors) {
            flash_set('success', 'Registration successful. Please log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register as Job Seeker';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <h2 class="mb-3">Job Seeker Registration</h2>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="full_name">Full name</label>
      <input class="form-control" id="full_name" name="full_name" value="<?= old('full_name') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="email">Email</label>
      <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="education_level">Education level</label>
      <select class="form-select" id="education_level" name="education_level" required>
        <option value="">-- choose --</option>
        <?php foreach (EDUCATION_LEVELS as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= ($_POST['education_level'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="mb-3"><label class="form-label" for="institution">Institution</label>
      <input class="form-control" id="institution" name="institution" value="<?= old('institution') ?>" required></div>
    <div class="mb-3"><label class="form-label" for="password">Password</label>
      <input class="form-control" type="password" id="password" name="password" required>
      <div class="form-text">Minimum 8 characters, with letters and numbers.</div></div>
    <div class="mb-3"><label class="form-label" for="password_confirm">Confirm password</label>
      <input class="form-control" type="password" id="password_confirm" name="password_confirm" required></div>
    <button class="btn btn-primary" type="submit">Create account</button>
    <a class="btn btn-link" href="<?= e(url('register_employer.php')) ?>">Register as employer instead</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
