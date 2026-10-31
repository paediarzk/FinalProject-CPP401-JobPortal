<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/vacancy.php';
require_once __DIR__ . '/includes/application.php';

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

// What the visitor sees in the "Apply" card depends on who they are (guest, job seeker, employer, administrator).
$user = current_user();
$application = null;
$hasCv = false;
if ($user !== null && $user['role'] === 'job_seeker') {
    $application = get_seeker_application($user['id'], (int)$v['id']);
    $profile = get_job_seeker_profile($user['id']);
    $hasCv = $profile !== null && cv_file_path($profile['cv_path'] ?? null) !== null;
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
      <h2 class="app-card__title">Apply</h2>
      <?php if ($user === null): ?>
        <p class="mb-3">Log in or create a job seeker account to apply for this vacancy.</p>
        <a class="btn btn-brand btn-sm" href="<?= e(url('login.php')) ?>">Log in</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('register_seeker.php')) ?>">Register</a>
      <?php elseif ($user['role'] !== 'job_seeker'): ?>
        <p class="text-muted mb-0">Only job seekers can apply for vacancies.</p>
      <?php elseif ($application !== null): ?>
        <p class="mb-2">You applied on <?= e(substr($application['applied_at'], 0, 10)) ?>. Status: <?= application_status_badge($application['status']) ?></p>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('seeker/applications.php')) ?>">View my applications</a>
      <?php elseif (!$hasCv): ?>
        <p class="mb-3">Upload your CV (PDF) on your profile first. The employer reviews your CV.</p>
        <a class="btn btn-brand btn-sm" href="<?= e(url('seeker/profile.php')) ?>">Go to my profile</a>
      <?php else: ?>
        <p class="mb-3">The employer will see your profile and your CV.</p>
        <form method="post" action="<?= e(url('seeker/apply.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="vacancy_id" value="<?= (int)$v['id'] ?>">
          <button class="btn btn-brand" type="submit">Apply for this vacancy</button>
        </form>
      <?php endif; ?>
    </section>

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
