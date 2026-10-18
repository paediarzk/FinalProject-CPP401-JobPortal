<?php
declare(strict_types=1);

/**
 * CV (PDF) upload, storage and delivery.
 *
 * Defence in depth - an upload must pass ALL of these layers:
 *   1. upload error code + size limit
 *   2. extension is .pdf
 *   3. real MIME type is application/pdf (finfo reads the file CONTENT, not the name)
 *   4. file starts with the PDF "magic bytes" %PDF-
 * and then:
 *   5. the file gets a RANDOM name (the user's file name is never used on disk)
 *   6. it is stored OUTSIDE the web root, so it has no public URL at all
 *   7. it can only be read through a PHP script that checks who is asking
 */

const CV_MAX_BYTES = 2 * 1024 * 1024;   // 2 MB

/**
 * Folder where CV files live. By default: <xampp>/jobportal_storage/cv,
 * i.e. next to "htdocs", NOT inside it. Apache only serves files inside htdocs,
 * so these files cannot be opened by typing a URL.
 * To use another folder, define('CV_STORAGE_DIR', '...') in config/config.php.
 */
function cv_storage_dir(): string
{
    if (defined('CV_STORAGE_DIR')) {
        return CV_STORAGE_DIR;
    }
    return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'jobportal_storage' . DIRECTORY_SEPARATOR . 'cv';
}

function ensure_cv_storage_dir(): bool
{
    $dir = cv_storage_dir();
    return is_dir($dir) || @mkdir($dir, 0775, true) || is_dir($dir);
}

/**
 * Turn a stored name into a full path, or null if it is not a valid stored name.
 * The strict pattern (32 hex characters + ".pdf") means a value like "../../secret"
 * can never be turned into a path (blocks path traversal).
 */
function cv_file_path(?string $storedName): ?string
{
    if ($storedName === null || !preg_match('/^[a-f0-9]{32}\.pdf$/', $storedName)) {
        return null;
    }
    $path = cv_storage_dir() . DIRECTORY_SEPARATOR . $storedName;
    return is_file($path) ? $path : null;
}

/** A safe, ASCII-only download name built from the profile name, never from user-supplied file names. */
function cv_download_name(string $fullName): string
{
    $clean = trim((string)preg_replace('/[^A-Za-z0-9]+/', '_', $fullName), '_');
    return 'CV_' . ($clean !== '' ? $clean : 'applicant') . '.pdf';
}

/** Validate one entry of $_FILES. Returns a list of error messages (empty = OK). */
function validate_cv_upload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['Invalid upload. Please choose a PDF file.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['Please choose a PDF file to upload.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['The file is larger than 2 MB. Please upload a smaller PDF.'];
        default:
            return ['The upload failed (error ' . (int)$file['error'] . '). Please try again.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        return ['The file is empty.'];
    }
    if ($size > CV_MAX_BYTES) {
        return ['The file is larger than 2 MB. Please upload a smaller PDF.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {            // must be a file PHP received through HTTP
        return ['Invalid upload. Please try again.'];
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['Only PDF files are allowed.'];
    }

    // The name and the browser-declared type can be faked; the CONTENT cannot.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if ($mime !== 'application/pdf') {
        return ['Only PDF files are allowed. The selected file is not a real PDF.'];
    }
    $handle = fopen($tmp, 'rb');
    $head = $handle ? fread($handle, 5) : '';
    if ($handle) {
        fclose($handle);
    }
    if ($head !== '%PDF-') {
        return ['Only PDF files are allowed. The selected file is not a real PDF.'];
    }

    return [];
}

/** Validate, store and register a CV. Replaces (and deletes) the previous CV. Returns error messages. */
function store_cv_for_user(int $userId, array $file): array
{
    $errors = validate_cv_upload($file);
    if ($errors) {
        return $errors;
    }
    if (!ensure_cv_storage_dir()) {
        error_log('CV storage folder could not be created: ' . cv_storage_dir());
        return ['The CV could not be saved due to a server error. Please try again.'];
    }

    $newName = bin2hex(random_bytes(16)) . '.pdf';             // 32 random hex chars
    $dest    = cv_storage_dir() . DIRECTORY_SEPARATOR . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        error_log('move_uploaded_file failed for ' . $dest);
        return ['The CV could not be saved due to a server error. Please try again.'];
    }

    $pdo = db();
    try {
        $stmt = $pdo->prepare('SELECT cv_path FROM job_seekers WHERE user_id = :id');
        $stmt->execute([':id' => $userId]);
        $oldName = $stmt->fetchColumn() ?: null;

        $pdo->prepare('UPDATE job_seekers SET cv_path = :p WHERE user_id = :id')
            ->execute([':p' => $newName, ':id' => $userId]);
    } catch (PDOException $ex) {
        @unlink($dest);                                         // no orphan file if the database failed
        error_log('CV database update failed: ' . $ex->getMessage());
        return ['The CV could not be saved due to a server error. Please try again.'];
    }

    // Delete the old file only AFTER the database points to the new one.
    $oldPath = cv_file_path(is_string($oldName) ? $oldName : null);
    if ($oldPath !== null) {
        @unlink($oldPath);
    }
    return [];
}

function delete_cv_for_user(int $userId): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT cv_path FROM job_seekers WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    $oldName = $stmt->fetchColumn() ?: null;

    $pdo->prepare('UPDATE job_seekers SET cv_path = NULL WHERE user_id = :id')->execute([':id' => $userId]);

    $oldPath = cv_file_path(is_string($oldName) ? $oldName : null);
    if ($oldPath !== null) {
        @unlink($oldPath);
    }
}

/**
 * Send a stored CV to the browser. The CALLER must already have checked that the
 * current user is allowed to see this file (owner now; employer-with-application in Milestone 3).
 */
function send_cv_file(?string $storedName, string $downloadName): never
{
    $path = cv_file_path($storedName);
    if ($path === null) {
        http_response_code(404);
        exit('CV not found.');
    }

    session_write_close();                                      // do not keep the session locked while streaming
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');                  // browser must trust our Content-Type
    header('Cache-Control: private, no-store');                 // personal data: do not cache
    readfile($path);
    exit;
}
