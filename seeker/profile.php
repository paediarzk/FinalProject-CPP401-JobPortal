<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/profile.php';

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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    } else {
        [$data, $errors] = validate_profile_input($_POST, $validSkillIds);
        $form           = $data + ['email' => $profile['email']];   // keep what the user typed
        $selectedSkills = $data['skills'];

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
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label" for="education_level">Education level</label>
            <select class="form-select" id="education_level" name="education_level" required>
              <?php foreach (EDUCATION_LEVELS as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= $form['education_level'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-7">
            <label class="form-label" for="institution">Institution</label>
            <input class="form-control" id="institution" name="institution" value="<?= e($form['institution']) ?>" required>
          </div>
        </div>
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
<?php require __DIR__ . '/../includes/footer.php'; ?>
