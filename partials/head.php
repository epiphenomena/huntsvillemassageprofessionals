<?php
/**
 * Opens the HTML document + sets up <head>. Pages may define before including:
 *   $page_title, $page_desc, $active (nav key), $body_class
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

// Start the session before any output so csrf_field() can run inside the page.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$page_title = $page_title ?? BUSINESS['name'];
$page_desc  = $page_desc ?? 'Licensed massage therapy in Huntsville, Alabama. ' . BUSINESS['tagline'];
$active     = $active ?? '';
$body_class = $body_class ?? '';
$full_title = $page_title === BUSINESS['name']
    ? BUSINESS['name'] . ' · ' . BUSINESS['tagline']
    : $page_title . ' · ' . BUSINESS['name'];
?>
<!doctype html>
<html lang="en">
<script>document.documentElement.classList.add('js');</script>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($full_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<meta property="og:title" content="<?= e($full_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= e($body_class) ?>">
