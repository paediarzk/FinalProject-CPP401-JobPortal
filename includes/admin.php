<?php
declare(strict_types=1);

require_once __DIR__ . '/vacancy.php';       // status labels, vacancy_today()
require_once __DIR__ . '/pagination.php';    // escape_like()

/**
 * Administrator logic (Milestone 3-A): statistics, vacancy moderation, account management.
 *
 * Rules enforced HERE (on the server), whatever the browser sends:
 *   approve   pending   -> published   (not for an expired deadline, not for a suspended employer)
 *   reject    pending   -> rejected    (a reason is required)
 *   takedown  published -> rejected    (a reason is required)
 *   suspend / activate: job seeker and employer accounts only; never an administrator, never yourself.
 * Every change runs inside a transaction that first LOCKS the row it is about to change.
 */

const MODERATION_NOTE_MIN = 5;
const MODERATION_NOTE_MAX = 500;
const ADMIN_PAGE_SIZE     = 15;

/* ------------------------------ statistics ------------------------------ */

/** Numbers for the dashboard. Every key is always present (zero when there is nothing). */
function get_admin_stats(): array
{
    $pdo = db();
    $stats = [
        'job_seeker' => ['active' => 0, 'suspended' => 0],
        'employer'   => ['active' => 0, 'suspended' => 0],
        'vacancies'  => array_fill_keys(array_keys(VACANCY_STATUSES), 0),
        'applications' => 0,
    ];
    foreach ($pdo->query("SELECT role, status, COUNT(*) AS n FROM users WHERE role IN ('job_seeker','employer') GROUP BY role, status")->fetchAll() as $r) {
        $stats[$r['role']][$r['status']] = (int)$r['n'];
    }
    foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM vacancies GROUP BY status')->fetchAll() as $r) {
        $stats['vacancies'][$r['status']] = (int)$r['n'];
    }
    $stats['applications'] = (int)$pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    return $stats;
}

/* ------------------------- vacancy moderation --------------------------- */

/**
 * Vacancies for the moderation list. $filters: 'status' (pending|published|rejected|closed|all), 'q' (title or company).
 * Returns [rows, total]. The waiting list (pending) is oldest-first so nobody waits forever.
 */
function search_moderation_vacancies(array $filters, int $limit, int $offset): array
{
    $where = [];
    $params = [];
    $status = $filters['status'] ?? 'pending';
    if (array_key_exists($status, VACANCY_STATUSES)) {
        $where[] = 'v.status = :status';
        $params[':status'] = $status;
    }
    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        // Native prepared statements do not allow the same :name twice, so each use has its own name.
        $where[] = '(v.title LIKE :like1 OR e.company_name LIKE :like2)';
        $params[':like1'] = $params[':like2'] = '%' . escape_like($q) . '%';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $from = 'FROM vacancies v
             JOIN employers e ON e.user_id = v.employer_id
             JOIN users u ON u.id = v.employer_id ' . $whereSql;

    $count = db()->prepare('SELECT COUNT(*) ' . $from);
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $order = $status === 'pending' ? 'ASC' : 'DESC';
    $stmt = db()->prepare(
        'SELECT v.id, v.title, v.position_type, v.location, v.deadline, v.status, v.created_at,
                e.company_name, u.status AS employer_status ' . $from .
        " ORDER BY v.created_at $order, v.id $order LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset
    );
    $stmt->execute($params);
    return [$stmt->fetchAll(), $total];
}

/** Number of vacancies per status, plus the total under key 'all' (for the filter tabs). */
function get_vacancy_status_counts(): array
{
    $counts = array_fill_keys(array_keys(VACANCY_STATUSES), 0);
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM vacancies GROUP BY status')->fetchAll() as $r) {
        $counts[$r['status']] = (int)$r['n'];
    }
    $counts['all'] = array_sum($counts);
    return $counts;
}

