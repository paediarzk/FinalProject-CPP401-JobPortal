<?php
declare(strict_types=1);

/**
 * Authentication logic (registration + login), kept OUT of the page files.
 * Separation of concerns: pages handle HTML, this file handles rules and SQL.
 */

const EDUCATION_LEVELS = [
    'diploma'  => 'Diploma (D3/D4)',
    'bachelor' => "Bachelor's degree (S1)",
    'master'   => "Master's degree (S2)",
    'other'    => 'Other',
];

/** Rules shared by both registration forms. Returns a list of error messages. */
function validate_account_fields(string $email, string $password, string $confirm): array
{
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }
    return $errors;
}

/**
 * Insert a user row + its profile row as ONE atomic unit (transaction):
 * either both succeed, or neither is saved. Prevents "orphan" accounts.
 * $insertProfile receives (PDO, newUserId) and inserts the role-specific row.
 */
function create_account(string $email, string $password, string $role, callable $insertProfile): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, :role)'
        );
        $stmt->execute([
            ':email' => $email,
            ':hash'  => password_hash($password, PASSWORD_DEFAULT), // bcrypt + automatic salt
            ':role'  => $role,
        ]);
        $userId = (int)$pdo->lastInsertId();

        $insertProfile($pdo, $userId);

        $pdo->commit();
        return [];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 1062 = duplicate entry -> the UNIQUE constraint on users.email fired.
        // Relying on the constraint (not "SELECT then INSERT") avoids race conditions.
        if (($ex->errorInfo[1] ?? 0) === 1062) {
            return ['This email address is already registered.'];
        }
        error_log('Registration failed: ' . $ex->getMessage()); // log details, hide from user
        return ['Registration failed due to a server error. Please try again.'];
    }
}

function register_job_seeker(array $in): array
{
    $name  = trim((string)($in['full_name'] ?? ''));
    $email = strtolower(trim((string)($in['email'] ?? '')));
    $level = (string)($in['education_level'] ?? '');
    $inst  = trim((string)($in['institution'] ?? ''));
    $pass  = (string)($in['password'] ?? '');
    $conf  = (string)($in['password_confirm'] ?? '');

    $errors = validate_account_fields($email, $pass, $conf);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
        $errors[] = 'Full name must be between 2 and 150 characters.';
    }
    if (!array_key_exists($level, EDUCATION_LEVELS)) {
        $errors[] = 'Please choose a valid education level.';
    }
    if (mb_strlen($inst) < 2 || mb_strlen($inst) > 200) {
        $errors[] = 'Institution must be between 2 and 200 characters.';
    }
    if ($errors) {
        return $errors;
    }

    return create_account($email, $pass, 'job_seeker', function (PDO $pdo, int $id) use ($name, $level, $inst) {
        $pdo->prepare('INSERT INTO job_seekers (user_id, full_name) VALUES (:id, :name)')
            ->execute([':id' => $id, ':name' => $name]);

        // The education level and institution typed at registration become the FIRST education entry.
        // (More entries, field of study, GPA and years are added later on the profile page.)
        $pdo->prepare(
            'INSERT INTO educations (job_seeker_id, institution, degree) VALUES (:id, :inst, :level)'
        )->execute([':id' => $id, ':inst' => $inst, ':level' => $level]);
    });
}

function register_employer(array $in): array
{
    $company  = trim((string)($in['company_name'] ?? ''));
    $email    = strtolower(trim((string)($in['email'] ?? '')));
    $industry = trim((string)($in['industry_sector'] ?? ''));
    $desc     = trim((string)($in['description'] ?? ''));
    $pass     = (string)($in['password'] ?? '');
    $conf     = (string)($in['password_confirm'] ?? '');

    $errors = validate_account_fields($email, $pass, $conf);
    if (mb_strlen($company) < 2 || mb_strlen($company) > 200) {
        $errors[] = 'Company name must be between 2 and 200 characters.';
    }
    if (mb_strlen($industry) < 2 || mb_strlen($industry) > 100) {
        $errors[] = 'Industry sector must be between 2 and 100 characters.';
    }
    if (mb_strlen($desc) > 2000) {
        $errors[] = 'Company description must not exceed 2000 characters.';
    }
    if ($errors) {
        return $errors;
    }

    return create_account($email, $pass, 'employer', function (PDO $pdo, int $id) use ($company, $industry, $desc) {
        $pdo->prepare(
            'INSERT INTO employers (user_id, company_name, industry_sector, description)
             VALUES (:id, :company, :industry, :desc)'
        )->execute([
            ':id' => $id, ':company' => $company, ':industry' => $industry,
            ':desc' => $desc === '' ? null : $desc,
        ]);
    });
}

/**
 * Verify credentials. On success, stores the user in the session and returns null.
 * On failure, returns an error message.
 * The message is deliberately GENERIC: it must not reveal whether the email exists
 * (prevents "user enumeration").
 */
function attempt_login(string $email, string $password): ?string
{
    $generic = 'Incorrect email or password.';
    $email = strtolower(trim($email));

    $stmt = db()->prepare(
        'SELECT u.id, u.password_hash, u.role, u.status,
                COALESCE(js.full_name, e.company_name, \'Administrator\') AS display_name
         FROM users u
         LEFT JOIN job_seekers js ON js.user_id = u.id
         LEFT JOIN employers   e  ON e.user_id  = u.id
         WHERE u.email = :email'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Always run password_verify, even for unknown emails, so response time
    // does not leak whether the account exists.
    $hash = $user['password_hash'] ?? '$2y$10$WJjYUD0nJgMkg0T9VLJs1OjdmuOS958cxF5EQ8ExRjC7aAiHBECPu';
    $valid = password_verify($password, $hash);

    if ($user === false || !$valid) {
        return $generic;
    }
    if ($user['status'] !== 'active') {
        return 'This account has been suspended. Please contact the administrator.';
    }

    session_regenerate_id(true); // new session ID after login => prevents session fixation
    $_SESSION['user'] = [
        'id'   => (int)$user['id'],
        'role' => $user['role'],
        'name' => $user['display_name'],
    ];
    return null;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
