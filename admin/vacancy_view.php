<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

$user = require_role('admin');

$rawId = $_GET['id'] ?? '';
if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1) {
    http_response_code(404);
    exit('Vacancy not found.');
}
$v = get_vacancy_for_moderation((int)$rawId);
if ($v === null) {
    http_response_code(404);
    exit('Vacancy not found.');
}

$expired   = vacancy_is_expired($v);
$suspended = $v['employer_status'] !== 'active';
$canApprove = $v['status'] === 'pending' && !$expired && !$suspended;

$pageTitle = 'Review Vacancy';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-9">
    <p class="mb-2"><a href="<?= e(url('admin/vacancies.php?status=' . $v['status'])) ?>">&larr; Back to the list</a></p>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1"><?= e($v['title']) ?></h1>
        <div class="text-muted"><?= e($v['company_name']) ?> &middot; <?= e($v['industry_sector']) ?></div>
      </div>
      <div class="text-end"><?= vacancy_status_badge($v['status']) ?>
        <?php if ($expired): ?><div class="mt-1"><span class="badge text-bg-light border">Deadline passed</span></div><?php endif; ?>
      </div>
    </div>

    <?php if ($suspended): ?><div class="alert alert-danger">The employer account is suspended.</div><?php endif; ?>
    <?php if (!empty($v['moderation_note'])): ?>
      <div class="alert alert-warning"><strong>Reason given:</strong> <?= e($v['moderation_note']) ?></div>
    <?php endif; ?>

    <section class="app-card">
      <div class="row g-3">
        <div class="col-sm-6"><div class="small text-muted">Position type</div><?= e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Location</div><?= e($v['location']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Allowance per month (IDR)</div><?= $v['allowance'] === null ? 'Not stated' : e(number_format((float)$v['allowance'], 0, ',', '.')) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Application deadline</div><?= e($v['deadline']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Employer e-mail</div><?= e($v['employer_email']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Posted</div><?= e(substr($v['created_at'], 0, 16)) ?></div>
      </div>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">Job description</h2>
      <div style="white-space: pre-wrap;"><?= e($v['description']) ?></div>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">Required skills</h2>
      <?php if ($v['skills']): ?>
        <?php foreach ($v['skills'] as $skill): ?><span class="badge rounded-pill text-bg-light border me-1"><?= e($skill) ?></span><?php endforeach; ?>
      <?php else: ?><span class="text-muted">None selected.</span><?php endif; ?>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">Decision</h2>
      <?php if ($v['status'] === 'pending'): ?>
        <?php if (!$canApprove): ?>
          <div class="alert alert-secondary"><?= $suspended ? 'It cannot be approved while the employer is suspended.' : 'The deadline has passed, so it cannot be approved.' ?> You can still reject it with a reason.</div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-3 align-items-start">
          <form method="post" action="<?= e(url('admin/vacancy_action.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="approve">
            <button class="btn btn-brand" type="submit" <?= $canApprove ? '' : 'disabled' ?>>Approve and publish</button>
          </form>
        </div>
        <hr>
        <form method="post" action="<?= e(url('admin/vacancy_action.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="reject">
          <label class="form-label" for="note">Reject: reason for the employer <span class="text-danger">*</span></label>
          <textarea class="form-control mb-2" id="note" name="note" rows="3" maxlength="<?= (int)MODERATION_NOTE_MAX ?>" placeholder="Explain what must be fixed (<?= (int)MODERATION_NOTE_MIN ?>-<?= (int)MODERATION_NOTE_MAX ?> characters)."></textarea>
          <button class="btn btn-outline-danger" type="submit">Reject</button>
        </form>
      <?php elseif ($v['status'] === 'published'): ?>
        <p class="text-muted">This vacancy is visible to job seekers. If it breaks the rules you can take it down; the employer sees your reason.</p>
        <form method="post" action="<?= e(url('admin/vacancy_action.php')) ?>" onsubmit="return confirm('Take this vacancy down? Job seekers will no longer see it.');">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="takedown">
          <label class="form-label" for="note">Take down: reason for the employer <span class="text-danger">*</span></label>
          <textarea class="form-control mb-2" id="note" name="note" rows="3" maxlength="<?= (int)MODERATION_NOTE_MAX ?>"></textarea>
          <button class="btn btn-outline-danger" type="submit">Take down</button>
        </form>
      <?php elseif ($v['status'] === 'rejected'): ?>
        <p class="text-muted mb-0">Rejected. The employer can edit the vacancy and submit it again; it then returns to the pending list.</p>
      <?php else: ?>
        <p class="text-muted mb-0">Closed by the employer. No action is possible.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
