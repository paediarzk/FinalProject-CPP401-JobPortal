<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('job_seeker');   // access control: wrong role => 403, not logged in => login page

$pageTitle = 'Job Seeker Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h2>Job Seeker Dashboard</h2>
<p>Welcome, <strong><?= e($user['name']) ?></strong>.</p>
<p class="text-muted">Features for this role will be added in the next milestones.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
