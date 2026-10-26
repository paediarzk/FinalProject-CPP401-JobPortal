<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';      // get_all_skills()
require_once __DIR__ . '/../includes/vacancy.php';

$user       = require_role('employer');
$employerId = $user['id'];                                // identity ALWAYS comes from the session

// ?id=12 means "edit vacancy 12"; no id means "create a new vacancy".
$vacancyId = null;
$existing  = null;
if (isset($_GET['id'])) {
    $rawId = $_GET['id'];
    if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1) {
        http_response_code(404);
        exit('Vacancy not found.');
    }
    $vacancyId = (int)$rawId;
    // Returns null for a missing vacancy AND for someone else's vacancy: both look the same (404).
    $existing = get_vacancy_for_employer($vacancyId, $employerId);
    if ($existing === null) {
        http_response_code(404);
        exit('Vacancy not found.');
    }
    if ($existing['status'] === 'closed') {
        flash_set('warning', 'Closed vacancies cannot be edited.');
        redirect('employer/vacancies.php');
    }
}
$isEdit = $existing !== null;

$allSkills     = get_all_skills();
$validSkillIds = array_map(fn($s) => (int)$s['id'], $allSkills);

// Values shown in the form.
if ($isEdit) {
    $allowanceText = $existing['allowance'] === null ? '' : (string)(int)$existing['allowance'];
    $form = [
        'title' => $existing['title'], 'position_type' => $existing['position_type'], 'location' => $existing['location'],
        'allowance_raw' => $allowanceText, 'deadline' => $existing['deadline'], 'description' => $existing['description'],
    ];
    $selectedSkills = $existing['skill_ids'];
} else {
    $form = ['title' => '', 'position_type' => '', 'location' => '', 'allowance_raw' => '', 'deadline' => '', 'description' => ''];
    $selectedSkills = [];
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    } else {
        [$data, $errors] = validate_vacancy_input($_POST, $validSkillIds);
        $form           = $data;                          // keep what the user typed
        $selectedSkills = $data['skills'];

        if (!$errors) {
            try {
                [$savedId, $problem, $newStatus] = save_vacancy($employerId, $vacancyId, $data);
                if ($problem === 'closed') {
                    flash_set('warning', 'Closed vacancies cannot be edited.');
                    redirect('employer/vacancies.php');
                }
                if ($problem === 'not_found') {
                    http_response_code(404);
                    exit('Vacancy not found.');
                }
                if (!$isEdit) {
                    flash_set('success', 'Your vacancy has been submitted for approval. Job seekers will see it after an administrator approves it.');
                } elseif ($existing['status'] !== 'pending') {
                    flash_set('success', 'Your changes have been saved. Because the vacancy was already reviewed, it is pending approval again.');
                } else {
                    flash_set('success', 'Your changes have been saved.');
                }
                redirect('employer/vacancies.php');       // Post/Redirect/Get
            } catch (PDOException $ex) {
                error_log('Vacancy save failed: ' . $ex->getMessage());
                $errors[] = 'The vacancy could not be saved due to a server error. Please try again.';
            }
        }
    }
}

$today = vacancy_today();
$pageTitle = $isEdit ? 'Edit Vacancy' : 'Post a Vacancy';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Edit vacancy' : 'Post a new vacancy' ?></h1>
    <?php if (!$isEdit): ?>
      <p class="text-muted mb-4">New vacancies are checked by an administrator before job seekers can see them.</p>
    <?php elseif ($existing['status'] === 'published'): ?>
      <div class="alert alert-warning">This vacancy is published. Saving your changes sends it back for approval, and it will be hidden from job seekers until it is approved again.</div>
    <?php elseif ($existing['status'] === 'rejected'): ?>
      <div class="alert alert-warning">This vacancy was rejected. Saving your changes submits it for approval again.
        <?php if (!empty($existing['moderation_note'])): ?><div class="mt-1"><strong>Reason given by the administrator:</strong> <?= e($existing['moderation_note']) ?></div><?php endif; ?></div>
    <?php else: ?>
      <p class="text-muted mb-4">This vacancy is waiting for approval.</p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <section class="app-card">
        <h2 class="app-card__title">Position</h2>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="title">Position title <span class="text-danger">*</span></label>
            <input class="form-control" id="title" name="title" maxlength="200" value="<?= e($form['title']) ?>" placeholder="e.g. Frontend Developer Intern">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="position_type">Position type <span class="text-danger">*</span></label>
            <select class="form-select" id="position_type" name="position_type">
              <option value="">Select one</option>
              <?php foreach (POSITION_TYPES as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= $form['position_type'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="location">Location <span class="text-danger">*</span></label>
            <input class="form-control" id="location" name="location" maxlength="150" value="<?= e($form['location']) ?>" placeholder="e.g. Jakarta, or Remote">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="allowance">Allowance / salary per month (IDR)</label>
            <input class="form-control" id="allowance" name="allowance" inputmode="numeric" maxlength="16" autocomplete="off"
                   value="<?= e($form['allowance_raw']) ?>" placeholder="e.g. 2500000">
            <div class="form-text">Optional. Leave empty if not stated; enter 0 for an unpaid position. Separators such as 2.500.000 are accepted.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="deadline">Application deadline <span class="text-danger">*</span></label>
            <input class="form-control" type="date" id="deadline" name="deadline" value="<?= e($form['deadline']) ?>"
                   min="<?= e($today->format('Y-m-d')) ?>" max="<?= e($today->modify('+' . VACANCY_MAX_DEADLINE_DAYS . ' days')->format('Y-m-d')) ?>">
            <div class="form-text">From today up to one year ahead.</div>
          </div>
        </div>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">Job description</h2>
        <label class="form-label visually-hidden" for="description">Job description</label>
        <textarea class="form-control" id="description" name="description" rows="9" maxlength="5000"
                  placeholder="Describe the tasks, requirements and what the intern or employee will learn."><?= e($form['description']) ?></textarea>
        <div class="form-text">20 to 5000 characters.</div>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">Required skills</h2>
        <p class="app-card__hint">Optional. Choose up to <?= (int)VACANCY_MAX_SKILLS ?> skills that applicants should have.</p>
        <div class="skill-chips">
          <?php foreach ($allSkills as $skill): $sid = (int)$skill['id']; ?>
            <input type="checkbox" class="btn-check" id="skill_<?= $sid ?>" name="skills[]" value="<?= $sid ?>"
                   autocomplete="off" <?= in_array($sid, $selectedSkills, true) ? 'checked' : '' ?>>
            <label for="skill_<?= $sid ?>"><?= e($skill['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </section>

      <div class="d-flex gap-2 mb-5">
        <button class="btn btn-brand" type="submit"><?= $isEdit ? 'Save changes' : 'Submit for approval' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('employer/vacancies.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
