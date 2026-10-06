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

$pageTitle = 'Create your account';
$heroTitle = 'Create a profile once. Apply to many vacancies.';
$heroText  = 'Search internships and entry-level jobs, apply, and follow every application in one place.';
require __DIR__ . '/includes/auth_header.php';
?>
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-sub">Join <?= e(APP_NAME) ?> to find internships and entry-level jobs.</p>

      <nav class="role-switch" aria-label="Account type">
        <a href="<?= e(url('register_seeker.php')) ?>" class="is-active" aria-current="page">Job seeker</a>
        <a href="<?= e(url('register_employer.php')) ?>">Employer</a>
      </nav>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= old('full_name') ?>" autocomplete="name" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" autocomplete="email" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="education_level">Education level</label>
            <select class="form-select" id="education_level" name="education_level" required>
              <option value="">Choose one</option>
              <?php foreach (EDUCATION_LEVELS as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= ($_POST['education_level'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="institution">Institution</label>
            <input class="form-control" id="institution" name="institution" value="<?= old('institution') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password">Password</label>
            <div class="pw-field">
              <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" required>
              <button type="button" class="pw-toggle" data-toggle-password="password" aria-pressed="false">Show</button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <div class="pw-field">
              <input class="form-control" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
              <button type="button" class="pw-toggle" data-toggle-password="password_confirm" aria-pressed="false">Show</button>
            </div>
          </div>
          <div class="col-12"><div class="form-text mt-0">At least 8 characters, with letters and numbers.</div></div>
          <div class="col-12">
            <button class="btn btn-brand w-100" type="submit">Create account</button>
          </div>
        </div>
      </form>

      <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
<?php require __DIR__ . '/includes/auth_footer.php'; ?>
