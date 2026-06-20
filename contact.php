<?php
require __DIR__ . '/includes/helpers.php';
$page_title = 'Contact Us';
$page_desc = 'Contact Huntsville Massage Professionals — call, email, or send us a message to book your appointment.';
$active = 'contact';
$sent = get('sent');
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">Get in touch</span>
    <h1>Contact Us</h1>
    <p style="max-width:54ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Questions, bookings, or gift cards — we'd love to hear from you.</p>
  </div>
</section>

<section id="contact">
  <div class="wrap contact-grid">
    <div class="reveal">
      <span class="eyebrow">Reach us directly</span>
      <h2>We're here to help</h2>
      <ul class="contact-info">
        <li>
          <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
          <div><strong>Call</strong><br><a href="tel:<?= e(BUSINESS['phone_e164']) ?>"><?= e(BUSINESS['phone']) ?></a></div>
        </li>
        <li>
          <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span>
          <div><strong>Email</strong><br><a href="mailto:<?= e(BUSINESS['email']) ?>"><?= e(BUSINESS['email']) ?></a></div>
        </li>
        <li>
          <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
          <div><strong>Visit</strong><br><?= e(BUSINESS['address']) ?></div>
        </li>
        <li>
          <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
          <div><strong>Hours</strong><br><?= e(BUSINESS['hours']) ?></div>
        </li>
      </ul>
      <div class="panel" style="margin-top:1rem">
        <h3 style="margin-bottom:.4rem">A note on scheduling</h3>
        <p class="muted" style="margin:0">Each of our therapists is independent — they do their own scheduling and take their own payments. We work by appointment only; please arrive 10–15 minutes early.</p>
      </div>
    </div>

    <div class="reveal panel">
      <h2 style="margin-top:0">Send us a message</h2>
      <?php if ($sent === 'ok'): ?>
        <div class="notice notice--ok"><strong>Thank you!</strong> Your message has been received — we'll be in touch shortly.</div>
      <?php elseif ($sent === 'err'): ?>
        <div class="notice notice--err">Sorry, something went wrong. Please check your details (we need a name and either an email or phone) and try again, or call us.</div>
      <?php endif; ?>

      <form class="form" action="/contact-handler.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="/contact.php">
        <!-- Honeypot: hidden from humans -->
        <div class="honeypot" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="field">
          <label for="name">Name <span class="hint">(required)</span></label>
          <input type="text" id="name" name="name" required autocomplete="name">
        </div>
        <div class="row-2">
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email">
          </div>
          <div class="field">
            <label for="phone">Phone <span class="hint">(optional)</span></label>
            <input type="tel" id="phone" name="phone" autocomplete="tel">
          </div>
        </div>
        <p class="hint" style="margin:-.4rem 0 0">Please give us at least one way to reach you — email or phone.</p>
        <div class="field">
          <label for="message">Message</label>
          <textarea id="message" name="message" placeholder="<?= e(CONTACT_DEFAULT_MESSAGE) ?>"></textarea>
          <span class="hint">Leave blank and we'll assume: "<?= e(CONTACT_DEFAULT_MESSAGE) ?>"</span>
        </div>
        <label class="checkbox">
          <input type="checkbox" name="marketing_opt_in" value="1">
          <span>Yes, send me occasional news, offers and seasonal specials by email.</span>
        </label>
        <button type="submit" class="btn btn--block">Send Message</button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
