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

$pageTitle = 'Register your company';
$heroTitle = 'Reach students and fresh graduates who are ready to start.';
$heroText  = 'Post internships and entry-level vacancies, then review applicants in one place.';
require __DIR__ . '/includes/auth_header.php';
?>
      <h1 class="auth-title">Register your company</h1>
      <p class="auth-sub">Post vacancies and find new talent on <?= e(APP_NAME) ?>.</p>

      <nav class="role-switch" aria-label="Account type">
        <a href="<?= e(url('register_seeker.php')) ?>">Job seeker</a>
        <a href="<?= e(url('register_employer.php')) ?>" class="is-active" aria-current="page">Employer</a>
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
            <label class="form-label" for="company_name">Company / organisation name</label>
            <input class="form-control" id="company_name" name="company_name" value="<?= old('company_name') ?>" autocomplete="organization" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" autocomplete="email" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="industry_sector">Industry sector</label>
            <input class="form-control" id="industry_sector" name="industry_sector" value="<?= old('industry_sector') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="description">Company description <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= old('description') ?></textarea>
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
