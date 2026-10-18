<?php
declare(strict_types=1);

/**
 * Job seeker profile logic (read, validate, save).
 * Pages (seeker/profile.php) only handle HTML; rules and SQL live here.
 */

const MAX_SKILLS_PER_PROFILE = 20;

const MAX_LANGUAGES_PER_PROFILE = 8;
const MAX_EDUCATIONS_PER_PROFILE = 5;
const EDUCATION_MIN_YEAR = 1950;
const EDUCATION_MAX_YEARS_AHEAD = 8;     // expected graduation may lie in the future

/** The four proficiency aspects stored for every language (overall is required, the rest optional). */
const LANGUAGE_ASPECTS = ['overall', 'speaking', 'reading', 'writing'];

/** Allowed proficiency levels: database value => label shown to the user. */
const LANGUAGE_LEVELS = [
    'basic'          => 'Basic',
    'conversational' => 'Conversational',
    'fluent'         => 'Fluent',
    'native'         => 'Native / bilingual',
];

/** Load one profile. $userId must come from the SESSION, never from the request. */
function get_job_seeker_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT js.user_id, u.email, js.full_name,
                js.phone, js.city, js.bio, js.interests, js.cv_path
         FROM job_seekers js
         JOIN users u ON u.id = js.user_id
         WHERE js.user_id = :id'
    );
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** A job seeker's education entries, most recent first. */
function get_job_seeker_educations(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT institution, degree, field_of_study, gpa, start_year, end_year
         FROM educations
         WHERE job_seeker_id = :id
         ORDER BY COALESCE(end_year, 9999) DESC, COALESCE(start_year, 0) DESC, id ASC'
    );
    $stmt->execute([':id' => $userId]);
    return $stmt->fetchAll();
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

/** The predefined language list. */
function get_all_languages(): array
{
    return db()->query('SELECT id, name FROM languages ORDER BY name')->fetchAll();
}

