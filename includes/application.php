<?php
declare(strict_types=1);

require_once __DIR__ . '/vacancy.php';       // vacancy_today(), POSITION_TYPES
require_once __DIR__ . '/pagination.php';    // paginate(), pagination_html()
require_once __DIR__ . '/profile.php';       // get_job_seeker_profile(), get_job_seeker_educations()
require_once __DIR__ . '/cv.php';            // cv_file_path(), send_cv_file()

/**
 * Job applications (Milestone 3-C).
 *
 *   job seeker applies ............. status 'submitted'   (one application per person per vacancy: UNIQUE in the database)
 *   employer starts reviewing ...... 'under_review'
 *   employer decides ............... 'accepted' or 'rejected'   (final)
 *
 * Security rules used everywhere in this file:
 *   - the job seeker id / employer id ALWAYS comes from the session, never from the request
 *   - an employer only ever reaches applications of THEIR OWN vacancies (every query joins vacancies.employer_id)
 *   - the status is never taken from a form without being checked against APPLICATION_TRANSITIONS
 */

const APPLICATION_PAGE_SIZE = 15;

const APPLICATION_STATUSES = [
    'submitted'    => ['label' => 'Submitted',    'class' => 'text-bg-secondary'],
    'under_review' => ['label' => 'Under review', 'class' => 'text-bg-info'],
    'accepted'     => ['label' => 'Accepted',     'class' => 'text-bg-success'],
    'rejected'     => ['label' => 'Rejected',     'class' => 'text-bg-danger'],
];

/** Which status an employer may move an application to. Accepted and rejected are final. */
const APPLICATION_TRANSITIONS = [
    'submitted'    => ['under_review', 'accepted', 'rejected'],
    'under_review' => ['accepted', 'rejected'],
    'accepted'     => [],
    'rejected'     => [],
];

function application_status_badge(string $status): string
{
    $s = APPLICATION_STATUSES[$status] ?? ['label' => $status, 'class' => 'text-bg-light'];
    return '<span class="badge ' . $s['class'] . '">' . e($s['label']) . '</span>';
}

/* ------------------------------ job seeker ------------------------------ */

/**
 * Apply for a vacancy. Returns one of:
 *   'submitted'  -> done
 *   'not_found'  -> the vacancy does not exist or is not public (not published, or the deadline has passed)
 *   'no_cv'      -> the job seeker has no (readable) CV; the CV is what the employer reviews
 *   'duplicate'  -> already applied (the UNIQUE constraint decides, so two quick clicks cannot both win)
 */
function submit_application(int $seekerId, int $vacancyId): string
{
    $vacancy = get_public_vacancy($vacancyId);                  // same visibility rule as the public pages
    if ($vacancy === null) {
        return 'not_found';
    }
    $profile = get_job_seeker_profile($seekerId);
    if ($profile === null || cv_file_path($profile['cv_path'] ?? null) === null) {
        return 'no_cv';
    }
    try {
        db()->prepare("INSERT INTO applications (job_seeker_id, vacancy_id, status) VALUES (:s, :v, 'submitted')")
            ->execute([':s' => $seekerId, ':v' => $vacancyId]);
    } catch (PDOException $ex) {
        if (($ex->errorInfo[1] ?? 0) === 1062) {                // duplicate entry: uq_app_once
            return 'duplicate';
        }
        throw $ex;
    }
    return 'submitted';
}

