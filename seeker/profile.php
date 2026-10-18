<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/cv.php';
require_once __DIR__ . '/../includes/education_suggestions.php';

/**
 * Print ONE education card (same idea as language_card). The JS template uses "__INDEX__".
 */
function education_card(string $idx, array $row): void
{
    ?>
    <div class="education-card" data-education-card>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h6 mb-0" data-education-title>Education</h3>
        <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0" data-education-delete>Delete</button>
      </div>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="edu_<?= e($idx) ?>_institution">School or University <span class="text-danger">*</span></label>
          <input class="form-control" id="edu_<?= e($idx) ?>_institution" name="educations[<?= e($idx) ?>][institution]"
                 list="school-suggestions" maxlength="200" autocomplete="off" placeholder="Start typing to see suggestions"
                 value="<?= e($row['institution'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="edu_<?= e($idx) ?>_degree">Degree <span class="text-danger">*</span></label>
          <select class="form-select" id="edu_<?= e($idx) ?>_degree" name="educations[<?= e($idx) ?>][degree]">
            <option value="">Select one</option>
            <?php foreach (EDUCATION_LEVELS as $val => $label): ?>
              <option value="<?= e($val) ?>" <?= ($row['degree'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="edu_<?= e($idx) ?>_field">Field of Study</label>
          <input class="form-control" id="edu_<?= e($idx) ?>_field" name="educations[<?= e($idx) ?>][field_of_study]"
                 list="field-suggestions" maxlength="150" autocomplete="off" placeholder="e.g. Informatics"
                 value="<?= e($row['field_of_study'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="edu_<?= e($idx) ?>_gpa">Overall Result (GPA)</label>
          <input class="form-control" id="edu_<?= e($idx) ?>_gpa" name="educations[<?= e($idx) ?>][gpa]"
                 inputmode="decimal" maxlength="4" autocomplete="off" placeholder="e.g. 3.75"
                 value="<?= e($row['gpa'] ?? '') ?>">
          <div class="form-text">Scale 0.00 - 4.00</div>
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label" for="edu_<?= e($idx) ?>_start">From</label>
          <input class="form-control" id="edu_<?= e($idx) ?>_start" name="educations[<?= e($idx) ?>][start_year]"
                 inputmode="numeric" maxlength="4" autocomplete="off" placeholder="YYYY"
                 value="<?= e($row['start_year'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label" for="edu_<?= e($idx) ?>_end">To (Actual or Expected)</label>
          <input class="form-control" id="edu_<?= e($idx) ?>_end" name="educations[<?= e($idx) ?>][end_year]"
                 inputmode="numeric" maxlength="4" autocomplete="off" placeholder="YYYY"
                 value="<?= e($row['end_year'] ?? '') ?>">
        </div>
      </div>
    </div>
    <?php
}

/**
 * Print ONE language card. $idx is part of the field names (languages[$idx][...]),
 * so every card sends its own group of values. The JS template uses "__INDEX__".
 */
function language_card(string $idx, array $row, array $allLanguages): void
{
    $aspects = ['overall' => 'Overall', 'speaking' => 'Speaking', 'reading' => 'Reading', 'writing' => 'Writing'];
    ?>
    <div class="language-card" data-language-card>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h6 mb-0" data-language-title>Language</h3>
        <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0" data-language-delete>Delete</button>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="lang_<?= e($idx) ?>_language">Language <span class="text-danger">*</span></label>
          <select class="form-select" id="lang_<?= e($idx) ?>_language" name="languages[<?= e($idx) ?>][language_id]" data-language-select>
            <option value="">Select one</option>
            <?php foreach ($allLanguages as $lang): ?>
              <option value="<?= (int)$lang['id'] ?>" <?= (string)($row['language_id'] ?? '') === (string)$lang['id'] ? 'selected' : '' ?>><?= e($lang['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <!-- No "name" attribute: this checkbox is a shortcut only and is never sent to the server. -->
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="lang_<?= e($idx) ?>_fluent" data-fluent-shortcut>
            <label class="form-check-label" for="lang_<?= e($idx) ?>_fluent">I am fluent in this language</label>
          </div>
        </div>
        <?php foreach ($aspects as $key => $label): ?>
          <div class="col-md-6">
            <label class="form-label" for="lang_<?= e($idx) ?>_<?= $key ?>"><?= $label ?><?= $key === 'overall' ? ' <span class="text-danger">*</span>' : '' ?></label>
            <select class="form-select" id="lang_<?= e($idx) ?>_<?= $key ?>" name="languages[<?= e($idx) ?>][<?= $key ?>]" data-language-level>
              <option value=""><?= $key === 'overall' ? 'Select one' : 'Not selected' ?></option>
              <?php foreach (LANGUAGE_LEVELS as $val => $levelLabel): ?>
                <option value="<?= e($val) ?>" <?= (string)($row[$key] ?? '') === $val ? 'selected' : '' ?>><?= e($levelLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

$user   = require_role('job_seeker');
$userId = $user['id'];            // identity ALWAYS comes from the session, never from the request

$profile = get_job_seeker_profile($userId);
if ($profile === null) {
    http_response_code(500);
    exit('Profile not found.');
}

$allSkills      = get_all_skills();
$validSkillIds  = array_map(fn($s) => (int)$s['id'], $allSkills);
$form           = $profile;                                  // values shown in the form
$selectedSkills = get_job_seeker_skill_ids($userId);

$allLanguages     = get_all_languages();
$validLanguageIds = array_map(fn($l) => (int)$l['id'], $allLanguages);
// Saved languages as display rows (all values as strings, same shape as the posted rows).
$languageCards = array_map(static fn($r) => [
    'language_id' => (string)$r['language_id'], 'overall' => (string)$r['overall'],
    'speaking' => (string)($r['speaking'] ?? ''), 'reading' => (string)($r['reading'] ?? ''), 'writing' => (string)($r['writing'] ?? ''),
], get_job_seeker_languages($userId));

// Saved education entries as display rows (all values as strings, same shape as the posted rows).
$educationCards = array_map(static fn($r) => [
    'institution' => (string)$r['institution'], 'degree' => (string)$r['degree'],
    'field_of_study' => (string)($r['field_of_study'] ?? ''), 'gpa' => (string)($r['gpa'] ?? ''),
    'start_year' => (string)($r['start_year'] ?? ''), 'end_year' => (string)($r['end_year'] ?? ''),
], get_job_seeker_educations($userId));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    } else {
        [$data, $errors] = validate_profile_input($_POST, $validSkillIds, $validLanguageIds);
        $form           = $data + ['email' => $profile['email']];   // keep what the user typed
        $selectedSkills = $data['skills'];
        $languageCards = $data['language_rows'];                    // keep the typed cards
        $educationCards = $data['education_rows'];                  // keep the typed cards

        if (!$errors) {
            try {
                save_job_seeker_profile($userId, $data);
                $_SESSION['user']['name'] = $data['full_name'];     // keep the navbar name in sync
                flash_set('success', 'Your profile has been saved.');
                redirect('seeker/profile.php');                     // Post/Redirect/Get: refresh will not resubmit
            } catch (PDOException $ex) {
                error_log('Profile save failed: ' . $ex->getMessage());
                $errors[] = 'Your profile could not be saved due to a server error. Please try again.';
            }
        }
    }
}

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <h1 class="h3 mb-1">My profile</h1>
    <p class="text-muted mb-4">Employers see this information when you apply. A complete profile makes a stronger impression.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <?php $hasCv = !empty($profile['cv_path']); ?>
    <section class="app-card">
      <h2 class="app-card__title">CV / Resume</h2>
      <?php if ($hasCv): ?>
        <p class="mb-3"><span class="badge text-bg-success me-2">Uploaded</span>Only you can open your CV. When applications are available, employers will see it only for vacancies you apply to.</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('seeker/cv_view.php')) ?>" target="_blank" rel="noopener">View CV</a>
          <form method="post" action="<?= e(url('seeker/cv_delete.php')) ?>" class="m-0"
                onsubmit="return confirm('Delete your CV? This cannot be undone.');">
            <?= csrf_field() ?>
            <button class="btn btn-outline-danger btn-sm" type="submit">Delete CV</button>
          </form>
        </div>
      <?php else: ?>
        <p class="text-muted mb-3">No CV uploaded yet. Include your internships, organisations, and projects in it.</p>
      <?php endif; ?>

      <form method="post" action="<?= e(url('seeker/cv_upload.php')) ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-8">
          <label class="form-label" for="cv"><?= $hasCv ? 'Replace your CV' : 'Upload your CV' ?> (PDF, max 2 MB)</label>
          <input class="form-control" type="file" id="cv" name="cv" accept="application/pdf,.pdf" required>
        </div>
        <div class="col-md-4">
          <button class="btn btn-brand w-100" type="submit"><?= $hasCv ? 'Replace CV' : 'Upload CV' ?></button>
        </div>
      </form>
    </section>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <section class="app-card">
        <h2 class="app-card__title">Personal information</h2>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" autocomplete="name" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" value="<?= e($profile['email']) ?>" disabled>
            <div class="form-text">Your email is your login and cannot be changed here.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="phone">Phone number</label>
            <input class="form-control" id="phone" name="phone" value="<?= e($form['phone'] ?? '') ?>" autocomplete="tel" placeholder="+62 812 3456 7890">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="city">City</label>
            <input class="form-control" id="city" name="city" value="<?= e($form['city'] ?? '') ?>" autocomplete="address-level2" placeholder="e.g. Bandung">
          </div>
        </div>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">Education</h2>
        <p class="app-card__hint">Add your education, up to <?= (int)MAX_EDUCATIONS_PER_PROFILE ?> entries. Type the name of your school or university; if it is not suggested, just type it in full.</p>

        <div id="education-list" data-max="<?= (int)MAX_EDUCATIONS_PER_PROFILE ?>">
          <?php
          $eduCards = $educationCards ?: [['institution' => '', 'degree' => '', 'field_of_study' => '', 'gpa' => '', 'start_year' => '', 'end_year' => '']];
          foreach ($eduCards as $i => $card) {
              education_card((string)$i, $card);
          }
          ?>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm" id="education-add">+ Add another education</button>

        <!-- Suggestions only: any text may be typed -->
        <datalist id="school-suggestions">
          <?php foreach (SCHOOL_SUGGESTIONS as $name): ?><option value="<?= e($name) ?>"></option><?php endforeach; ?>
        </datalist>
        <datalist id="field-suggestions">
          <?php foreach (FIELD_SUGGESTIONS as $name): ?><option value="<?= e($name) ?>"></option><?php endforeach; ?>
        </datalist>

        <template id="education-template">
          <?php education_card('__INDEX__', []); ?>
        </template>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">About you</h2>
        <div class="mb-3">
          <label class="form-label" for="bio">Short bio</label>
          <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="1000"
                    placeholder="Introduce yourself in a few sentences."><?= e($form['bio'] ?? '') ?></textarea>
          <div class="form-text">Up to 1000 characters. Mention internships, organisations, and projects here or in your CV.</div>
        </div>
        <div>
          <label class="form-label" for="interests">Areas of interest</label>
          <input class="form-control" id="interests" name="interests" maxlength="500"
                 value="<?= e($form['interests'] ?? '') ?>" placeholder="e.g. web development, data analysis, UI design">
        </div>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">Languages</h2>
        <p class="app-card__hint">Add the languages you speak (up to <?= (int)MAX_LANGUAGES_PER_PROFILE ?>). The overall level is required; speaking, reading and writing are optional.</p>

        <div id="language-list" data-max="<?= (int)MAX_LANGUAGES_PER_PROFILE ?>">
          <?php
          $cards = $languageCards ?: [['language_id' => '', 'overall' => '', 'speaking' => '', 'reading' => '', 'writing' => '']];
          foreach ($cards as $i => $card) {
              language_card((string)$i, $card, $allLanguages);
          }
          ?>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm" id="language-add">+ Add another language</button>

        <!-- Blueprint for the "Add another" button (cloned by JavaScript, "__INDEX__" is replaced) -->
        <template id="language-template">
          <?php language_card('__INDEX__', [], $allLanguages); ?>
        </template>
      </section>

      <section class="app-card">
        <h2 class="app-card__title">Skills</h2>
        <p class="app-card__hint">Choose up to <?= (int)MAX_SKILLS_PER_PROFILE ?> skills that describe you best.</p>
        <div class="skill-chips">
          <?php foreach ($allSkills as $skill): $sid = (int)$skill['id']; ?>
            <input type="checkbox" class="btn-check" id="skill_<?= $sid ?>" name="skills[]" value="<?= $sid ?>"
                   autocomplete="off" <?= in_array($sid, $selectedSkills, true) ? 'checked' : '' ?>>
            <label for="skill_<?= $sid ?>"><?= e($skill['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </section>

      <div class="d-flex gap-2 mb-5">
        <button class="btn btn-brand" type="submit">Save changes</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('seeker/dashboard.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script src="<?= e(url('assets/js/profile-languages.js')) ?>"></script>
<script src="<?= e(url('assets/js/profile-education.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
