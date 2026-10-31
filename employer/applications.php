<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user = require_role('employer');

// Filters come from the URL, so they are checked. The vacancy must be one of THIS employer's vacancies.
$myVacancies = get_employer_vacancies($user['id']);
$vacancyId = read_id($_GET['vacancy'] ?? null);
if (!in_array($vacancyId, array_map(fn($v) => (int)$v['id'], $myVacancies), true)) {
    $vacancyId = 0;
}
$statusKeys = array_keys(APPLICATION_STATUSES);
$status = (isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], $statusKeys, true)) ? $_GET['status'] : '';
$filters = ['status' => $status, 'vacancy' => $vacancyId];

$counts = get_employer_application_counts($user['id'], $vacancyId ?: null);
$total  = $status === '' ? $counts['all'] : $counts[$status];
$pg     = paginate($total, APPLICATION_PAGE_SIZE, $_GET['page'] ?? null);
[$rows] = $total > 0 ? search_employer_applications($user['id'], $filters, APPLICATION_PAGE_SIZE, $pg['offset']) : [[], 0];
$keep   = array_filter(['status' => $status, 'vacancy' => $vacancyId ?: ''], fn($v) => $v !== '');

$pageTitle = 'Applications';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3">
  <h1 class="h3 mb-1">Applications received</h1>
  <p class="text-muted mb-0">Review the people who applied for your vacancies.</p>
</div>

<ul class="nav nav-pills mb-3 flex-wrap">
  <?php foreach (array_merge(['' => ['label' => 'All']], APPLICATION_STATUSES) as $key => $info): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === (string)$key ? 'active' : '' ?>"
         href="<?= e(url('employer/applications.php' . ($q = http_build_query(array_filter(['status' => $key, 'vacancy' => $vacancyId ?: ''], fn($v) => $v !== ''))) ? '?' . $q : '')) ?>">
        <?= e($info['label']) ?> <span class="badge text-bg-light"><?= (int)($key === '' ? $counts['all'] : $counts[$key]) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<form method="get" class="row g-2 mb-3">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="col-sm-8 col-md-6">
    <label class="visually-hidden" for="vacancy">Vacancy</label>
    <select class="form-select" id="vacancy" name="vacancy">
      <option value="">All my vacancies</option>
      <?php foreach ($myVacancies as $v): ?>
        <option value="<?= (int)$v['id'] ?>" <?= $vacancyId === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-brand" type="submit">Filter</button></div>
</form>

<?php if (!$rows): ?>
  <section class="app-card text-center py-5"><p class="mb-0 text-muted"><?= $keep ? 'No applications match.' : 'No one has applied yet.' ?></p></section>
<?php else: ?>
  <section class="app-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Applicant</th><th>Vacancy</th><th>Applied on</th><th>Status</th><th class="text-end"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
          <tr>
            <td><div class="fw-semibold"><?= e($a['full_name']) ?></div><div class="small text-muted"><?= e($a['email']) ?></div></td>
            <td><?= e($a['title']) ?></td>
            <td class="text-nowrap"><?= e(substr($a['applied_at'], 0, 10)) ?></td>
            <td><?= application_status_badge($a['status']) ?></td>
            <td class="text-end"><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('employer/application_view.php?id=' . (int)$a['id'])) ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <p class="text-muted small mt-2 mb-3">Showing <?= count($rows) ?> of <?= (int)$total ?> applications &middot; page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></p>
  <?= pagination_html($pg, 'employer/applications.php', $keep) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
