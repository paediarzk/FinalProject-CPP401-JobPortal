<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user   = require_role('job_seeker');
$counts = get_seeker_application_counts($user['id']);
$total  = $counts['all'];
$pg     = paginate($total, APPLICATION_PAGE_SIZE, $_GET['page'] ?? null);
[$rows] = $total > 0 ? search_seeker_applications($user['id'], APPLICATION_PAGE_SIZE, $pg['offset']) : [[], 0];

$pageTitle = 'My Applications';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">My applications</h1>
    <p class="text-muted mb-0">Follow the status of every vacancy you applied for.</p>
  </div>
  <a class="btn btn-brand" href="<?= e(url('vacancies.php')) ?>">Browse vacancies</a>
</div>

<?php if (!$rows): ?>
  <section class="app-card text-center py-5">
    <p class="mb-3">You have not applied for any vacancy yet.</p>
    <a class="btn btn-brand" href="<?= e(url('vacancies.php')) ?>">Find a vacancy</a>
  </section>
<?php else: ?>
  <section class="app-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Position</th><th>Location</th><th>Applied on</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
          <?php $open = $a['vacancy_status'] === 'published' && $a['deadline'] >= vacancy_today()->format('Y-m-d'); ?>
          <tr>
            <td>
              <?php if ($open): ?>
                <a class="fw-semibold text-decoration-none" href="<?= e(url('vacancy.php?id=' . (int)$a['vacancy_id'])) ?>"><?= e($a['title']) ?></a>
              <?php else: ?>
                <div class="fw-semibold"><?= e($a['title']) ?></div>
              <?php endif; ?>
              <div class="small text-muted"><?= e($a['company_name']) ?> &middot; <?= e(POSITION_TYPES[$a['position_type']] ?? $a['position_type']) ?>
                <?php if (!$open): ?><span class="badge text-bg-light border">Vacancy no longer open</span><?php endif; ?></div>
            </td>
            <td><?= e($a['location']) ?></td>
            <td class="text-nowrap"><?= e(substr($a['applied_at'], 0, 10)) ?></td>
            <td><?= application_status_badge($a['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <p class="text-muted small mt-2 mb-3">Showing <?= count($rows) ?> of <?= (int)$total ?> applications &middot; page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></p>
  <?= pagination_html($pg, 'seeker/applications.php', []) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
