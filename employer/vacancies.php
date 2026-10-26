<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/vacancy.php';

$user      = require_role('employer');
$vacancies = get_employer_vacancies($user['id']);       // only THIS employer's vacancies

$pageTitle = 'My Vacancies';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">My vacancies</h1>
    <p class="text-muted mb-0">New and edited vacancies stay <strong>pending</strong> until an administrator approves them.</p>
  </div>
  <a class="btn btn-brand" href="<?= e(url('employer/vacancy_form.php')) ?>">+ Post a vacancy</a>
</div>

<?php if (!$vacancies): ?>
  <section class="app-card text-center py-5">
    <p class="mb-3">You have not posted any vacancy yet.</p>
    <a class="btn btn-brand" href="<?= e(url('employer/vacancy_form.php')) ?>">Post your first vacancy</a>
  </section>
<?php else: ?>
  <section class="app-card p-0">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr><th>Position</th><th>Type</th><th>Location</th><th>Deadline</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($vacancies as $v): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($v['title']) ?></div>
              <?php if (!empty($v['skill_names'])): ?><div class="small text-muted"><?= e($v['skill_names']) ?></div><?php endif; ?>
            </td>
            <td><?= e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) ?></td>
            <td><?= e($v['location']) ?></td>
            <td class="text-nowrap">
              <?= e($v['deadline']) ?>
              <?php if (vacancy_is_expired($v)): ?><div><span class="badge text-bg-light border">Deadline passed</span></div><?php endif; ?>
            </td>
            <td>
              <?= vacancy_status_badge($v['status']) ?>
              <?php if ($v['status'] === 'rejected' && !empty($v['moderation_note'])): ?>
                <div class="small text-muted mt-1" style="max-width: 18rem;"><strong>Reason:</strong> <?= e($v['moderation_note']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
              <?php if ($v['status'] !== 'closed'): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('employer/vacancy_form.php?id=' . (int)$v['id'])) ?>">Edit</a>
                <form method="post" action="<?= e(url('employer/vacancy_close.php')) ?>" class="d-inline"
                      onsubmit="return confirm('Close this vacancy? It cannot be edited or reopened afterwards.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" type="submit">Close</button>
                </form>
              <?php else: ?>
                <span class="text-muted small">&mdash;</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