/** This job seeker's application for one vacancy (null if none). */
function get_seeker_application(int $seekerId, int $vacancyId): ?array
{
    $stmt = db()->prepare('SELECT id, status, applied_at FROM applications WHERE job_seeker_id = :s AND vacancy_id = :v');
    $stmt->execute([':s' => $seekerId, ':v' => $vacancyId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Number of applications per status for one job seeker (every status present, plus 'all'). */
function get_seeker_application_counts(int $seekerId): array
{
    $counts = array_fill_keys(array_keys(APPLICATION_STATUSES), 0);
    $stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM applications WHERE job_seeker_id = :s GROUP BY status');
    $stmt->execute([':s' => $seekerId]);
    foreach ($stmt->fetchAll() as $r) {
        $counts[$r['status']] = (int)$r['n'];
    }
    $counts['all'] = array_sum($counts);
    return $counts;
}

/** One page of this job seeker's applications, newest first. Returns [rows, total]. */
function search_seeker_applications(int $seekerId, int $limit, int $offset): array
{
    $count = db()->prepare('SELECT COUNT(*) FROM applications WHERE job_seeker_id = :s');
    $count->execute([':s' => $seekerId]);
    $total = (int)$count->fetchColumn();

    $stmt = db()->prepare(
        'SELECT a.id, a.status, a.applied_at, v.id AS vacancy_id, v.title, v.location, v.position_type,
                v.status AS vacancy_status, v.deadline, e.company_name
         FROM applications a
         JOIN vacancies v ON v.id = a.vacancy_id
         JOIN employers e ON e.user_id = v.employer_id
         WHERE a.job_seeker_id = :s
         ORDER BY a.applied_at DESC, a.id DESC
         LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
    );
    $stmt->execute([':s' => $seekerId]);
    return [$stmt->fetchAll(), $total];
}

/* -------------------------------- employer ------------------------------- */

/** Number of applications per status over all vacancies of one employer (plus 'all'). Optional: one vacancy only. */
function get_employer_application_counts(int $employerId, ?int $vacancyId = null): array
{
    $counts = array_fill_keys(array_keys(APPLICATION_STATUSES), 0);
    $sql = 'SELECT a.status, COUNT(*) AS n FROM applications a JOIN vacancies v ON v.id = a.vacancy_id WHERE v.employer_id = :e';
    $params = [':e' => $employerId];
    if ($vacancyId !== null) {
        $sql .= ' AND v.id = :v';
        $params[':v'] = $vacancyId;
    }
    $stmt = db()->prepare($sql . ' GROUP BY a.status');
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $r) {
        $counts[$r['status']] = (int)$r['n'];
    }
    $counts['all'] = array_sum($counts);
    return $counts;
}

/** Applications received per vacancy of one employer: [vacancyId => n]. */
function get_employer_vacancy_application_counts(int $employerId): array
{
    $stmt = db()->prepare(
        'SELECT a.vacancy_id, COUNT(*) AS n FROM applications a JOIN vacancies v ON v.id = a.vacancy_id
         WHERE v.employer_id = :e GROUP BY a.vacancy_id'
    );
    $stmt->execute([':e' => $employerId]);
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
        $map[(int)$r['vacancy_id']] = (int)$r['n'];
    }
    return $map;
}

/**
 * Applications received by one employer. $filters: 'status' (one of APPLICATION_STATUSES, '' = all),
 * 'vacancy' (an id of THIS employer's vacancy, 0 = all). Returns [rows, total]; newest first.
 */
function search_employer_applications(int $employerId, array $filters, int $limit, int $offset): array
{
    $where  = ['v.employer_id = :e'];
    $params = [':e' => $employerId];
    $status = $filters['status'] ?? '';
    if (array_key_exists($status, APPLICATION_STATUSES)) {
        $where[] = 'a.status = :status';
        $params[':status'] = $status;
    }
    $vacancy = (int)($filters['vacancy'] ?? 0);
    if ($vacancy > 0) {
        $where[] = 'v.id = :v';
        $params[':v'] = $vacancy;
    }
    $from = 'FROM applications a
             JOIN vacancies v ON v.id = a.vacancy_id
             JOIN job_seekers js ON js.user_id = a.job_seeker_id
             JOIN users u ON u.id = a.job_seeker_id
             WHERE ' . implode(' AND ', $where);

    $count = db()->prepare('SELECT COUNT(*) ' . $from);
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $stmt = db()->prepare(
        'SELECT a.id, a.status, a.applied_at, v.id AS vacancy_id, v.title, js.full_name, u.email ' . $from .
        ' ORDER BY a.applied_at DESC, a.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset)
    );
    $stmt->execute($params);
    return [$stmt->fetchAll(), $total];
}

/** One received application with the applicant's profile. Null if it does not exist OR belongs to another employer. */
function get_employer_application(int $applicationId, int $employerId): ?array
{
    $stmt = db()->prepare(
        'SELECT a.id, a.status, a.applied_at, a.job_seeker_id, v.id AS vacancy_id, v.title, v.status AS vacancy_status,
                js.full_name, js.phone, js.city, js.bio, js.interests, js.cv_path, u.email
         FROM applications a
         JOIN vacancies v ON v.id = a.vacancy_id
         JOIN job_seekers js ON js.user_id = a.job_seeker_id
         JOIN users u ON u.id = a.job_seeker_id
         WHERE a.id = :id AND v.employer_id = :e'
    );
    $stmt->execute([':id' => $applicationId, ':e' => $employerId]);
    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }
    $row['educations'] = get_job_seeker_educations((int)$row['job_seeker_id']);
    $skills = db()->prepare('SELECT s.name FROM job_seeker_skills js JOIN skills s ON s.id = js.skill_id WHERE js.job_seeker_id = :id ORDER BY s.name');
    $skills->execute([':id' => $row['job_seeker_id']]);
    $row['skills'] = $skills->fetchAll(PDO::FETCH_COLUMN);
    $langs = db()->prepare(
        'SELECT l.name, jl.overall FROM job_seeker_languages jl JOIN languages l ON l.id = jl.language_id
         WHERE jl.job_seeker_id = :id ORDER BY l.name'
    );
    $langs->execute([':id' => $row['job_seeker_id']]);
    $row['languages'] = $langs->fetchAll();
    $row['has_cv'] = cv_file_path($row['cv_path'] ?? null) !== null;
    unset($row['cv_path']);                                      // the stored file name is never sent to a page
    return $row;
}

/**
 * Change the status of a received application. Returns:
 *   'updated' | 'not_found' (missing or another employer's) | 'invalid_status' | 'invalid_transition'
 * The row is LOCKED while it is checked, so two quick clicks cannot both win.
 */
function set_application_status(int $applicationId, int $employerId, string $newStatus): string
{
    if (!array_key_exists($newStatus, APPLICATION_STATUSES) || $newStatus === 'submitted') {
        return 'invalid_status';
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT a.status FROM applications a JOIN vacancies v ON v.id = a.vacancy_id
             WHERE a.id = :id AND v.employer_id = :e FOR UPDATE'
        );
        $stmt->execute([':id' => $applicationId, ':e' => $employerId]);
        $current = $stmt->fetchColumn();
        if ($current === false) {
            $pdo->rollBack();
            return 'not_found';
        }
        if (!in_array($newStatus, APPLICATION_TRANSITIONS[$current] ?? [], true)) {
            $pdo->rollBack();
            return 'invalid_transition';
        }
        $pdo->prepare('UPDATE applications SET status = :s WHERE id = :id')->execute([':s' => $newStatus, ':id' => $applicationId]);
        $pdo->commit();
        return 'updated';
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

/** A positive id from the URL or a form, or 0 when it is not one. */
function read_id($raw): int
{
    return (is_string($raw) && ctype_digit($raw) && strlen($raw) <= 9) ? (int)$raw : 0;
}