/** One vacancy with everything a moderator needs (employer, skills). Null if the id does not exist. */
function get_vacancy_for_moderation(int $vacancyId): ?array
{
    $stmt = db()->prepare(
        'SELECT v.id, v.title, v.position_type, v.description, v.location, v.allowance, v.deadline,
                v.status, v.moderation_note, v.created_at,
                e.company_name, e.industry_sector, e.description AS company_description,
                u.email AS employer_email, u.status AS employer_status
         FROM vacancies v
         JOIN employers e ON e.user_id = v.employer_id
         JOIN users u ON u.id = v.employer_id
         WHERE v.id = :id'
    );
    $stmt->execute([':id' => $vacancyId]);
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

/** Validate the reason typed by the administrator. Returns [cleanNote|null, errorMessage|null]. */
function validate_moderation_note(string $note): array
{
    $note = trim($note);
    $length = mb_strlen($note);
    if ($length < MODERATION_NOTE_MIN || $length > MODERATION_NOTE_MAX) {
        return [null, 'Please give a reason of ' . MODERATION_NOTE_MIN . ' to ' . MODERATION_NOTE_MAX . ' characters.'];
    }
    return [$note, null];
}

/**
 * Apply a moderation decision. Returns one of:
 *   'approved' | 'rejected' | 'taken_down'                      -> done
 *   'not_found' | 'invalid_action' | 'invalid_state'
 *   'expired' | 'employer_suspended'                            -> approval refused (see rules above)
 */
function moderate_vacancy(int $vacancyId, string $action, ?string $note): string
{
    if (!in_array($action, ['approve', 'reject', 'takedown'], true)) {
        return 'invalid_action';
    }
    if ($action !== 'approve' && $note === null) {
        return 'invalid_action';                      // rejecting always needs a reason
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Lock the row: two administrators clicking at the same moment cannot both win.
        $stmt = $pdo->prepare(
            'SELECT v.status, v.deadline, u.status AS employer_status
             FROM vacancies v JOIN users u ON u.id = v.employer_id
             WHERE v.id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $vacancyId]);
        $row = $stmt->fetch();
        if ($row === false) {
            $pdo->rollBack();
            return 'not_found';
        }

        $required = $action === 'takedown' ? 'published' : 'pending';
        if ($row['status'] !== $required) {
            $pdo->rollBack();
            return 'invalid_state';
        }

        if ($action === 'approve') {
            if ($row['deadline'] < vacancy_today()->format('Y-m-d')) {
                $pdo->rollBack();
                return 'expired';
            }
            if ($row['employer_status'] !== 'active') {
                $pdo->rollBack();
                return 'employer_suspended';
            }
            $pdo->prepare("UPDATE vacancies SET status = 'published', moderation_note = NULL WHERE id = :id")
                ->execute([':id' => $vacancyId]);
            $result = 'approved';
        } else {
            $pdo->prepare("UPDATE vacancies SET status = 'rejected', moderation_note = :note WHERE id = :id")
                ->execute([':note' => $note, ':id' => $vacancyId]);
            $result = $action === 'reject' ? 'rejected' : 'taken_down';
        }

        $pdo->commit();
        return $result;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

/* ---------------------------- account management ------------------------- */

/**
 * Search accounts. $filters: 'q' (e-mail, full name or company), 'role' (job_seeker|employer|admin), 'status' (active|suspended).
 * Returns [rows, total]. Unknown filter values are ignored, never put into the SQL.
 */
function search_users(array $filters, int $limit, int $offset): array
{
    $where = [];
    $params = [];
    $role = $filters['role'] ?? '';
    if (in_array($role, ['job_seeker', 'employer', 'admin'], true)) {
        $where[] = 'u.role = :role';
        $params[':role'] = $role;
    }
    $status = $filters['status'] ?? '';
    if (in_array($status, ['active', 'suspended'], true)) {
        $where[] = 'u.status = :status';
        $params[':status'] = $status;
    }
    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(u.email LIKE :like1 OR js.full_name LIKE :like2 OR e.company_name LIKE :like3)';
        $params[':like1'] = $params[':like2'] = $params[':like3'] = '%' . escape_like($q) . '%';
    }
    $from = 'FROM users u
             LEFT JOIN job_seekers js ON js.user_id = u.id
             LEFT JOIN employers e ON e.user_id = u.id ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '');

    $count = db()->prepare('SELECT COUNT(*) ' . $from);
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $stmt = db()->prepare(
        "SELECT u.id, u.email, u.role, u.status, u.created_at,
                COALESCE(js.full_name, e.company_name, 'Administrator') AS display_name " . $from .
        ' ORDER BY u.created_at DESC, u.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
    );
    $stmt->execute($params);
    return [$stmt->fetchAll(), $total];
}

/**
 * Suspend or re-activate an account. Returns:
 *   'suspended' | 'activated' | 'unchanged'          -> done / nothing to do
 *   'not_found' | 'self' | 'admin' | 'invalid_status'  -> refused
 */
function set_user_status(int $adminId, int $targetId, string $newStatus): string
{
    if (!in_array($newStatus, ['active', 'suspended'], true)) {
        return 'invalid_status';
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT role, status FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $targetId]);
        $row = $stmt->fetch();

        if ($row === false) {
            $pdo->rollBack();
            return 'not_found';
        }
        if ($targetId === $adminId) {                  // an administrator must not lock themselves out
            $pdo->rollBack();
            return 'self';
        }
        if ($row['role'] === 'admin') {                // administrator accounts are not managed here
            $pdo->rollBack();
            return 'admin';
        }
        if ($row['status'] === $newStatus) {
            $pdo->rollBack();
            return 'unchanged';
        }
        $pdo->prepare('UPDATE users SET status = :s WHERE id = :id')->execute([':s' => $newStatus, ':id' => $targetId]);
        $pdo->commit();
        return $newStatus === 'suspended' ? 'suspended' : 'activated';
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

/**
 * The list pages remember their filters in a hidden field ("back") so that after an action the
 * administrator returns to the same list. The text is NEVER used as an address: it is parsed and only
 * the four known keys are copied, so it cannot redirect anywhere else.
 */
function safe_back_query($raw): array
{
    $clean = [];
    if (!is_string($raw)) {
        return $clean;
    }
    parse_str(substr($raw, 0, 300), $parsed);
    foreach (['q', 'role', 'status', 'page'] as $key) {
        if (isset($parsed[$key]) && is_string($parsed[$key]) && $parsed[$key] !== '') {
            $clean[$key] = mb_substr($parsed[$key], 0, 100);
        }
    }
    return $clean;
}
