<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/vacancy.php';

$user   = require_role('employer');
$counts = get_employer_vacancy_counts($user['id']);
$recent = get_employer_vacancies($user['id'], 5);

$pageTitle = 'Employer Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">Welcome, <?= e($user['name']) ?></h1>
    <p class="text-muted mb-0">Your employer dashboard.</p>
  </div>
  <a class="btn btn-brand" href="<?= e(url('employer/vacancy_form.php')) ?>">+ Post a vacancy</a>
</div>

<div class="row g-3 mb-4">
  <?php foreach (VACANCY_STATUSES as $status => $info): ?>
    <div class="col-6 col-md-3">
      <section class="app-card mb-0 h-100">
        <div class="display-6 fw-bold"><?= (int)$counts[$status] ?></div>
        <div><?= vacancy_status_badge($status) ?></div>
      </section>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($counts['pending'] > 0): ?>
  <p class="text-muted small">Pending vacancies are not visible to job seekers until an administrator approves them.</p>
<?php endif; ?>

<section class="app-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="app-card__title mb-0">Latest vacancies</h2>
    <a class="small" href="<?= e(url('employer/vacancies.php')) ?>">View all</a>
  </div>
  <?php if (!$recent): ?>
    <p class="text-muted mb-0">You have not posted any vacancy yet.</p>
  <?php else: ?>
    <ul class="list-group list-group-flush">
      <?php foreach ($recent as $v): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><span class="fw-semibold"><?= e($v['title']) ?></span>
                <span class="text-muted small"> &middot; <?= e($v['location']) ?> &middot; deadline <?= e($v['deadline']) ?></span></span>
          <?= vacancy_status_badge($v['status']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<p class="text-muted small">Applicants will be shown here when applications are available (Milestone 3).</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
