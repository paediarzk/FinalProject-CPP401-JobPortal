<?php
declare(strict_types=1);

/**
 * Vacancy logic for employers (Milestone 2-C).
 *
 * Life cycle of a vacancy (status):
 *   new vacancy ............................ pending   (waits for an administrator - Milestone 3)
 *   administrator approves ................. published
 *   administrator rejects .................. rejected
 *   employer edits a published/rejected one  -> pending again (it must be re-checked)
 *   employer closes it ..................... closed    (final; there is no hard delete)
 *
 * Security rules used everywhere in this file:
 *   - the employer id ALWAYS comes from the session, never from the request
 *   - every query that touches one vacancy also filters on employer_id (nobody can read or change
 *     another employer's vacancy, even if they guess its number)
 *   - the status is never taken from a form
 */

const VACANCY_MAX_SKILLS         = 10;
const VACANCY_MAX_DEADLINE_DAYS  = 365;
const VACANCY_MAX_ALLOWANCE      = 1000000000;      // 1 billion per month; protects against typos

const POSITION_TYPES = [
    'internship'  => 'Internship',
    'entry_level' => 'Entry-level',
];

const VACANCY_STATUSES = [
    'pending'   => ['label' => 'Pending approval', 'class' => 'text-bg-warning'],
    'published' => ['label' => 'Published',        'class' => 'text-bg-success'],
    'rejected'  => ['label' => 'Rejected',         'class' => 'text-bg-danger'],
    'closed'    => ['label' => 'Closed',           'class' => 'text-bg-secondary'],
];

/**
 * "Today" in the user's time zone. XAMPP's php.ini usually says Europe/Berlin, which is several
 * hours behind Jakarta, so the app must NOT depend on the server's default time zone.
 * Override with define('APP_TIMEZONE', '...') in config/config.php if needed.
 */
function vacancy_today(): DateTimeImmutable
{
    $zone = new DateTimeZone(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Jakarta');
    return new DateTimeImmutable('today', $zone);
}

/** HTML for a coloured status label. Only fixed class names and an escaped label are printed. */
function vacancy_status_badge(string $status): string
{
    $s = VACANCY_STATUSES[$status] ?? ['label' => $status, 'class' => 'text-bg-light'];
    return '<span class="badge ' . $s['class'] . '">' . e($s['label']) . '</span>';
}

/** True when the application deadline has passed but the vacancy is still pending or published. */
function vacancy_is_expired(array $vacancy): bool
{
    return in_array($vacancy['status'], ['pending', 'published'], true)
        && $vacancy['deadline'] < vacancy_today()->format('Y-m-d');
}

/* ----------------------------- reading ----------------------------- */

/** All vacancies of ONE employer, newest first, with their skill names joined into one text. */
function get_employer_vacancies(int $employerId, ?int $limit = null): array
{
    $sql = 'SELECT v.id, v.title, v.position_type, v.location, v.allowance, v.deadline, v.status, v.moderation_note, v.created_at,
                   GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR \', \') AS skill_names
            FROM vacancies v
            LEFT JOIN vacancy_skills vs ON vs.vacancy_id = v.id
            LEFT JOIN skills s ON s.id = vs.skill_id
            WHERE v.employer_id = :e
            GROUP BY v.id
            ORDER BY v.created_at DESC, v.id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);              // $limit is an int, so this is safe
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([':e' => $employerId]);
    return $stmt->fetchAll();
}

/** Number of vacancies per status for one employer (every status present, zero when none). */
function get_employer_vacancy_counts(int $employerId): array
{
    $counts = array_fill_keys(array_keys(VACANCY_STATUSES), 0);
    $stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM vacancies WHERE employer_id = :e GROUP BY status');
    $stmt->execute([':e' => $employerId]);
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['status']] = (int)$row['n'];
    }
    return $counts;
}

/** One vacancy that belongs to this employer (null if it does not exist OR belongs to someone else). */
function get_vacancy_for_employer(int $vacancyId, int $employerId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, title, position_type, description, location, allowance, deadline, status, moderation_note
         FROM vacancies WHERE id = :id AND employer_id = :e'
    );
    $stmt->execute([':id' => $vacancyId, ':e' => $employerId]);
    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }
    $skills = db()->prepare('SELECT skill_id FROM vacancy_skills WHERE vacancy_id = :id');
    $skills->execute([':id' => $vacancyId]);
    $row['skill_ids'] = array_map('intval', $skills->fetchAll(PDO::FETCH_COLUMN));
    return $row;
}

