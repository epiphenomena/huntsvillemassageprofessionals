<?php
require __DIR__ . '/includes/data.php';
$page_title = 'Our Therapists';
$page_desc = 'Meet the licensed massage therapists of Huntsville Massage Professionals.';
$active = 'therapists';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$therapists = hmp_therapists();
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">Meet the team</span>
    <h1>Our Therapists</h1>
    <p style="max-width:58ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Each therapist is an independent, licensed professional. Explore their specialties and book the one that's right for you.</p>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="grid grid--3">
      <?php foreach ($therapists as $t): ?>
        <article class="therapist-card reveal">
          <img class="therapist-card__photo" src="/assets/img/therapists/<?= e($t['slug']) ?>.svg" alt="Portrait of <?= e($t['name']) ?>" loading="lazy">
          <div class="therapist-card__body">
            <h2 class="therapist-card__name"><?= e($t['name']) ?></h2>
            <p class="therapist-card__title"><?= e($t['title']) ?> · <?= e($t['license']) ?></p>
            <div class="tags">
              <?php foreach (array_slice($t['specialties'], 0, 4) as $s): ?>
                <span class="tag"><?= e($s) ?></span>
              <?php endforeach; ?>
            </div>
            <a class="btn btn--ghost btn--small" href="/therapist.php?t=<?= e($t['slug']) ?>">View profile &amp; book</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
