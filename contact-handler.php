<?php
/**
 * Contact / inquiry form handler.
 *
 * Used by both the Contact page and the Gift Card page. On success it:
 *   1. Upserts a unified `clients` row (so every inquiry lands in the CRM list).
 *   2. Records an open follow-up (the "needs a callback / reply" queue).
 *   3. Optionally emails a notification.
 *
 * Spam defense: a hidden honeypot field ("website") + per-session CSRF token.
 * The user is redirected back with a status flag (PRG pattern).
 */

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact.php');
    exit;
}

$return = post('return', '/contact.php');
// Only allow same-site relative returns.
if (!str_starts_with($return, '/') || str_starts_with($return, '//')) {
    $return = '/contact.php';
}

$redirect = static function (string $status) use ($return): void {
    $sep = str_contains($return, '?') ? '&' : '?';
    header('Location: ' . $return . $sep . 'sent=' . $status . '#contact');
    exit;
};

// --- Honeypot: real users never fill this hidden field. Silently "succeed". ---
if (post('website') !== '') {
    $redirect('ok');
}

// --- CSRF ---
if (!csrf_check($_POST['csrf'] ?? null)) {
    $redirect('err');
}

$name  = post('name');
$email = post('email');
$phone = post('phone');
$message = post('message');
$source = post('source') === 'gift_card' ? 'gift_card' : 'contact_form';
$topic  = post('topic'); // e.g. "Gift card purchase"
$optin  = isset($_POST['marketing_opt_in']) ? 1 : 0;

if ($message === '') {
    $message = CONTACT_DEFAULT_MESSAGE;
}

// --- Validation ---
if ($name === '' || ($email === '' && $phone === '')) {
    $redirect('err');
}
if ($email !== '' && !is_email($email)) {
    $redirect('err');
}

try {
    $pdo = db();
    $clientId = upsert_client($pdo, [
        'name' => $name,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'status' => 'lead',
        'source' => $source,
        'marketing_opt_in' => $optin,
        'notes' => $topic ? ('Inquiry topic: ' . $topic) : null,
    ]);

    $kind = $email !== '' ? 'email' : 'callback';
    $fullMsg = ($topic ? "[$topic] " : '') . $message;
    $st = $pdo->prepare('INSERT INTO followups (client_id, kind, message, status, due_date) VALUES (?,?,?,?,date("now"))');
    $st->execute([$clientId, $kind, $fullMsg, 'open']);
} catch (Throwable $e) {
    error_log('contact-handler: ' . $e->getMessage());
    $redirect('err');
}

// --- Optional email notification (best-effort; never blocks success) ---
if (CONTACT_NOTIFY_EMAIL) {
    $subject = ($topic ?: 'Website inquiry') . ' from ' . $name;
    $body = 'New inquiry via ' . parse_url(BUSINESS['url'], PHP_URL_HOST) . "\n\n"
        . "Name: $name\nEmail: " . ($email ?: '—') . "\nPhone: " . ($phone ?: '—') . "\n"
        . ($topic ? "Topic: $topic\n" : '')
        . 'Marketing opt-in: ' . ($optin ? 'Yes' : 'No') . "\n\nMessage:\n$message\n";
    hmp_send_mail(CONTACT_NOTIFY_EMAIL, $subject, $body, $email !== '' ? $email : null);
}

$redirect('ok');
