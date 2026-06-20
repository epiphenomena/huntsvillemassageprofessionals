<?php
/**
 * CRM bootstrap: HTTP Basic Auth + DB + shared layout helpers.
 *
 * Auth is enforced in PHP (not just .htaccess) so it works identically on
 * Apache, nginx and the PHP built-in server. A belt-and-suspenders .htaccess
 * also ships in this directory for Apache deployments.
 *
 * IMPORTANT: Basic Auth sends credentials in (Base64, not encrypted) plaintext.
 * Always serve /crm over HTTPS in production.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

crm_require_auth();

/** Enforce HTTP Basic Auth against config credentials. */
function crm_require_auth(): void
{
    $user = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

    // Fallback for CGI/FastCGI where credentials arrive in the Authorization header.
    if ($user === null) {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (stripos($hdr, 'basic ') === 0) {
            $decoded = base64_decode(substr($hdr, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $pass] = explode(':', $decoded, 2);
            }
        }
    }

    $ok = is_string($user) && is_string($pass)
        && hash_equals(CRM_USER, $user)
        && password_verify($pass, CRM_PASSWORD_HASH);

    if (!$ok) {
        header('WWW-Authenticate: Basic realm="Huntsville Massage CRM"');
        http_response_code(401);
        echo '<!doctype html><meta charset=utf-8><title>Authentication required</title>'
            . '<body style="font-family:sans-serif;padding:3rem;text-align:center">'
            . '<h1>Authentication required</h1><p>This area is for staff only.</p></body>';
        exit;
    }
}

/** True when the current request is an HTMX request (return a fragment, not a full page). */
function is_htmx(): bool
{
    return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
}

/** Render the CRM page chrome (header/nav). Skipped automatically for HTMX requests. */
function crm_header(string $title, string $active = ''): void
{
    $nav = [
        'dashboard' => ['Dashboard', '/crm/'],
        'clients'   => ['Clients', '/crm/clients.php'],
        'calendar'  => ['Calendar', '/crm/calendar.php'],
        'leads'     => ['Follow-ups', '/crm/leads.php'],
        'giftcards' => ['Gift Cards', '/crm/giftcards.php'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · HMP CRM</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/crm/assets/crm.css">
<script src="/crm/assets/htmx.min.js" defer></script>
</head>
<body>
<header class="crm-top">
  <a class="crm-brand" href="/crm/">
    <svg viewBox="0 0 32 32" width="26" height="26" style="color:var(--green-600)"><use href="/assets/img/logo.svg#leaf"></use></svg>
    <span>HMP <strong>CRM</strong></span>
  </a>
  <nav class="crm-nav">
    <?php foreach ($nav as $key => [$label, $href]): ?>
      <a href="<?= e($href) ?>"<?= $active === $key ? ' class="active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="crm-site-link" href="/" target="_blank" rel="noopener">View site ↗</a>
</header>
<main class="crm-main">
    <?php
}

function crm_footer(): void
{
    echo '</main></body></html>';
}

/** Status pill markup. */
function status_pill(string $status): string
{
    $cls = match ($status) {
        'active'   => 'pill--active',
        'lead'     => 'pill--lead',
        default    => 'pill--inactive',
    };
    return '<span class="pill ' . $cls . '">' . e(ucfirst($status)) . '</span>';
}
