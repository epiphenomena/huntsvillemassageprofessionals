<?php
require __DIR__ . '/includes/helpers.php';
$page_title = 'Gift Cards';
$page_desc = 'Give the gift of relaxation. Gift certificates for any service or package at Huntsville Massage Professionals.';
$active = 'gift-cards';
$sent = get('sent');
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">The perfect gift</span>
    <h1>Gift Cards &amp; Certificates</h1>
    <p style="max-width:56ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Give someone you love the gift of relaxation — for birthdays, holidays, or just because.</p>
  </div>
</section>

<section>
  <div class="wrap contact-grid">
    <div class="reveal">
      <span class="eyebrow">How it works</span>
      <h2>Thoughtful, easy &amp; always appreciated</h2>
      <p class="lead">Gift certificates can be applied to any service or spa package. Choose a dollar amount or a specific experience — we'll take care of the rest.</p>

      <div class="grid" style="gap:1.4rem;margin:1.5rem 0">
        <div class="step"><h3 style="font-size:1.1rem;margin:0 0 .2rem">Request below or call us</h3><p class="muted" style="margin:0">Tell us the amount or package and who it's for.</p></div>
        <div class="step"><h3 style="font-size:1.1rem;margin:0 0 .2rem">We confirm &amp; arrange payment</h3><p class="muted" style="margin:0">A team member follows up to complete your purchase.</p></div>
        <div class="step"><h3 style="font-size:1.1rem;margin:0 0 .2rem">Give the gift of wellness</h3><p class="muted" style="margin:0">Your recipient books whenever they're ready.</p></div>
      </div>

      <div class="panel">
        <h3 style="margin-top:0">Good to know</h3>
        <ul class="muted" style="margin:0;padding-left:1.1rem;line-height:1.9">
          <li>Gift certificates are non-transferable and not redeemable for cash.</li>
          <li>Please give 24 hours' notice to reschedule or cancel an appointment.</li>
          <li>Same-day cancellations reduce certificate value by half.</li>
          <li>No-shows result in forfeiture of the service.</li>
        </ul>
        <p style="margin:1rem 0 0">Prefer to buy by phone? Call <a href="tel:<?= e(BUSINESS['phone_e164']) ?>"><?= e(BUSINESS['phone']) ?></a>.</p>
      </div>
    </div>

    <div class="reveal panel">
      <h2 style="margin-top:0">Request a Gift Card</h2>
      <?php if ($sent === 'ok'): ?>
        <div class="notice notice--ok"><strong>Thank you!</strong> We've received your gift card request and will reach out to complete your purchase.</div>
      <?php elseif ($sent === 'err'): ?>
        <div class="notice notice--err">Sorry, something went wrong. Please check your details or call us at <?= e(BUSINESS['phone']) ?>.</div>
      <?php endif; ?>

      <form class="form" action="/contact-handler.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="/gift-cards.php">
        <input type="hidden" name="source" value="gift_card">
        <input type="hidden" name="topic" value="Gift card purchase">
        <div class="honeypot" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="field">
          <label for="name">Your name <span class="hint">(required)</span></label>
          <input type="text" id="name" name="name" required autocomplete="name">
        </div>
        <div class="row-2">
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel">
          </div>
        </div>
        <div class="field">
          <label for="message">What would you like?</label>
          <textarea id="message" name="message" placeholder="e.g. A $100 gift certificate for my mom, Jane — or the 'Just For Her' package."></textarea>
        </div>
        <label class="checkbox">
          <input type="checkbox" name="marketing_opt_in" value="1">
          <span>Keep me posted on seasonal gift specials by email.</span>
        </label>
        <button type="submit" class="btn btn--gold btn--block">Request Gift Card</button>
        <p class="hint center">We'll contact you to confirm details and arrange payment.</p>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
