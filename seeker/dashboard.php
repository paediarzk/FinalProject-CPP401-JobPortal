<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/application.php';

$user     = require_role('job_seeker');
$profile  = get_job_seeker_profile($user['id']);
$skillIds = get_job_seeker_skill_ids($user['id']);
$progress = profile_completeness($profile ?? [], count($skillIds), count(get_job_seeker_languages($user['id'])));

$appCounts = get_seeker_application_counts($user['id']);

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

<section class="app-card" style="max-width: 40rem;">
  <h2 class="app-card__title">Vacancies</h2>
  <p class="mb-3">Search internships and entry-level jobs by keyword, position type and location.</p>
  <a class="btn btn-brand btn-sm" href="<?= e(url('vacancies.php')) ?>">Browse vacancies</a>
</section>
<section class="app-card" style="max-width: 40rem;">
  <h2 class="app-card__title">My applications</h2>
  <?php if ($appCounts['all'] === 0): ?>
    <p class="mb-3">You have not applied for any vacancy yet.</p>
  <?php else: ?>
    <p class="mb-3"><?= (int)$appCounts['all'] ?> application<?= $appCounts['all'] === 1 ? '' : 's' ?>:
      <?php foreach (APPLICATION_STATUSES as $key => $info): ?><?= (int)$appCounts[$key] ?> <?= e(strtolower($info['label'])) ?><?= $key === 'rejected' ? '' : ' &middot; ' ?><?php endforeach; ?></p>
  <?php endif; ?>
  <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('seeker/applications.php')) ?>">View my applications</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
