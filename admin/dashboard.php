<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user  = require_role('admin');
$stats = get_admin_stats();
[$waiting] = search_moderation_vacancies(['status' => 'pending'], 5, 0);     // oldest first

$pageTitle = 'Administrator Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">Welcome, <?= e($user['name']) ?></h1>
    <p class="text-muted mb-0">Overview of the platform.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-brand" href="<?= e(url('admin/vacancies.php')) ?>">Moderate vacancies</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/users.php')) ?>">Manage accounts</a>
  </div>
</div>

<h2 class="h6 text-uppercase text-muted mb-2">Accounts</h2>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><section class="app-card mb-0 h-100">
    <div class="display-6 fw-bold"><?= (int)($stats['job_seeker']['active'] + $stats['job_seeker']['suspended']) ?></div>
    <div>Job seekers</div><div class="small text-muted"><?= (int)$stats['job_seeker']['suspended'] ?> suspended</div>
  </section></div>
  <div class="col-6 col-md-3"><section class="app-card mb-0 h-100">
    <div class="display-6 fw-bold"><?= (int)($stats['employer']['active'] + $stats['employer']['suspended']) ?></div>
    <div>Employers</div><div class="small text-muted"><?= (int)$stats['employer']['suspended'] ?> suspended</div>
  </section></div>
  <div class="col-6 col-md-3"><section class="app-card mb-0 h-100">
    <div class="display-6 fw-bold"><?= array_sum($stats['vacancies']) ?></div>
    <div>Vacancies</div><div class="small text-muted">all statuses</div>
  </section></div>
  <div class="col-6 col-md-3"><section class="app-card mb-0 h-100">
    <div class="display-6 fw-bold"><?= (int)$stats['applications'] ?></div>
    <div>Applications</div><div class="small text-muted">submitted so far</div>
  </section></div>
</div>

<h2 class="h6 text-uppercase text-muted mb-2">Vacancies by status</h2>
<div class="row g-3 mb-4">
  <?php foreach (VACANCY_STATUSES as $status => $info): ?>
    <div class="col-6 col-md-3">
      <a class="text-decoration-none text-reset" href="<?= e(url('admin/vacancies.php?status=' . $status)) ?>">
        <section class="app-card mb-0 h-100">
          <div class="display-6 fw-bold"><?= (int)$stats['vacancies'][$status] ?></div>
          <div><?= vacancy_status_badge($status) ?></div>
        </section>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<section class="app-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="app-card__title mb-0">Waiting for your review</h2>
    <a class="small" href="<?= e(url('admin/vacancies.php?status=pending')) ?>">View all pending (<?= (int)$stats['vacancies']['pending'] ?>)</a>
  </div>
  <?php if (!$waiting): ?>
    <p class="text-muted mb-0">Nothing is waiting. All vacancies have been reviewed.</p>
  <?php else: ?>
    <ul class="list-group list-group-flush">
      <?php foreach ($waiting as $v): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
          <span><span class="fw-semibold"><?= e($v['title']) ?></span>
                <span class="text-muted small"> &middot; <?= e($v['company_name']) ?> &middot; posted <?= e(substr($v['created_at'], 0, 10)) ?></span></span>
          <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/vacancy_view.php?id=' . (int)$v['id'])) ?>">Review</a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
