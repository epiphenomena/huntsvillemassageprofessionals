</main>
<footer class="site-footer">
  <div class="wrap site-footer__grid">
    <div class="site-footer__brand">
      <span class="brand__name">Huntsville Massage Professionals</span>
      <p><?= e(BUSINESS['tagline']) ?></p>
      <p class="muted">A group of independent, licensed massage therapists serving Huntsville, Alabama.</p>
    </div>
    <div>
      <h4>Visit</h4>
      <p><?= e(BUSINESS['address']) ?></p>
      <p class="muted"><?= e(BUSINESS['hours']) ?></p>
    </div>
    <div>
      <h4>Contact</h4>
      <p><a href="tel:<?= e(BUSINESS['phone_e164']) ?>"><?= e(BUSINESS['phone']) ?></a></p>
      <p><a href="mailto:<?= e(BUSINESS['email']) ?>"><?= e(BUSINESS['email']) ?></a></p>
    </div>
    <div>
      <h4>Explore</h4>
      <ul class="footer-links">
        <li><a href="/therapists.php">Our Therapists</a></li>
        <li><a href="/services.php">Services &amp; Pricing</a></li>
        <li><a href="/gift-cards.php">Gift Cards</a></li>
        <li><a href="/forms.php">New Client Forms</a></li>
        <li><a href="/book.php">Book an Appointment</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap site-footer__bar">
    <p>&copy; <?= date('Y') ?> Huntsville Massage Professionals. All rights reserved.</p>
    <p class="muted">By appointment only · Each therapist schedules independently.</p>
  </div>
</footer>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
