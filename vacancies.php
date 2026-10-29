<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/vacancy_ui.php';
require_once __DIR__ . '/includes/pagination.php';

refresh_public_session();                       // a suspended account must not look "logged in" here

// The criteria come from the URL, so they are checked: anything unknown becomes "no filter".
$filters = read_public_filters($_GET);
$active  = array_filter($filters, fn($v) => $v !== '');     // only the criteria that are really used

$total = count_public_vacancies($filters);
$pg    = paginate($total, PUBLIC_PAGE_SIZE, $_GET['page'] ?? null);   // invalid / too large page numbers are corrected
$rows  = $total > 0 ? search_public_vacancies($filters, PUBLIC_PAGE_SIZE, $pg['offset']) : [];

$pageTitle = 'Vacancies';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-3">
  <h1 class="h3 mb-1">Find a vacancy</h1>
  <p class="text-muted mb-0">Internships and entry-level jobs that are open for applications.</p>
</div>

<section class="app-card">
  <?= vacancy_search_form_html($filters, (bool)$active) ?>
</section>

<?php if (!$rows): ?>
  <section class="app-card text-center py-5">
    <p class="mb-2"><?= $active ? 'No vacancies match your search.' : 'There are no open vacancies right now.' ?></p>
    <p class="text-muted small mb-0"><?= $active ? 'Try a different keyword, another position type or a broader location.' : 'Please check again later.' ?></p>
  </section>
<?php else: ?>
  <p class="text-muted small mb-2">Showing <?= count($rows) ?> of <?= (int)$total ?> vacancies &middot; page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></p>
  <?php foreach ($rows as $v): ?>
    <?= vacancy_card_html($v) ?>
  <?php endforeach; ?>
  <div class="mt-3"><?= pagination_html($pg, 'vacancies.php', $active) ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
