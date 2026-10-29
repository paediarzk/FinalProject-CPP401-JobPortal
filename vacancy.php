<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/vacancy.php';

refresh_public_session();

// The id comes from the URL: it must be a positive whole number.
$rawId = $_GET['id'] ?? '';
if (!is_string($rawId) || !ctype_digit($rawId) || strlen($rawId) > 9 || (int)$rawId < 1) {
    http_response_code(404);
    exit('Vacancy not found.');
}
// Only published vacancies whose deadline has not passed are returned. Pending, rejected, closed and expired ones
// give exactly the same answer as a number that does not exist, so nothing leaks about them.
$v = get_public_vacancy((int)$rawId);
if ($v === null) {
    http_response_code(404);
    exit('Vacancy not found.');
}

$pageTitle = $v['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-9">
    <p class="mb-2"><a href="<?= e(url('vacancies.php')) ?>">&larr; Back to vacancies</a></p>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1"><?= e($v['title']) ?></h1>
        <div class="text-muted"><?= e($v['company_name']) ?> &middot; <?= e($v['industry_sector']) ?></div>
      </div>
      <span class="badge text-bg-light border"><?= e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) ?></span>
    </div>

    <section class="app-card">
      <div class="row g-3">
        <div class="col-sm-6"><div class="small text-muted">Position type</div><?= e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Location</div><?= e($v['location']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Allowance per month (IDR)</div><?= $v['allowance'] === null ? 'Not stated' : e(number_format((float)$v['allowance'], 0, ',', '.')) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Application deadline</div><?= e($v['deadline']) ?></div>
        <div class="col-sm-6"><div class="small text-muted">Posted</div><?= e(substr($v['created_at'], 0, 10)) ?></div>
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
      <?php else: ?><span class="text-muted">None specified.</span><?php endif; ?>
    </section>

    <section class="app-card">
      <h2 class="app-card__title">About <?= e($v['company_name']) ?></h2>
      <div class="small text-muted mb-1"><?= e($v['industry_sector']) ?></div>
      <?php if (!empty($v['company_description'])): ?>
        <div style="white-space: pre-wrap;"><?= e($v['company_description']) ?></div>
      <?php else: ?><span class="text-muted">No company description.</span><?php endif; ?>
    </section>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