/* --------------------------- validation ---------------------------- */

/**
 * Turn "2500000", "2.500.000" or "2,500,000" into a whole number.
 * Returns [valueOrNull, errorOrNull]. An empty text means "not stated" (NULL).
 */
function parse_allowance(string $raw): array
{
    if ($raw === '') {
        return [null, null];
    }
    $compact = str_replace(' ', '', $raw);
    if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $compact)) {       // thousand separators in groups of 3
        $digits = preg_replace('/[.,]/', '', $compact);
    } elseif (preg_match('/^\d+$/', $compact)) {
        $digits = $compact;
    } else {
        return [null, 'Allowance must be a whole number, for example 2500000.'];
    }
    if (strlen($digits) > 12 || (int)$digits > VACANCY_MAX_ALLOWANCE) {
        return [null, 'Allowance is too large. Please check the amount.'];
    }
    return [number_format((float)$digits, 2, '.', ''), null];
}

/**
 * Clean and validate the vacancy form. Returns [data, errors].
 * Only the fields listed here are read, so extra fields an attacker adds
 * (status, employer_id, id, ...) are ignored.
 */
function validate_vacancy_input(array $in, array $validSkillIds): array
{
    $errors = [];
    $tampered = false;
    $field = static function (string $key) use ($in, &$tampered): string {
        $v = $in[$key] ?? '';
        if (!is_string($v)) {
            $tampered = true;                            // e.g. title[]=x was sent
            return '';
        }
        return trim($v);
    };

    $data = [
        'title'         => $field('title'),
        'position_type' => $field('position_type'),
        'location'      => $field('location'),
        'allowance_raw' => $field('allowance'),
        'deadline'      => $field('deadline'),
        'description'   => $field('description'),
        'allowance'     => null,
        'skills'        => [],
    ];

    if (mb_strlen($data['title']) < 3 || mb_strlen($data['title']) > 200) {
        $errors[] = 'Position title must be between 3 and 200 characters.';
    }
    if (!array_key_exists($data['position_type'], POSITION_TYPES)) {
        $errors[] = 'Please choose a position type.';
    }
    if (mb_strlen($data['location']) < 2 || mb_strlen($data['location']) > 150) {
        $errors[] = 'Location must be between 2 and 150 characters.';
    }

    [$data['allowance'], $allowanceError] = parse_allowance($data['allowance_raw']);
    if ($allowanceError !== null) {
        $errors[] = $allowanceError;
    }

    // Deadline: a real calendar date, from today up to one year ahead (Jakarta time).
    $today = vacancy_today();
    if ($data['deadline'] === '') {
        $errors[] = 'Please choose an application deadline.';
    } else {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['deadline'], $today->getTimezone());
        if ($date === false || $date->format('Y-m-d') !== $data['deadline']) {
            $errors[] = 'The application deadline is not a valid date.';
        } elseif ($date < $today) {
            $errors[] = 'The application deadline cannot be in the past.';
        } elseif ($date > $today->modify('+' . VACANCY_MAX_DEADLINE_DAYS . ' days')) {
            $errors[] = 'The application deadline must be within one year from today.';
        }
    }

    if (mb_strlen($data['description']) < 20 || mb_strlen($data['description']) > 5000) {
        $errors[] = 'Job description must be between 20 and 5000 characters.';
    }

    // Required skills (optional, at most 10): every ID must exist in the predefined list.
    $raw = $in['skills'] ?? [];
    $ids = [];
    if (is_array($raw)) {
        $scalars = array_filter($raw, 'is_scalar');
        if (count($scalars) !== count($raw)) {
            $tampered = true;                            // something other than plain values was sent
        }
        $ids = array_values(array_unique(array_map('intval', $scalars)));
    } elseif ($raw !== []) {
        $tampered = true;                                // e.g. skills=abc
    }
    foreach ($ids as $id) {
        if (!in_array($id, $validSkillIds, true)) {
            $errors[] = 'One or more selected skills are invalid.';
            $ids = [];
            break;
        }
    }
    if (count($ids) > VACANCY_MAX_SKILLS) {
        $errors[] = 'You can select at most ' . VACANCY_MAX_SKILLS . ' skills.';
    }
    $data['skills'] = $ids;

    if ($tampered) {
        $errors[] = 'Invalid form submission. Please reload the page and try again.';
    }
    return [$data, $errors];
}

