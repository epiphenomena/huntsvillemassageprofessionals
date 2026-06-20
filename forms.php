<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';
$page_title = 'New Client Forms';
$page_desc = 'Complete your massage intake and consent form online before your first visit.';
$active = 'forms';
$sent = get('sent');
$conditions = hmp_conditions();
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">Before your visit</span>
    <h1>Client Intake &amp; Consent</h1>
    <p style="max-width:60ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Save time at your appointment — complete your intake and consent form online. Your information is kept private and confidential.</p>
  </div>
</section>

<section>
  <div class="wrap" style="max-width:820px">
    <?php if ($sent === 'ok'): ?>
      <div class="notice notice--ok"><strong>Thank you!</strong> Your intake and consent form has been submitted. We look forward to seeing you.</div>
    <?php elseif ($sent === 'err'): ?>
      <div class="notice notice--err">Please complete the required fields (name, a way to contact you, and the consent agreement) and try again.</div>
    <?php endif; ?>

    <div class="panel reveal">
      <p class="muted">Fields marked <span class="hint">(required)</span> must be completed. Everything else helps your therapist give you the safest, most effective session.</p>

      <form class="form" action="/intake-handler.php" method="post">
        <?= csrf_field() ?>
        <div class="honeypot" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <h3>Your details</h3>
        <div class="row-2">
          <div class="field"><label for="name">Full name <span class="hint">(required)</span></label><input type="text" id="name" name="name" required></div>
          <div class="field"><label for="birthdate">Date of birth</label><input type="date" id="birthdate" name="birthdate"></div>
        </div>
        <div class="row-2">
          <div class="field"><label for="email">Email <span class="hint">(required)</span></label><input type="email" id="email" name="email" required></div>
          <div class="field"><label for="phone">Cell phone <span class="hint">(required)</span></label><input type="tel" id="phone" name="phone" required></div>
        </div>
        <div class="row-2">
          <div class="field"><label for="emergency_contact">Emergency contact &amp; phone</label><input type="text" id="emergency_contact" name="emergency_contact" placeholder="Name — 256-555-0000"></div>
          <div class="field"><label for="referred_by">How did you hear about us?</label><input type="text" id="referred_by" name="referred_by"></div>
        </div>

        <hr class="divider">
        <h3>Health history</h3>
        <div class="row-2">
          <div class="field"><label for="allergies">Allergies</label><input type="text" id="allergies" name="allergies" placeholder="e.g. nut oils, lavender, latex"></div>
          <div class="field"><label for="medications">Current medications</label><input type="text" id="medications" name="medications"></div>
        </div>

        <div class="field">
          <span class="form-label">Please check anything you have now or have had in the past:</span>
          <div class="checkbox-grid" style="margin-top:.5rem">
            <?php foreach ($conditions as $cond): ?>
              <label class="checkbox"><input type="checkbox" name="conditions[]" value="<?= e($cond) ?>"><span><?= e($cond) ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field"><label for="other_conditions">Other notable medical conditions</label><input type="text" id="other_conditions" name="other_conditions"></div>
        <div class="row-2">
          <div class="field"><label for="recent_illnesses">Recent illnesses / injuries / accidents</label><input type="text" id="recent_illnesses" name="recent_illnesses"></div>
          <div class="field"><label for="recent_surgeries">Recent surgeries / broken bones</label><input type="text" id="recent_surgeries" name="recent_surgeries"></div>
        </div>
        <div class="row-2">
          <div class="field">
            <span class="form-label">Are you currently pregnant?</span>
            <div style="display:flex;gap:1.5rem;margin-top:.4rem">
              <label class="checkbox"><input type="radio" name="pregnant" value="No" checked><span>No</span></label>
              <label class="checkbox"><input type="radio" name="pregnant" value="Yes"><span>Yes</span></label>
            </div>
          </div>
          <div class="field"><label for="due_date">If yes, due date</label><input type="date" id="due_date" name="due_date"></div>
        </div>
        <div class="field"><label for="reason">Reason for today's visit</label><input type="text" id="reason" name="reason" placeholder="e.g. lower back tension, relaxation, prenatal"></div>
        <div class="field">
          <span class="form-label">Have you had a professional massage before?</span>
          <div style="display:flex;gap:1.5rem;margin-top:.4rem">
            <label class="checkbox"><input type="radio" name="had_massage_before" value="Yes"><span>Yes</span></label>
            <label class="checkbox"><input type="radio" name="had_massage_before" value="No"><span>No</span></label>
          </div>
        </div>

        <hr class="divider">
        <h3>Informed consent &amp; service agreement</h3>
        <div class="panel" style="background:var(--green-50);border-color:var(--green-100);font-size:.93rem;line-height:1.75">
          <p>Massage therapy is provided for relaxation and relief from muscular tension and stress. Your therapist does not diagnose illness, perform skeletal adjustments, or prescribe medication, and massage is not a substitute for medical care.</p>
          <p>I confirm that I have shared all known medical conditions and will keep my therapist updated on any changes. If I feel any pain or discomfort during a session, I will tell my therapist right away so pressure and technique can be adjusted to my comfort.</p>
          <p>I understand and agree that:</p>
          <ul style="padding-left:1.1rem">
            <li>The relationship between client and therapist is professional, and all information I provide is kept confidential.</li>
            <li>I will be properly draped at all times for my comfort, security and warmth.</li>
            <li>Massage is solely for therapeutic purposes; any inappropriate or suggestive remarks or behavior will end the session immediately, and the session will be charged in full.</li>
            <li>My therapist also has the right to a safe, respectful environment, free from unwanted, harmful or offensive contact or behavior.</li>
            <li>I may ask that any technique be modified, stopped, or not performed at any time.</li>
          </ul>
          <p style="margin-bottom:0">By submitting this form, I confirm the information above is accurate to the best of my knowledge, and I freely give my consent to receive massage therapy, including future sessions.</p>
        </div>
        <p class="hint">This consent and liability language is provided as a starting point and should be reviewed by a licensed professional before use.</p>

        <label class="checkbox">
          <input type="checkbox" name="consent_agreed" value="1" required>
          <span><strong>I have read and agree</strong> to the informed consent &amp; service agreement above. <span class="hint">(required)</span></span>
        </label>
        <div class="row-2">
          <div class="field"><label for="consent_signature">Type your full name to sign <span class="hint">(required)</span></label><input type="text" id="consent_signature" name="consent_signature" required></div>
          <div class="field"><label for="consent_date">Date</label><input type="date" id="consent_date" name="consent_date" value="<?= date('Y-m-d') ?>"></div>
        </div>

        <label class="checkbox">
          <input type="checkbox" name="marketing_opt_in" value="1">
          <span>I'd like to receive occasional news, wellness tips and special offers by email.</span>
        </label>

        <button type="submit" class="btn btn--block">Submit Intake &amp; Consent</button>
      </form>
    </div>

    <div class="center" style="margin-top:1.6rem">
      <p class="muted">Prefer a printable copy? Download the Word versions:</p>
      <div class="hero__actions" style="justify-content:center">
        <a class="btn btn--ghost btn--small" href="/forms/Client-Intake-Form.docx" download>Intake Form (.docx)</a>
        <a class="btn btn--ghost btn--small" href="/forms/Consent-and-Service-Agreement.docx" download>Consent Form (.docx)</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
