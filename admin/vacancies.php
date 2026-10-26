<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user = require_role('admin');

// Filters come from the URL, so they are checked: anything unknown falls back to the default.
$statusKeys = array_merge(array_keys(VACANCY_STATUSES), ['all']);
$status = (isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], $statusKeys, true)) ? $_GET['status'] : 'pending';
$q      = (isset($_GET['q']) && is_string($_GET['q'])) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$filters = ['status' => $status, 'q' => $q];

$counts = get_vacancy_status_counts();
[$rows, $total] = search_moderation_vacancies($filters, ADMIN_PAGE_SIZE, 0);
$pg = paginate($total, ADMIN_PAGE_SIZE, $_GET['page'] ?? null);
if ($pg['offset'] > 0) {
    [$rows] = search_moderation_vacancies($filters, ADMIN_PAGE_SIZE, $pg['offset']);
}
$keep = array_filter(['status' => $status, 'q' => $q], fn($v) => $v !== '');

$pageTitle = 'Moderate Vacancies';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3">
  <h1 class="h3 mb-1">Moderate vacancies</h1>
  <p class="text-muted mb-0">Approve or reject what employers post. Only approved vacancies are shown to job seekers.</p>
</div>

<ul class="nav nav-pills mb-3 flex-wrap">
  <?php foreach (array_merge(VACANCY_STATUSES, ['all' => ['label' => 'All']]) as $key => $info): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $key ? 'active' : '' ?>"
         href="<?= e(url('admin/vacancies.php?' . http_build_query(array_filter(['status' => $key, 'q' => $q], fn($v) => $v !== '')))) ?>">
        <?= e($key === 'pending' ? 'Pending' : $info['label']) ?> <span class="badge text-bg-light"><?= (int)$counts[$key] ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<form method="get" class="row g-2 mb-3">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="col-sm-8 col-md-6">
    <label class="visually-hidden" for="q">Search</label>
    <input class="form-control" id="q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Search by position title or company name">
  </div>
  <div class="col-auto"><button class="btn btn-brand" type="submit">Search</button></div>
  <?php if ($q !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="<?= e(url('admin/vacancies.php?status=' . $status)) ?>">Clear</a></div><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <section class="app-card text-center py-5"><p class="mb-0 text-muted">No vacancies match.</p></section>
<?php else: ?>
  <section class="app-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Position</th><th>Type</th><th>Location</th><th>Deadline</th><th>Status</th><th>Posted</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $v): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($v['title']) ?></div>
              <div class="small text-muted"><?= e($v['company_name']) ?><?php if ($v['employer_status'] !== 'active'): ?> <span class="badge text-bg-danger">Employer suspended</span><?php endif; ?></div>
            </td>
            <td><?= e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) ?></td>
            <td><?= e($v['location']) ?></td>
            <td class="text-nowrap"><?= e($v['deadline']) ?><?php if (vacancy_is_expired($v)): ?><div><span class="badge text-bg-light border">Deadline passed</span></div><?php endif; ?></td>
            <td><?= vacancy_status_badge($v['status']) ?></td>
            <td class="text-nowrap"><?= e(substr($v['created_at'], 0, 10)) ?></td>
            <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/vacancy_view.php?id=' . (int)$v['id'])) ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <p class="text-muted small mt-2 mb-3">Showing <?= count($rows) ?> of <?= (int)$total ?> vacancies &middot; page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></p>
  <?= pagination_html($pg, 'admin/vacancies.php', $keep) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
