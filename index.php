<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/data.php';
$page_title = BUSINESS['name'];
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$therapists = hmp_therapists();
?>

<section class="hero">
  <svg class="hero__leaf hero__leaf--1" viewBox="0 0 32 32"><use href="/assets/img/logo.svg#leaf"></use></svg>
  <svg class="hero__leaf hero__leaf--2" viewBox="0 0 32 32"><use href="/assets/img/logo.svg#leaf"></use></svg>
  <div class="wrap hero__inner">
    <span class="eyebrow">Huntsville, Alabama</span>
    <h1>Refresh your mind, body &amp; soul.</h1>
    <p class="lead">A collective of Alabama's most highly trained licensed massage therapists — each an independent professional, working together to give you individualized, therapeutic care.</p>
    <div class="hero__actions">
      <a class="btn btn--gold" href="/book.php">Book an Appointment</a>
      <a class="btn btn--ghost" href="/services.php" style="color:#fff;border-color:rgba(255,255,255,.6)">View Services</a>
    </div>
  </div>
</section>

<!-- Trust / features -->
<section>
  <div class="wrap">
    <div class="grid grid--4">
      <?php
      $features = [
        ['Licensed &amp; experienced', 'Every therapist is licensed in Alabama, with specialties from Ashiatsu to lymphatic drainage.'],
        ['Individualized care', 'Independent professionals who tailor every session to your body and goals.'],
        ['By appointment only', 'Calm, unhurried sessions — arrive 10–15 minutes early and simply unwind.'],
        ['A treatment for everyone', 'Relaxation, deep tissue, prenatal, hot stone, reflexology &amp; more.'],
      ];
      foreach ($features as [$h, $p]): ?>
        <div class="card card--feature reveal">
          <div class="card__icon"><svg width="24" height="24" viewBox="0 0 32 32"><use href="/assets/img/logo.svg#leaf"></use></svg></div>
          <h3><?= $h ?></h3>
          <p class="muted"><?= $p ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- About teaser -->
<section class="bg-sand">
  <div class="wrap contact-grid">
    <div class="reveal">
      <span class="eyebrow">Who we are</span>
      <h2>A group of independent professionals, united by care.</h2>
      <p class="lead">Huntsville Massage Professionals is a group of the most highly trained licensed massage therapists in Alabama. Each therapist is an individual professional, working together within the group to provide individualized therapeutic treatment to our clients.</p>
      <p>Because each therapist schedules and runs their own practice, you get the personal attention of a solo practitioner with the depth and variety of an entire team.</p>
      <a class="btn" href="/about.php">More about us</a>
    </div>
    <div class="reveal panel">
      <h3>What our clients come for</h3>
      <hr class="divider">
      <div class="price-row"><span class="price-row__name">Relaxation &amp; Hot Stone</span><span class="price-row__opts">from $60</span></div>
      <div class="price-row"><span class="price-row__name">Deep Tissue &amp; Trigger Point</span><span class="price-row__opts">from $60</span></div>
      <div class="price-row"><span class="price-row__name">Ashiatsu Oriental Bar Therapy</span><span class="price-row__opts">from $100</span></div>
      <div class="price-row"><span class="price-row__name">Lymphatic Drainage &amp; Facial</span><span class="price-row__opts">from $75</span></div>
      <div class="price-row"><span class="price-row__name">Prenatal &amp; Reflexology</span><span class="price-row__opts">from $65</span></div>
      <p style="margin-top:1.2rem"><a href="/services.php">See the full service menu &rarr;</a></p>
    </div>
  </div>
</section>

<!-- Therapists teaser -->
<section>
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow">Meet the team</span>
      <h2>Find the right therapist for you</h2>
      <p class="muted">Each of our licensed therapists brings their own specialties and style. Explore their profiles and book directly.</p>
    </div>
    <div class="grid grid--4">
      <?php foreach (array_slice($therapists, 0, 4) as $t): ?>
        <a class="therapist-card reveal" href="/therapist.php?t=<?= e($t['slug']) ?>">
          <img class="therapist-card__photo" src="/assets/img/therapists/<?= e($t['slug']) ?>.svg" alt="<?= e($t['name']) ?>" loading="lazy">
          <div class="therapist-card__body">
            <h3 class="therapist-card__name"><?= e($t['name']) ?></h3>
            <p class="therapist-card__title"><?= e($t['title']) ?></p>
            <span class="btn btn--ghost btn--small">View profile</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <p class="center" style="margin-top:2rem"><a class="btn" href="/therapists.php">Meet all <?= count($therapists) ?> therapists</a></p>
  </div>
</section>

<!-- Gift cards band -->
<section>
  <div class="wrap">
    <div class="cta-band reveal">
      <span class="eyebrow" style="color:var(--gold)">The perfect gift</span>
      <h2>Give the gift of relaxation</h2>
      <p>Gift certificates are available for any service or package — a thoughtful gift for birthdays, holidays, or just because.</p>
      <a class="btn btn--gold" href="/gift-cards.php">Gift Cards &amp; Certificates</a>
    </div>
  </div>
</section>

<!-- Final CTA / contact -->
<section class="bg-green">
  <div class="wrap center reveal">
    <span class="eyebrow">Ready when you are</span>
    <h2>Book your next session</h2>
    <p style="max-width:52ch;margin:0 auto 1.6rem;color:rgba(255,255,255,.85)">We work by appointment only. Reach out by phone, send us a message, or request a time online.</p>
    <div class="hero__actions">
      <a class="btn btn--gold" href="/book.php">Request a Booking</a>
      <a class="btn btn--ghost" style="color:#fff;border-color:rgba(255,255,255,.6)" href="tel:<?= e(BUSINESS['phone_e164']) ?>">Call <?= e(BUSINESS['phone']) ?></a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
