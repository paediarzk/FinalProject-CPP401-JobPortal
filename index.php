<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5">
  <h1 class="display-6 fw-bold">Find your internship or first job here</h1>
  <p class="lead text-muted">A non-government portal for internship and entry-level opportunities.</p>
  <?php if (!current_user()): ?>
    <a class="btn btn-primary me-2" href="<?= e(url('register_seeker.php')) ?>">I'm a student / graduate</a>
    <a class="btn btn-outline-primary" href="<?= e(url('register_employer.php')) ?>">I'm an employer</a>
  <?php endif; ?>
  <p class="text-muted mt-4 small">Vacancy search will be added in Milestone 3.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
