<?php
/**
 * Huntsville Massage Professionals — central configuration.
 *
 * Adjust these values per deployment. Nothing secret should be committed for a
 * production site — set the CRM password hash via environment or edit below.
 */

declare(strict_types=1);

// ---- Business details (single source of truth, used site-wide) -------------
const BUSINESS = [
    'name'     => 'Huntsville Massage Professionals',
    'tagline'  => 'Refresh your mind, body & soul.',
    'phone'    => '256-738-3469',
    'phone_e164' => '+12567383469',
    'email'    => 'huntsvillemassageprofessional@gmail.com',
    'address'  => '910 Merchants Walk, Huntsville, Alabama 35801',
    'hours'    => 'By appointment only · Please arrive 10–15 minutes early',
    'url'      => 'https://www.huntsvillemassageprofessionals.com',
];

// ---- Paths -----------------------------------------------------------------
// The SQLite database is kept OUTSIDE the public document root where possible.
// By default it lives in ./storage which is locked down via .htaccess; for a
// hardened deployment set HMP_DB_PATH to a directory above the webroot.
define('HMP_ROOT', __DIR__);
define('HMP_DB_PATH', getenv('HMP_DB_PATH') ?: HMP_ROOT . '/storage/hmp.sqlite');

// ---- CRM authentication ----------------------------------------------------
// HTTP Basic Auth credentials for /crm. Replace the hash before going live:
//   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT), PHP_EOL;"
// Default login is  admin / changeme  (CHANGE THIS).
const CRM_USER = 'admin';
const CRM_PASSWORD_HASH = '$2y$12$daDaH02OKvl9Wb.B87BnKuWUT9pI5axvpxXOAPIod/Am6yyoo5..e'; // "changeme"

// ---- Contact form ----------------------------------------------------------
// Where contact-form notifications are emailed. Leave blank to disable
// notifications (submissions are still stored in the CRM regardless).
const CONTACT_NOTIFY_EMAIL = BUSINESS['email'];
const CONTACT_DEFAULT_MESSAGE = "I'm interested in getting more information or booking a massage.";

// ---- Outgoing email (SMTP) -------------------------------------------------
// Leave SMTP_HOST empty to use PHP mail(). For authenticated SMTP on DreamHost:
//   Host:  smtp.dreamhost.com
//   Port:  465 (implicit TLS)  or  587 (STARTTLS)
//   User:  the full mailbox login, e.g. smtp-auth@huntsvillemassageprofessionals.com
//   Pass:  that mailbox's password — set HMP_SMTP_PASS in the environment, or
//          paste it below (config.php is denied web access via .htaccess).
// Each value may instead come from the environment variable shown, keeping the
// password out of source control.
define('SMTP_HOST', getenv('HMP_SMTP_HOST') ?: '');                 // e.g. 'smtp.dreamhost.com'
define('SMTP_PORT', (int) (getenv('HMP_SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('HMP_SMTP_USER') ?: 'smtp-auth@huntsvillemassageprofessionals.com');
define('SMTP_PASS', getenv('HMP_SMTP_PASS') ?: '');                 // <-- set this
define('MAIL_FROM', getenv('HMP_MAIL_FROM') ?: SMTP_USER);          // From / envelope address
define('MAIL_FROM_NAME', BUSINESS['name']);
