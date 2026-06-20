<?php
/** Small shared helpers used across the public site and CRM. */

declare(strict_types=1);

/** HTML-escape. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Trimmed POST string. */
function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

/** Trimmed GET string. */
function get(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

/** Basic email sanity check. */
function is_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Friendly date display, e.g. "Jun 20, 2026". */
function fmt_date(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date('M j, Y', $ts) : e($iso);
}

/** Per-session CSRF token. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Validate a CSRF token from a submitted form. */
function csrf_check(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/** Render a hidden CSRF field. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Find an existing client by email or phone, or create a new one.
 * Used to unify contact-form leads, bookings and intake submissions.
 */
function upsert_client(PDO $pdo, array $data): int
{
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;

    $found = null;
    if ($email) {
        $st = $pdo->prepare('SELECT id FROM clients WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $found = $st->fetchColumn();
    }
    if (!$found && $phone) {
        $st = $pdo->prepare('SELECT id FROM clients WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        $found = $st->fetchColumn();
    }

    if ($found) {
        // Enrich the existing record without clobbering populated fields.
        $st = $pdo->prepare(
            'UPDATE clients SET
                email = COALESCE(NULLIF(email, ""), ?),
                phone = COALESCE(NULLIF(phone, ""), ?),
                marketing_opt_in = MAX(marketing_opt_in, ?),
                updated_at = datetime("now")
             WHERE id = ?'
        );
        $st->execute([$email, $phone, (int) ($data['marketing_opt_in'] ?? 0), (int) $found]);
        return (int) $found;
    }

    $st = $pdo->prepare(
        'INSERT INTO clients (name, email, phone, status, source, marketing_opt_in, preferences, notes)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $st->execute([
        $data['name'] ?? 'Unknown',
        $email,
        $phone,
        $data['status'] ?? 'lead',
        $data['source'] ?? 'manual',
        (int) ($data['marketing_opt_in'] ?? 0),
        $data['preferences'] ?? null,
        $data['notes'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}
