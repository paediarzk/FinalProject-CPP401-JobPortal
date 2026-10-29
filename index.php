<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/vacancy_ui.php';

refresh_public_session();
$latest = search_public_vacancies([], PUBLIC_HOME_LATEST, 0);      // newest open vacancies

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5">
  <h1 class="display-6 fw-bold">Find your internship or first job here</h1>
  <p class="lead text-muted">A non-government portal for internship and entry-level opportunities.</p>
  <?php if (!current_user()): ?>
    <a class="btn btn-brand me-2" href="<?= e(url('register_seeker.php')) ?>">I'm a student / graduate</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('register_employer.php')) ?>">I'm an employer</a>
  <?php endif; ?>
</div>

<section class="app-card">
  <?= vacancy_search_form_html(['q' => '', 'type' => '', 'location' => '']) ?>
</section>

<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
  <h2 class="h5 mb-0">Latest vacancies</h2>
  <a class="small" href="<?= e(url('vacancies.php')) ?>">Browse all vacancies</a>
</div>
<?php if (!$latest): ?>
  <section class="app-card text-center py-5"><p class="mb-0 text-muted">There are no open vacancies right now. Please check again later.</p></section>
<?php else: ?>
  <?php foreach ($latest as $v): ?><?= vacancy_card_html($v) ?><?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
