<?php
/**
 * Export the client list to CSV for promotional outreach (e.g. importing into
 * Mailchimp). Respects the same filters as the clients list.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$pdo = db();

$q      = get('q');
$status = get('status');
$optin  = get('optin') === '1';

$sql = 'SELECT name, email, phone, status, source, marketing_opt_in, preferences, created_at FROM clients WHERE 1=1';
$args = [];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = "%$q%";
    array_push($args, $like, $like, $like);
}
if (in_array($status, ['lead', 'active', 'inactive'], true)) {
    $sql .= ' AND status = ?';
    $args[] = $status;
}
if ($optin) {
    $sql .= ' AND marketing_opt_in = 1';
}
$sql .= ' ORDER BY name COLLATE NOCASE';
$st = $pdo->prepare($sql);
$st->execute($args);

$filename = 'hmp-clients-' . ($optin ? 'optin-' : '') . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// Header row tuned for Mailchimp-style imports.
fputcsv($out, ['Name', 'Email', 'Phone', 'Status', 'Source', 'Email Opt-In', 'Preferences', 'Added']);
while ($row = $st->fetch()) {
    fputcsv($out, [
        $row['name'], $row['email'], $row['phone'], $row['status'], $row['source'],
        ((int) $row['marketing_opt_in'] === 1) ? 'Yes' : 'No', $row['preferences'], $row['created_at'],
    ]);
}
fclose($out);