/* ----------------------------- writing ----------------------------- */

/**
 * Create ($vacancyId = null) or update a vacancy together with its skills, as ONE atomic unit.
 * Returns [vacancyId|null, errorCode|null, newStatus|null].
 * errorCode: 'not_found' (missing or someone else's) or 'closed' (closed vacancies cannot be edited).
 */
function save_vacancy(int $employerId, ?int $vacancyId, array $data): array
{
    $pdo = db();
    $params = [
        ':title' => $data['title'], ':type' => $data['position_type'], ':description' => $data['description'],
        ':location' => $data['location'], ':allowance' => $data['allowance'], ':deadline' => $data['deadline'],
    ];

    try {
        $pdo->beginTransaction();

        if ($vacancyId === null) {
            $pdo->prepare(
                "INSERT INTO vacancies (employer_id, title, position_type, description, location, allowance, deadline, status)
                 VALUES (:employer, :title, :type, :description, :location, :allowance, :deadline, 'pending')"
            )->execute($params + [':employer' => $employerId]);
            $id = (int)$pdo->lastInsertId();
            $status = 'pending';
        } else {
            // Lock the row and verify ownership + state INSIDE the transaction.
            $stmt = $pdo->prepare('SELECT status FROM vacancies WHERE id = :id AND employer_id = :e FOR UPDATE');
            $stmt->execute([':id' => $vacancyId, ':e' => $employerId]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                $pdo->rollBack();
                return [null, 'not_found', null];
            }
            if ($current === 'closed') {
                $pdo->rollBack();
                return [null, 'closed', null];
            }
            // Editing something that was already reviewed means the new text must be reviewed again.
            $status = in_array($current, ['published', 'rejected'], true) ? 'pending' : $current;

            $pdo->prepare(
                'UPDATE vacancies
                 SET title = :title, position_type = :type, description = :description, location = :location,
                     allowance = :allowance, deadline = :deadline, status = :status,
                     moderation_note = NULL            -- new text: the old reason no longer applies
                 WHERE id = :id AND employer_id = :employer'
            )->execute($params + [':status' => $status, ':id' => $vacancyId, ':employer' => $employerId]);
            $pdo->prepare('DELETE FROM vacancy_skills WHERE vacancy_id = :id')->execute([':id' => $vacancyId]);
            $id = $vacancyId;
        }

        $insert = $pdo->prepare('INSERT INTO vacancy_skills (vacancy_id, skill_id) VALUES (:v, :s)');
        foreach ($data['skills'] as $skillId) {
            $insert->execute([':v' => $id, ':s' => $skillId]);
        }

        $pdo->commit();
        return [$id, null, $status];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;                                       // the page decides what to tell the user
    }
}

/** Close a vacancy. Returns 'closed', 'already_closed' or 'not_found' (missing or someone else's). */
function close_vacancy(int $vacancyId, int $employerId): string
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT status FROM vacancies WHERE id = :id AND employer_id = :e');
    $stmt->execute([':id' => $vacancyId, ':e' => $employerId]);
    $status = $stmt->fetchColumn();

    if ($status === false) {
        return 'not_found';
    }
    if ($status === 'closed') {
        return 'already_closed';
    }
    $pdo->prepare("UPDATE vacancies SET status = 'closed' WHERE id = :id AND employer_id = :e")
        ->execute([':id' => $vacancyId, ':e' => $employerId]);
    return 'closed';
}

/* ===================== public vacancies (Milestone 3-B) =====================
 * What a visitor (guest, job seeker, employer or administrator) may see:
 *   status = 'published'  AND  deadline today or later (Jakarta time, see vacancy_today()).
 * Pending, rejected and closed vacancies - and published ones whose deadline has passed - are never returned,
 * so every page that uses these functions is safe to show to anyone.
 */

const PUBLIC_PAGE_SIZE     = 12;     // results per page on vacancies.php
const PUBLIC_HOME_LATEST   = 6;      // vacancies shown on the home page
const PUBLIC_FILTER_MAXLEN = 100;    // keyword / location length limit (same as the administrator search)

