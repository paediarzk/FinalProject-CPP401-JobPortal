<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';

$user     = require_role('job_seeker');
$profile  = get_job_seeker_profile($user['id']);
$skillIds = get_job_seeker_skill_ids($user['id']);
$progress = profile_completeness($profile ?? [], count($skillIds), count(get_job_seeker_languages($user['id'])));

$pageTitle = 'Job Seeker Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Welcome, <?= e($user['name']) ?></h1>
<p class="text-muted mb-4">Your job seeker dashboard.</p>

<section class="app-card" style="max-width: 40rem;">
  <h2 class="app-card__title">Your profile</h2>
  <div class="d-flex justify-content-between small mb-1">
    <span><?= (int)$progress['done'] ?> of <?= (int)$progress['total'] ?> optional details completed</span>
    <strong><?= (int)$progress['percent'] ?>%</strong>
  </div>
  <div class="progress mb-3" role="progressbar" aria-label="Profile completeness"
       aria-valuenow="<?= (int)$progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar" style="width: <?= (int)$progress['percent'] ?>%"></div>
  </div>

  <?php if ($progress['missing']): ?>
    <p class="mb-2 small text-muted">Still missing:</p>
    <p class="mb-3"><?= e(implode(', ', $progress['missing'])) ?></p>
    <a class="btn btn-brand btn-sm" href="<?= e(url('seeker/profile.php')) ?>">Complete your profile</a>
  <?php else: ?>
    <p class="mb-3">Your profile is complete. Well done!</p>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('seeker/profile.php')) ?>">Edit profile</a>
  <?php endif; ?>
</section>

<p class="text-muted small">Vacancy search and applications will be available in Milestone 3.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
