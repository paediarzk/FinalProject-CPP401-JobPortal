<?php
declare(strict_types=1);

/** Escape output for HTML. ALWAYS use this when printing user data (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Flash messages: shown once, then removed ---------- */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- CSRF protection ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // cryptographically secure
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Compare in constant time; stops forged cross-site form submissions. */
function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/* ---------- Session / access control ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'job_seeker' => 'seeker/dashboard.php',
        'employer'   => 'employer/dashboard.php',
        'admin'      => 'admin/dashboard.php',
        default      => 'index.php',
    };
}

/**
 * An account suspended by the administrator must lose access IMMEDIATELY, not only at the next login.
 * The session still says "logged in", so the database is asked on every protected request
 * (one small query by primary key).
 */
function enforce_active_account(array $user): void
{
    $stmt = db()->prepare('SELECT status FROM users WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
    $status = $stmt->fetchColumn();

    if ($status !== 'active') {                    // suspended, or the account no longer exists
        logout_with_message('warning', $status === false
            ? 'Your session has ended. Please log in again.'
            : 'Your account has been suspended. Please contact the administrator.');
        redirect('login.php');
    }
}

/** Guard: call at top of any protected page. */
function require_role(string $role): array
{
    $user = current_user();
    if ($user === null) {
        flash_set('warning', 'Please log in first.');
        redirect('login.php');
    }
    enforce_active_account($user);
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('403 Forbidden - you do not have access to this page.');
    }
    return $user;
}

/** Keep already-logged-in users away from login/register pages. */
function redirect_if_logged_in(): void
{
    $user = current_user();
    if ($user !== null) {
        redirect(dashboard_path($user['role']));
    }
}

/** Keep typed form values after a validation error (never for passwords). */
function old(string $key): string
{
    return e(trim((string)($_POST[$key] ?? '')));
}
