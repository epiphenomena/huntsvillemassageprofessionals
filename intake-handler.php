<?php
/**
 * Digital intake + consent form handler.
 *
 * Stores the full submission in `intakes`, and upserts a unified `clients`
 * record (carrying massage preferences/allergies + marketing opt-in) so the
 * client appears in the CRM. Honeypot + CSRF protected; prepared statements.
 */

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$redirect = static function (string $status): void {
    header('Location: /forms.php?sent=' . $status . '#main');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirect('err');
}
if (post('website') !== '') {
    $redirect('ok'); // honeypot tripped
}
if (!csrf_check($_POST['csrf'] ?? null)) {
    $redirect('err');
}

$name  = post('name');
$email = post('email');
$phone = post('phone');
$consentAgreed = isset($_POST['consent_agreed']) ? 1 : 0;
$signature = post('consent_signature');

if ($name === '' || $email === '' || $phone === '' || !$consentAgreed || $signature === '') {
    $redirect('err');
}
if (!is_email($email)) {
    $redirect('err');
}

$conditions = $_POST['conditions'] ?? [];
if (!is_array($conditions)) {
    $conditions = [];
}
$conditions = array_values(array_filter(array_map('strval', $conditions)));
$optin = isset($_POST['marketing_opt_in']) ? 1 : 0;

$allergies   = post('allergies');
$medications = post('medications');

try {
    $pdo = db();

    // Build a preferences note for the CRM from health details.
    $prefBits = [];
    if ($allergies !== '')   { $prefBits[] = 'Allergies: ' . $allergies; }
    if ($medications !== '') { $prefBits[] = 'Medications: ' . $medications; }
    if (post('reason') !== ''){ $prefBits[] = 'Reason: ' . post('reason'); }

    $clientId = upsert_client($pdo, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'status' => 'active',
        'source' => 'intake',
        'marketing_opt_in' => $optin,
        'preferences' => $prefBits ? implode(' · ', $prefBits) : null,
        'notes' => 'Submitted digital intake & consent form.',
    ]);

    $st = $pdo->prepare(
        'INSERT INTO intakes
            (client_id, name, email, phone, birthdate, emergency_contact, referred_by,
             allergies, medications, conditions, other_conditions, recent_illnesses,
             recent_surgeries, pregnant, due_date, reason, had_massage_before,
             marketing_opt_in, consent_agreed, consent_signature, consent_date)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
        $clientId, $name, $email, $phone, post('birthdate'), post('emergency_contact'), post('referred_by'),
        $allergies, $medications, json_encode($conditions), post('other_conditions'), post('recent_illnesses'),
        post('recent_surgeries'), post('pregnant'), post('due_date'), post('reason'), post('had_massage_before'),
        $optin, $consentAgreed, $signature, post('consent_date') ?: date('Y-m-d'),
    ]);
} catch (Throwable $e) {
    error_log('intake-handler: ' . $e->getMessage());
    $redirect('err');
}

$redirect('ok');
