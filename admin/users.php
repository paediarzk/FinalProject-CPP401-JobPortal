<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user = require_role('admin');

$role   = (isset($_GET['role'])   && is_string($_GET['role'])   && in_array($_GET['role'],   ['job_seeker', 'employer', 'admin'], true)) ? $_GET['role'] : '';
$status = (isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], ['active', 'suspended'], true)) ? $_GET['status'] : '';
$q      = (isset($_GET['q']) && is_string($_GET['q'])) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$filters = ['q' => $q, 'role' => $role, 'status' => $status];

[$rows, $total] = search_users($filters, ADMIN_PAGE_SIZE, 0);
$pg = paginate($total, ADMIN_PAGE_SIZE, $_GET['page'] ?? null);
if ($pg['offset'] > 0) {
    [$rows] = search_users($filters, ADMIN_PAGE_SIZE, $pg['offset']);
}
$keep = array_filter($filters, fn($v) => $v !== '');
$back = http_build_query(array_merge($keep, $pg['page'] > 1 ? ['page' => $pg['page']] : []));
$roleLabels = ['job_seeker' => 'Job seeker', 'employer' => 'Employer', 'admin' => 'Administrator'];

$pageTitle = 'Manage Accounts';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3">
  <h1 class="h3 mb-1">Manage accounts</h1>
  <p class="text-muted mb-0">Search accounts and suspend or re-activate job seekers and employers. A suspended account cannot log in, and an account that is logged in is signed out at its next request.</p>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-5">
    <label class="visually-hidden" for="q">Search</label>
    <input class="form-control" id="q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Search by e-mail, name or company">
  </div>
  <div class="col-6 col-md-2">
    <label class="visually-hidden" for="role">Role</label>
    <select class="form-select" id="role" name="role">
      <option value="">All roles</option>
      <?php foreach ($roleLabels as $val => $label): ?><option value="<?= e($val) ?>" <?= $role === $val ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="visually-hidden" for="status">Status</label>
    <select class="form-select" id="status" name="status">
      <option value="">Any status</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-brand" type="submit">Search</button></div>
  <?php if ($keep): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="<?= e(url('admin/users.php')) ?>">Clear</a></div><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <section class="app-card text-center py-5"><p class="mb-0 text-muted">No accounts match.</p></section>
<?php else: ?>
  <section class="app-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>Name / company</th><th>E-mail</th><th>Role</th><th>Status</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $manageable = $r['role'] !== 'admin' && (int)$r['id'] !== $user['id']; ?>
          <tr>
            <td class="fw-semibold"><?= e($r['display_name']) ?></td>
            <td><?= e($r['email']) ?></td>
            <td><?= e($roleLabels[$r['role']] ?? $r['role']) ?></td>
            <td><span class="badge <?= $r['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td class="text-nowrap"><?= e(substr($r['created_at'], 0, 10)) ?></td>
            <td class="text-end text-nowrap">
              <?php if ($manageable): ?>
                <form method="post" action="<?= e(url('admin/user_action.php')) ?>" class="d-inline"
                      onsubmit="return confirm('<?= $r['status'] === 'active' ? 'Suspend this account? The user will be signed out.' : 'Activate this account again?' ?>');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="action" value="<?= $r['status'] === 'active' ? 'suspend' : 'activate' ?>">
                  <input type="hidden" name="back" value="<?= e($back) ?>">
                  <button class="btn btn-sm <?= $r['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= $r['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                </form>
              <?php else: ?><span class="text-muted small">&mdash;</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <p class="text-muted small mt-2 mb-3">Showing <?= count($rows) ?> of <?= (int)$total ?> accounts &middot; page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></p>
  <?= pagination_html($pg, 'admin/users.php', $keep) ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
