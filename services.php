<?php
require __DIR__ . '/includes/data.php';
$page_title = 'Services & Pricing';
$page_desc = 'Massage services and pricing at Huntsville Massage Professionals — relaxation, deep tissue, hot stone, Ashiatsu, prenatal, lymphatic drainage and more.';
$active = 'services';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$services = hmp_services();
$packages = hmp_packages();
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">What we offer</span>
    <h1>Services &amp; Pricing</h1>
    <p style="max-width:58ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Therapeutic and relaxation massage tailored to you. Pricing varies by session length; your therapist will help you choose.</p>
  </div>
</section>

<section>
  <div class="wrap" style="max-width:880px">
    <?php foreach ($services as $group => $rows): ?>
      <div class="price-group reveal">
        <h3><?= e($group) ?></h3>
        <?php foreach ($rows as $r): ?>
          <div class="price-row">
            <span class="price-row__name"><?= e($r['name']) ?></span>
            <span class="price-row__opts"><?= e($r['options']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="bg-sand">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow">Save with a bundle</span>
      <h2>Spa Packages</h2>
      <p class="muted">Curated experiences — perfect for treating yourself or someone special.</p>
    </div>
    <div class="grid grid--3">
      <?php foreach ($packages as $p): ?>
        <div class="card reveal">
          <h3><?= e($p['name']) ?></h3>
          <p class="therapist-card__title" style="font-size:1.05rem"><?= e($p['price']) ?></p>
          <p class="muted"><?= e($p['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section>
  <div class="wrap"><div class="cta-band reveal">
    <h2>Ready to book?</h2>
    <p>Request an appointment online or call us. We work by appointment only.</p>
    <div class="hero__actions">
      <a class="btn btn--gold" href="/book.php">Book Now</a>
      <a class="btn btn--ghost" style="color:#fff;border-color:rgba(255,255,255,.6)" href="/gift-cards.php">Buy a Gift Card</a>
    </div>
  </div></div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
