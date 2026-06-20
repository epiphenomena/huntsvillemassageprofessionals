<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';

$slug = get('t');
$t = $slug ? hmp_therapist($slug) : null;

if (!$t) {
    http_response_code(404);
    $page_title = 'Therapist not found';
    include __DIR__ . '/partials/head.php';
    include __DIR__ . '/partials/header.php';
    echo '<section class="wrap center"><h1>Therapist not found</h1><p class="muted">We couldn\'t find that profile.</p><p><a class="btn" href="/therapists.php">Back to all therapists</a></p></section>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$page_title = $t['name'] . ' — ' . $t['title'];
$page_desc = $t['name'] . ', ' . $t['license'] . '. ' . $t['bio'];
$active = 'therapists';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap">
    <p style="margin-bottom:1.5rem"><a href="/therapists.php">&larr; All therapists</a></p>
    <div class="contact-grid">
      <div class="reveal">
        <img class="panel" style="padding:0;overflow:hidden;width:100%;max-width:420px" src="/assets/img/therapists/<?= e($t['slug']) ?>.svg" alt="Portrait of <?= e($t['name']) ?>">
      </div>
      <div class="reveal">
        <span class="eyebrow"><?= e($t['license']) ?></span>
        <h1 style="margin-bottom:.15rem"><?= e($t['name']) ?></h1>
        <p class="therapist-card__title" style="font-size:1rem"><?= e($t['title']) ?></p>
        <p class="lead"><?= e($t['bio']) ?></p>

        <h3 style="margin-top:1.5rem">Specialties</h3>
        <div class="tags">
          <?php foreach ($t['specialties'] as $s): ?>
            <span class="tag"><?= e($s) ?></span>
          <?php endforeach; ?>
        </div>

        <div class="hero__actions" style="justify-content:flex-start;margin-top:2rem">
          <a class="btn btn--gold" href="/book.php?t=<?= e($t['slug']) ?>">Book with <?= e(explode(' ', $t['name'])[0]) ?></a>
          <a class="btn btn--ghost" href="tel:<?= e(BUSINESS['phone_e164']) ?>">Call <?= e(BUSINESS['phone']) ?></a>
        </div>
        <p class="muted" style="margin-top:1rem;font-size:.9rem">Each therapist schedules independently and takes their own payments. Online requests are confirmed directly with your therapist.</p>
      </div>
    </div>
  </div>
</section>

<section class="bg-sand">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow">Booking calendar</span>
      <h2>Availability</h2>
      <p class="muted">A live booking calendar can be embedded here per therapist. For now, request a time and your therapist will confirm.</p>
    </div>
    <div class="center reveal">
      <a class="btn" href="/book.php?t=<?= e($t['slug']) ?>">View <?= e(explode(' ', $t['name'])[0]) ?>'s available times</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