/**
 * Read the search criteria from the URL (?q=...&type=...&location=...).
 * Nothing is trusted: arrays, unknown position types and over-long text are reduced to "no filter".
 * Returns ['q' => string, 'type' => string, 'location' => string]; an empty string means "not used".
 */
function read_public_filters(array $get): array
{
    $text = static function (string $key) use ($get): string {
        $v = $get[$key] ?? '';
        return is_string($v) ? mb_substr(trim($v), 0, PUBLIC_FILTER_MAXLEN) : '';
    };
    $type = $get['type'] ?? '';
    return [
        'q'        => $text('q'),
        'type'     => (is_string($type) && array_key_exists($type, POSITION_TYPES)) ? $type : '',
        'location' => $text('location'),
    ];
}

/** Build the WHERE part shared by the count and the list, so both always agree. Returns [sql, params]. */
function public_vacancy_where(array $filters): array
{
    $where  = ["v.status = 'published'", 'v.deadline >= :today'];
    $params = [':today' => vacancy_today()->format('Y-m-d')];

    if (($filters['type'] ?? '') !== '' && array_key_exists($filters['type'], POSITION_TYPES)) {
        $where[] = 'v.position_type = :type';
        $params[':type'] = $filters['type'];
    }
    $location = trim((string)($filters['location'] ?? ''));
    if ($location !== '') {
        $where[] = 'v.location LIKE :loc';
        $params[':loc'] = '%' . escape_like($location) . '%';
    }
    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        // Keyword: position title, company name, or the name of a required skill (whole text, partial match).
        // Native prepared statements do not allow the same :name twice, so each use has its own name.
        $where[] = '(v.title LIKE :like1 OR e.company_name LIKE :like2 OR EXISTS (
                        SELECT 1 FROM vacancy_skills vs2 JOIN skills s2 ON s2.id = vs2.skill_id
                        WHERE vs2.vacancy_id = v.id AND s2.name LIKE :like3))';
        $params[':like1'] = $params[':like2'] = $params[':like3'] = '%' . escape_like($q) . '%';
    }
    return [implode(' AND ', $where), $params];
}

/** Number of public vacancies that match the criteria. */
function count_public_vacancies(array $filters): int
{
    [$where, $params] = public_vacancy_where($filters);
    $stmt = db()->prepare('SELECT COUNT(*) FROM vacancies v JOIN employers e ON e.user_id = v.employer_id WHERE ' . $where);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * One page of public vacancies, newest first. The id is a tie-breaker so that vacancies posted in the same
 * second keep a fixed order: no row appears twice or disappears when the visitor moves between pages.
 */
function search_public_vacancies(array $filters, int $limit, int $offset): array
{
    [$where, $params] = public_vacancy_where($filters);
    $stmt = db()->prepare(
        'SELECT v.id, v.title, v.position_type, v.location, v.allowance, v.deadline, v.created_at,
                e.company_name,
                (SELECT GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR \', \')
                   FROM vacancy_skills vs JOIN skills s ON s.id = vs.skill_id
                  WHERE vs.vacancy_id = v.id) AS skill_names
         FROM vacancies v
         JOIN employers e ON e.user_id = v.employer_id
         WHERE ' . $where . '
         ORDER BY v.created_at DESC, v.id DESC
         LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)      // both are ints, so this is safe
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** One public vacancy with company information and skills. Null if it does not exist OR is not public. */
function get_public_vacancy(int $vacancyId): ?array
{
    $stmt = db()->prepare(
        "SELECT v.id, v.title, v.position_type, v.description, v.location, v.allowance, v.deadline, v.created_at,
                e.company_name, e.industry_sector, e.description AS company_description
         FROM vacancies v
         JOIN employers e ON e.user_id = v.employer_id
         WHERE v.id = :id AND v.status = 'published' AND v.deadline >= :today"
    );
    $stmt->execute([':id' => $vacancyId, ':today' => vacancy_today()->format('Y-m-d')]);
    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }
    $skills = db()->prepare(
        'SELECT s.name FROM vacancy_skills vs JOIN skills s ON s.id = vs.skill_id WHERE vs.vacancy_id = :id ORDER BY s.name'
    );
    $skills->execute([':id' => $vacancyId]);
    $row['skills'] = $skills->fetchAll(PDO::FETCH_COLUMN);
    return $row;
}
