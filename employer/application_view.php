<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/application.php';

$user = require_role('employer');

$id = read_id($_GET['id'] ?? null);
$a  = $id > 0 ? get_employer_application($id, $user['id']) : null;      // only an application of THIS employer's vacancy
if ($a === null) {
    http_response_code(404);
    exit('Application not found.');
}
$next = APPLICATION_TRANSITIONS[$a['status']] ?? [];

$pageTitle = 'Review Application';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-9">
    <p class="mb-2"><a href="<?= e(url('employer/applications.php')) ?>">&larr; Back to applications</a></p>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1"><?= e($a['full_name']) ?></h1>
        <div class="text-muted">Applied for <?= e($a['title']) ?> on <?= e(substr($a['applied_at'], 0, 10)) ?></div>
      </div>
      <div><?= application_status_badge($a['status']) ?></div>
    </div>

    <section class="app-card">
      <div class="row g-3">
        <div class="col-sm-6"><div class="small text-muted">E-mail</div><?= e($a['email']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Phone</div><?= $a['phone'] ? e($a['phone']) : '<span class="text-muted">Not stated</span>' ?></div>
        <div class="col-sm-6"><div class="small text-muted">City</div><?= $a['city'] ? e($a['city']) : '<span class="text-muted">Not stated</span>' ?></div>
        <div class="col-sm-6"><div class="small text-muted">CV</div>
          <?php if ($a['has_cv']): ?>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?= e(url('employer/application_cv.php?id=' . (int)$a['id'])) ?>">Open CV (PDF)</a>
          <?php else: ?><span class="text-muted">The applicant has removed the CV.</span><?php endif; ?>
        </div>
      </div>
    </section>

    <?php if (!empty($a['bio'])): ?>
    <section class="app-card"><h2 class="app-card__title">About</h2><div style="white-space: pre-wrap;"><?= e($a['bio']) ?></div></section>
    <?php endif; ?>

    <section class="app-card">
      <h2 class="app-card__title">Education</h2>
      <?php if ($a['educations']): ?>
        <?php foreach ($a['educations'] as $ed): ?>
          <div class="mb-2">
            <div class="fw-semibold"><?= e($ed['institution']) ?></div>
            <div class="small text-muted"><?= e(EDUCATION_LEVELS[$ed['degree']] ?? $ed['degree']) ?>
              <?php if (!empty($ed['field_of_study'])): ?> &middot; <?= e($ed['field_of_study']) ?><?php endif; ?>
              <?php if ($ed['gpa'] !== null): ?> &middot; GPA <?= e(number_format((float)$ed['gpa'], 2)) ?><?php endif; ?>
              <?php if ($ed['start_year'] || $ed['end_year']): ?> &middot; <?= e(trim(($ed['start_year'] ?? '') . ' - ' . ($ed['end_year'] ?? ''), ' -')) ?><?php endif; ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?><span class="text-muted">None.</span><?php endif; ?>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">Skills and languages</h2>
      <div class="mb-2">
        <?php foreach ($a['skills'] as $skill): ?><span class="badge rounded-pill text-bg-light border me-1"><?= e($skill) ?></span><?php endforeach; ?>
        <?php if (!$a['skills']): ?><span class="text-muted">No skills listed.</span><?php endif; ?>
      </div>
      <?php foreach ($a['languages'] as $l): ?>
        <span class="badge rounded-pill text-bg-light border me-1"><?= e($l['name']) ?> &middot; <?= e(LANGUAGE_LEVELS[$l['overall']] ?? $l['overall']) ?></span>
      <?php endforeach; ?>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">Decision</h2>
      <?php if (!$next): ?>
        <p class="text-muted mb-0">This application is <?= e(strtolower(APPLICATION_STATUSES[$a['status']]['label'])) ?>. The decision is final.</p>
      <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($next as $target): ?>
            <form method="post" action="<?= e(url('employer/application_action.php')) ?>"
                  <?php if ($target !== 'under_review'): ?>onsubmit="return confirm('Mark this application as <?= e(strtolower(APPLICATION_STATUSES[$target]['label'])) ?>? This cannot be changed afterwards.');"<?php endif; ?>>
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="status" value="<?= e($target) ?>">
              <button class="btn <?= $target === 'accepted' ? 'btn-brand' : ($target === 'rejected' ? 'btn-outline-danger' : 'btn-outline-secondary') ?>" type="submit">
                <?= e(['under_review' => 'Start reviewing', 'accepted' => 'Accept', 'rejected' => 'Reject'][$target]) ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
