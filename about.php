<?php
require __DIR__ . '/includes/data.php';
$page_title = 'About Us';
$page_desc = 'Huntsville Massage Professionals is a collective of independent, licensed massage therapists serving Huntsville, Alabama.';
$active = 'about';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">About us</span>
    <h1>Therapeutic massage, delivered with intention.</h1>
    <p style="max-width:60ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Refresh your mind, body &amp; soul with a team that treats every client as an individual.</p>
  </div>
</section>

<section>
  <div class="wrap contact-grid">
    <div class="reveal">
      <span class="eyebrow">Our philosophy</span>
      <h2>A collective of independent professionals</h2>
      <p class="lead">Huntsville Massage Professionals is a group of the most highly trained licensed massage therapists in Alabama. Each therapist is an individual professional, working together within the group to provide individualized therapeutic treatment to our clients.</p>
      <p>We believe great bodywork is personal. Because each of our therapists runs their own practice — doing their own scheduling and taking their own payments — you get focused, one-on-one care, backed by the combined experience and specialties of the whole team.</p>
      <p>Whether you're seeking deep therapeutic relief, a calming escape, prenatal care, or specialized lymphatic work, there's a therapist here for you.</p>
    </div>
    <div class="reveal panel">
      <h3>How it works</h3>
      <hr class="divider">
      <div class="grid" style="gap:1.6rem">
        <div class="step">
          <h3 style="margin:0 0 .25rem;font-size:1.1rem">Choose your therapist</h3>
          <p class="muted" style="margin:0">Browse profiles and specialties to find your best match.</p>
        </div>
        <div class="step">
          <h3 style="margin:0 0 .25rem;font-size:1.1rem">Request your appointment</h3>
          <p class="muted" style="margin:0">Book online or call us — we work by appointment only.</p>
        </div>
        <div class="step">
          <h3 style="margin:0 0 .25rem;font-size:1.1rem">Arrive &amp; unwind</h3>
          <p class="muted" style="margin:0">Please arrive 10–15 minutes before your appointment time.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="bg-sand">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow">What we offer</span>
      <h2>Specialties across the team</h2>
    </div>
    <div class="grid grid--3">
      <?php
      $offers = [
        ['Relaxation &amp; Hot Stone', 'Melt away stress with Swedish-style relaxation, heated stones and warmed bamboo.'],
        ['Deep Tissue &amp; Trigger Point', 'Targeted, therapeutic pressure to release chronic tension and knots.'],
        ['Ashiatsu Bar Therapy', 'Gravity-assisted barefoot technique for broad, deep, soothing pressure.'],
        ['Prenatal Massage', 'Safe, nurturing care designed for the comfort of expecting mothers.'],
        ['Lymphatic Drainage', 'Gentle techniques to support circulation, recovery and facial rejuvenation.'],
        ['Reflexology &amp; Thai Foot', 'Pressure-point and foot-focused therapy for whole-body balance.'],
      ];
      foreach ($offers as [$h, $p]): ?>
        <div class="card reveal">
          <h3><?= $h ?></h3>
          <p class="muted"><?= $p ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="center" style="margin-top:2rem"><a class="btn" href="/services.php">See services &amp; pricing</a></p>
  </div>
</section>

<section>
  <div class="wrap"><div class="cta-band reveal">
    <h2>Come see us in Huntsville</h2>
    <p><?= e(BUSINESS['address']) ?></p>
    <div class="hero__actions">
      <a class="btn btn--gold" href="/contact.php">Contact Us</a>
      <a class="btn btn--ghost" style="color:#fff;border-color:rgba(255,255,255,.6)" href="/therapists.php">Meet the Team</a>
    </div>
  </div></div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