/** A job seeker's languages as a list of rows (language_id, overall, speaking, reading, writing). */
function get_job_seeker_languages(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT jl.language_id, jl.overall, jl.speaking, jl.reading, jl.writing
         FROM job_seeker_languages jl
         JOIN languages l ON l.id = jl.language_id
         WHERE jl.job_seeker_id = :id
         ORDER BY l.name'
    );
    $stmt->execute([':id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Clean and validate the submitted form.
 * Returns [cleanData, errors]. Only whitelisted fields are read from $in, so extra
 * fields an attacker adds to the request (e.g. user_id, role) are simply ignored.
 */
function validate_profile_input(array $in, array $validSkillIds, array $validLanguageIds = []): array
{
    $errors = [];
    $data = [
        'full_name'       => trim((string)($in['full_name'] ?? '')),
        'phone'           => trim((string)($in['phone'] ?? '')),
        'city'            => trim((string)($in['city'] ?? '')),
        'bio'             => trim((string)($in['bio'] ?? '')),
        'interests'       => trim((string)($in['interests'] ?? '')),
        'skills'          => [],
        'languages'       => [],   // validated: [language_id => [overall, speaking, reading, writing]]
        'language_rows'   => [],   // what the user typed (used to re-display the cards after an error)
        'educations'      => [],   // validated list of education entries
        'education_rows'  => [],   // what the user typed (used to re-display the cards after an error)
    ];

    if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
        $errors[] = 'Full name must be between 2 and 150 characters.';
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

    // Languages: the form sends one card per language:
    //   languages[INDEX][language_id], [overall], [speaking], [reading], [writing]
    // Every value is checked on the server (IDs and levels against whitelists);
    // the "I am fluent" checkbox is only a browser shortcut and is never sent.
    [$data['languages'], $data['language_rows'], $langErrors] = validate_language_cards($in['languages'] ?? [], $validLanguageIds);
    $errors = array_merge($errors, $langErrors);

    // Education: one card per entry, at least 1 and at most 5.
    [$data['educations'], $data['education_rows'], $eduErrors] = validate_education_cards($in['educations'] ?? []);
    $errors = array_merge($errors, $eduErrors);

    return [$data, $errors];
}

/**
 * Validate the education cards. Returns [validatedList, rowsForRedisplay, errors].
 * Rules: 1..5 entries; school and degree required; GPA 0.00-4.00 (comma accepted);
 * years with 4 digits between 1950 and (this year + 8); end year not before start year.
 * An untouched blank card is ignored.
 */
function validate_education_cards($raw): array
{
    $field = static function (array $row, string $key): ?string {
        $v = $row[$key] ?? '';
        return is_string($v) ? trim($v) : null;      // null = tampered (e.g. an array was sent)
    };
    $keys = ['institution', 'degree', 'field_of_study', 'gpa', 'start_year', 'end_year'];

    $rows = [];
    $tampered = false;
    if (!is_array($raw)) {
        $tampered = true;
        $raw = [];
    }
    foreach ($raw as $row) {
        if (!is_array($row)) {
            $tampered = true;
            break;
        }
        $vals = [];
        foreach ($keys as $k) {
            $vals[$k] = $field($row, $k);
        }
        if (in_array(null, $vals, true)) {
            $tampered = true;
            break;
        }
        if (implode('', $vals) === '') {
            continue;                                  // blank card
        }
        $rows[] = $vals;
    }

    $flags = array_fill_keys(['invalid', 'none', 'too_many', 'missing_institution', 'institution_length',
        'missing_degree', 'field_length', 'gpa', 'year_format', 'year_range', 'year_order'], false);
    $flags['invalid'] = $tampered;
    $flags['none'] = count($rows) === 0;
    $flags['too_many'] = count($rows) > MAX_EDUCATIONS_PER_PROFILE;

    $maxYear = (int)date('Y') + EDUCATION_MAX_YEARS_AHEAD;
    $valid = [];
    foreach ($rows as $r) {
        if ($r['institution'] === '') {
            $flags['missing_institution'] = true;
        } elseif (mb_strlen($r['institution']) < 2 || mb_strlen($r['institution']) > 200) {
            $flags['institution_length'] = true;
        }
        if ($r['degree'] === '') {
            $flags['missing_degree'] = true;
        } elseif (!array_key_exists($r['degree'], EDUCATION_LEVELS)) {
            $flags['invalid'] = true;
        }
        if (mb_strlen($r['field_of_study']) > 150) {
            $flags['field_length'] = true;
        }

        $gpa = null;
        if ($r['gpa'] !== '') {
            $g = str_replace(',', '.', $r['gpa']);     // "3,75" is common in Indonesia
            if (preg_match('/^[0-4](\.\d{1,2})?$/', $g) && (float)$g <= 4.0) {
                $gpa = number_format((float)$g, 2, '.', '');
            } else {
                $flags['gpa'] = true;
            }
        }

        $years = [];
        foreach (['start_year', 'end_year'] as $k) {
            $years[$k] = null;
            if ($r[$k] === '') {
                continue;
            }
            if (!preg_match('/^\d{4}$/', $r[$k])) {
                $flags['year_format'] = true;
            } elseif ((int)$r[$k] < EDUCATION_MIN_YEAR || (int)$r[$k] > $maxYear) {
                $flags['year_range'] = true;
            } else {
                $years[$k] = (int)$r[$k];
            }
        }
        if ($years['start_year'] !== null && $years['end_year'] !== null && $years['end_year'] < $years['start_year']) {
            $flags['year_order'] = true;
        }

        $valid[] = [
            'institution'    => $r['institution'],
            'degree'         => $r['degree'],
            'field_of_study' => $r['field_of_study'] === '' ? null : $r['field_of_study'],
            'gpa'            => $gpa,
            'start_year'     => $years['start_year'],
            'end_year'       => $years['end_year'],
        ];
    }

    $messages = [
        'invalid'             => 'One or more education entries are invalid.',
        'none'                => 'Please add at least one education entry.',
        'too_many'            => 'You can add at most ' . MAX_EDUCATIONS_PER_PROFILE . ' education entries.',
        'missing_institution' => 'Please enter the school or university for every education entry.',
        'institution_length'  => 'School or university must be between 2 and 200 characters.',
        'missing_degree'      => 'Please choose the degree for every education entry.',
        'field_length'        => 'Field of study must not exceed 150 characters.',
        'gpa'                 => 'GPA must be a number from 0.00 to 4.00 (for example 3.75).',
        'year_format'         => 'Years must have 4 digits (for example 2024).',
        'year_range'          => 'Years must be between ' . EDUCATION_MIN_YEAR . ' and ' . $maxYear . '.',
        'year_order'          => 'The end year cannot be earlier than the start year.',
    ];
    $errors = [];
    foreach ($messages as $flag => $text) {
        if ($flags[$flag]) {
            $errors[] = $text;
        }
    }
    return [$errors ? [] : $valid, $rows, $errors];
}

/**
 * Validate the language cards. Returns [validatedMap, rowsForRedisplay, errors].
 * A card with nothing filled in is ignored (the page always shows one empty card).
 */
function validate_language_cards($raw, array $validLanguageIds): array
{
    $field = static function (array $row, string $key): ?string {
        $v = $row[$key] ?? '';
        return is_string($v) ? trim($v) : null;      // null = tampered (e.g. an array was sent)
    };

    $rows = [];
    $tampered = false;
    if (!is_array($raw)) {
        $tampered = true;                              // e.g. languages=abc instead of language cards
        $raw = [];
    }
    if (is_array($raw)) {
        foreach ($raw as $row) {
            if (!is_array($row)) {
                $tampered = true;
                break;
            }
            $vals = ['language_id' => $field($row, 'language_id')];
            foreach (LANGUAGE_ASPECTS as $aspect) {
                $vals[$aspect] = $field($row, $aspect);
            }
            if (in_array(null, $vals, true)) {
                $tampered = true;
                break;
            }
            if (implode('', $vals) === '') {
                continue;                              // blank card
            }
            $rows[] = $vals;
        }
    }

    $flags = ['invalid' => $tampered, 'missing_language' => false, 'missing_overall' => false, 'duplicate' => false, 'too_many' => false];
    if (count($rows) > MAX_LANGUAGES_PER_PROFILE) {
        $flags['too_many'] = true;
    }

    $valid = [];
    foreach ($rows as $r) {
        if ($r['language_id'] === '') {
            $flags['missing_language'] = true;
            continue;
        }
        $lid = (int)$r['language_id'];
        if (!ctype_digit($r['language_id']) || !in_array($lid, $validLanguageIds, true)) {
            $flags['invalid'] = true;
            continue;
        }
        if ($r['overall'] === '') {
            $flags['missing_overall'] = true;
            continue;
        }
        foreach (LANGUAGE_ASPECTS as $aspect) {
            if ($r[$aspect] !== '' && !array_key_exists($r[$aspect], LANGUAGE_LEVELS)) {
                $flags['invalid'] = true;
                continue 2;
            }
        }
        if (isset($valid[$lid])) {
            $flags['duplicate'] = true;
            continue;
        }
        $valid[$lid] = [
            'overall'  => $r['overall'],
            'speaking' => $r['speaking'] === '' ? null : $r['speaking'],
            'reading'  => $r['reading'] === '' ? null : $r['reading'],
            'writing'  => $r['writing'] === '' ? null : $r['writing'],
        ];
    }

    $messages = [
        'invalid'          => 'One or more language entries are invalid.',
        'too_many'         => 'You can add at most ' . MAX_LANGUAGES_PER_PROFILE . ' languages.',
        'missing_language' => 'Please choose a language for every language card you fill in.',
        'missing_overall'  => 'Please choose the overall level for every language you add.',
        'duplicate'        => 'Each language can be added only once.',
    ];
    $errors = [];
    foreach ($messages as $flag => $text) {
        if ($flags[$flag]) {
            $errors[] = $text;
        }
    }
    return [$errors ? [] : $valid, $rows, $errors];
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
             SET full_name = :full_name,
                 phone = :phone, city = :city, bio = :bio, interests = :interests
             WHERE user_id = :id'
        )->execute([
            ':full_name'       => $data['full_name'],
            ':phone'           => $nullIfEmpty($data['phone']),
            ':city'            => $nullIfEmpty($data['city']),
            ':bio'             => $nullIfEmpty($data['bio']),
            ':interests'       => $nullIfEmpty($data['interests']),
            ':id'              => $userId,
        ]);

        // Education entries are synchronised like skills and languages (inside the same transaction).
        $pdo->prepare('DELETE FROM educations WHERE job_seeker_id = :id')->execute([':id' => $userId]);
        $insertEdu = $pdo->prepare(
            'INSERT INTO educations (job_seeker_id, institution, degree, field_of_study, gpa, start_year, end_year)
             VALUES (:uid, :institution, :degree, :field, :gpa, :start, :end)'
        );
        foreach (($data['educations'] ?? []) as $edu) {
            $insertEdu->execute([
                ':uid' => $userId, ':institution' => $edu['institution'], ':degree' => $edu['degree'],
                ':field' => $edu['field_of_study'], ':gpa' => $edu['gpa'],
                ':start' => $edu['start_year'], ':end' => $edu['end_year'],
            ]);
        }

        $pdo->prepare('DELETE FROM job_seeker_skills WHERE job_seeker_id = :id')
            ->execute([':id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO job_seeker_skills (job_seeker_id, skill_id) VALUES (:uid, :sid)'
        );
        foreach ($data['skills'] as $skillId) {
            $insert->execute([':uid' => $userId, ':sid' => $skillId]);
        }

        // Languages are synchronised the same way, inside the same transaction.
        $pdo->prepare('DELETE FROM job_seeker_languages WHERE job_seeker_id = :id')
            ->execute([':id' => $userId]);
        $insertLang = $pdo->prepare(
            'INSERT INTO job_seeker_languages (job_seeker_id, language_id, overall, speaking, reading, writing)
             VALUES (:uid, :lid, :overall, :speaking, :reading, :writing)'
        );
        foreach (($data['languages'] ?? []) as $langId => $lv) {
            $insertLang->execute([
                ':uid' => $userId, ':lid' => $langId,
                ':overall' => $lv['overall'], ':speaking' => $lv['speaking'],
                ':reading' => $lv['reading'], ':writing' => $lv['writing'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;   // the page decides what message to show
    }
}

/** How many of the 7 optional profile details are filled, and which are missing. */
function profile_completeness(array $profile, int $skillCount, int $languageCount = 0): array
{
    $items = [
        'Phone number'          => !empty($profile['phone']),
        'City'                  => !empty($profile['city']),
        'Short bio'             => !empty($profile['bio']),
        'Areas of interest'     => !empty($profile['interests']),
        'At least one skill'    => $skillCount > 0,
        'At least one language' => $languageCount > 0,
        'CV (PDF)'              => !empty($profile['cv_path']),
    ];
    $done = count(array_filter($items));
    return [
        'done'    => $done,
        'total'   => count($items),
        'percent' => (int)round($done / count($items) * 100),
        'missing' => array_keys(array_filter($items, fn($ok) => !$ok)),
    ];
}
