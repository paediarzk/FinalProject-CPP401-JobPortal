<?php
declare(strict_types=1);

/**
 * Job seeker profile logic (read, validate, save).
 * Pages (seeker/profile.php) only handle HTML; rules and SQL live here.
 */

const MAX_SKILLS_PER_PROFILE = 20;

/** Load one profile. $userId must come from the SESSION, never from the request. */
function get_job_seeker_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT js.user_id, u.email, js.full_name, js.education_level, js.institution,
                js.phone, js.city, js.bio, js.interests, js.cv_path
         FROM job_seekers js
         JOIN users u ON u.id = js.user_id
         WHERE js.user_id = :id'
    );
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** The predefined skill list, used for both profiles and (later) vacancies. */
function get_all_skills(): array
{
    return db()->query('SELECT id, name FROM skills ORDER BY name')->fetchAll();
}

function get_job_seeker_skill_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT skill_id FROM job_seeker_skills WHERE job_seeker_id = :id');
    $stmt->execute([':id' => $userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Clean and validate the submitted form.
 * Returns [cleanData, errors]. Only whitelisted fields are read from $in, so extra
 * fields an attacker adds to the request (e.g. user_id, role) are simply ignored.
 */
function validate_profile_input(array $in, array $validSkillIds): array
{
    $errors = [];
    $data = [
        'full_name'       => trim((string)($in['full_name'] ?? '')),
        'education_level' => (string)($in['education_level'] ?? ''),
        'institution'     => trim((string)($in['institution'] ?? '')),
        'phone'           => trim((string)($in['phone'] ?? '')),
        'city'            => trim((string)($in['city'] ?? '')),
        'bio'             => trim((string)($in['bio'] ?? '')),
        'interests'       => trim((string)($in['interests'] ?? '')),
        'skills'          => [],
    ];

    if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
        $errors[] = 'Full name must be between 2 and 150 characters.';
    }
    if (!array_key_exists($data['education_level'], EDUCATION_LEVELS)) {
        $errors[] = 'Please choose a valid education level.';
    }
    if (mb_strlen($data['institution']) < 2 || mb_strlen($data['institution']) > 200) {
        $errors[] = 'Institution must be between 2 and 200 characters.';
    }

    if ($data['phone'] !== '') {
        $digits = preg_replace('/\D/', '', $data['phone']);
        if (!preg_match('/^\+?[0-9 ()\-]{8,20}$/', $data['phone'])
            || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors[] = 'Phone number is not valid. Use digits, spaces, "+", "-" or brackets (8-15 digits).';
        }
    }
    if (mb_strlen($data['city']) > 100) {
        $errors[] = 'City must not exceed 100 characters.';
    }
    if (mb_strlen($data['bio']) > 1000) {
        $errors[] = 'Short bio must not exceed 1000 characters.';
    }
    if (mb_strlen($data['interests']) > 500) {
        $errors[] = 'Areas of interest must not exceed 500 characters.';
    }

    // Skills: every submitted ID must exist in the predefined list (server-side check).
    $raw = $in['skills'] ?? [];
    $ids = is_array($raw) ? array_values(array_unique(array_map('intval', $raw))) : [];
    foreach ($ids as $id) {
        if (!in_array($id, $validSkillIds, true)) {
            $errors[] = 'One or more selected skills are invalid.';
            $ids = [];
            break;
        }
    }
    if (count($ids) > MAX_SKILLS_PER_PROFILE) {
        $errors[] = 'You can select at most ' . MAX_SKILLS_PER_PROFILE . ' skills.';
    }
    $data['skills'] = $ids;

    return [$data, $errors];
}

/**
 * Save profile + skills as ONE atomic unit (transaction).
 * Skills are "synchronised": delete the old links, insert the new ones.
 * If anything fails in the middle, rollBack() restores the previous state.
 */
function save_job_seeker_profile(int $userId, array $data): void
{
    $pdo = db();
    $nullIfEmpty = fn(string $v): ?string => $v === '' ? null : $v;

    try {
        $pdo->beginTransaction();

        $pdo->prepare(
            'UPDATE job_seekers
             SET full_name = :full_name, education_level = :education_level, institution = :institution,
                 phone = :phone, city = :city, bio = :bio, interests = :interests
             WHERE user_id = :id'
        )->execute([
            ':full_name'       => $data['full_name'],
            ':education_level' => $data['education_level'],
            ':institution'     => $data['institution'],
            ':phone'           => $nullIfEmpty($data['phone']),
            ':city'            => $nullIfEmpty($data['city']),
            ':bio'             => $nullIfEmpty($data['bio']),
            ':interests'       => $nullIfEmpty($data['interests']),
            ':id'              => $userId,
        ]);

        $pdo->prepare('DELETE FROM job_seeker_skills WHERE job_seeker_id = :id')
            ->execute([':id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO job_seeker_skills (job_seeker_id, skill_id) VALUES (:uid, :sid)'
        );
        foreach ($data['skills'] as $skillId) {
            $insert->execute([':uid' => $userId, ':sid' => $skillId]);
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;   // the page decides what message to show
    }
}

/** How many of the 5 optional profile details are filled, and which are missing. */
function profile_completeness(array $profile, int $skillCount): array
{
    $items = [
        'Phone number'       => !empty($profile['phone']),
        'City'               => !empty($profile['city']),
        'Short bio'          => !empty($profile['bio']),
        'Areas of interest'  => !empty($profile['interests']),
        'At least one skill' => $skillCount > 0,
    ];
    $done = count(array_filter($items));
    return [
        'done'    => $done,
        'total'   => count($items),
        'percent' => (int)round($done / count($items) * 100),
        'missing' => array_keys(array_filter($items, fn($ok) => !$ok)),
    ];
}
