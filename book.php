<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$pdo = db();
$therapists = hmp_therapists();
$services = hmp_service_options();
$filterSlug = get('t');
$filterTherapist = $filterSlug ? hmp_therapist($filterSlug) : null;
$status = '';

// ---- Handle a booking request ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = '/book.php' . ($filterSlug ? '?t=' . urlencode($filterSlug) : '');
    $sep = $filterSlug ? '&' : '?';
    $go = static function (string $s) use ($back, $sep): void {
        header('Location: ' . $back . $sep . 'booked=' . $s);
        exit;
    };
    if (post('website') !== '') { $go('1'); }            // honeypot
    if (!csrf_check($_POST['csrf'] ?? null)) { $go('err'); }
    $slotId = (int) post('slot_id');
    $name = post('name');
    $email = post('email');
    $phone = post('phone');
    $service = post('service');
    $valid = $slotId && $name !== '' && ($email !== '' || $phone !== '') && (!$email || is_email($email));

    if ($valid) {
        try {
            $pdo->beginTransaction();
            // Lock the slot row by re-checking it is still open.
            $st = $pdo->prepare("SELECT * FROM slots WHERE id=? AND status='open'");
            $st->execute([$slotId]);
            $slot = $st->fetch();
            if ($slot) {
                $pdo->prepare("UPDATE slots SET status='booked' WHERE id=?")->execute([$slotId]);
                $clientId = upsert_client($pdo, [
                    'name' => $name, 'email' => $email ?: null, 'phone' => $phone ?: null,
                    'status' => 'lead', 'source' => 'booking',
                    'marketing_opt_in' => isset($_POST['marketing_opt_in']) ? 1 : 0,
                    'notes' => 'Self-booked online — confirm the appointment.',
                ]);
                $pdo->prepare('INSERT INTO bookings (slot_id, client_id, service, notes) VALUES (?,?,?,?)')
                    ->execute([$slotId, $clientId, $service ?: null, post('notes') ?: null]);
                // Staff follow-up: confirm the requested time.
                $pdo->prepare('INSERT INTO followups (client_id, kind, message, status, due_date) VALUES (?,?,?,?,date("now"))')
                    ->execute([$clientId, 'callback', 'Confirm online booking request: ' . fmt_date($slot['date']) . ' ' . $slot['start_time'], 'open']);
                $pdo->commit();
                $go('1');
            }
            $pdo->commit();
            $go('taken');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('book: ' . $e->getMessage());
            $go('err');
        }
    }
    $go('err');
}

// ---- Gather open slots (next 21 days) ----
$sql = "SELECT s.*, t.name AS therapist_name, t.slug AS therapist_slug
        FROM slots s JOIN therapists t ON t.id = s.therapist_id
        WHERE s.status='open' AND s.date >= date('now') AND s.date <= date('now','+21 days')";
$args = [];
if ($filterTherapist) {
    // Match by therapist id via slug seeded into the DB.
    $sql .= ' AND t.slug = ?';
    $args[] = $filterSlug;
}
$sql .= ' ORDER BY s.date, s.start_time, t.name';
$st = $pdo->prepare($sql);
$st->execute($args);
$slots = $st->fetchAll();

// Group by date.
$byDate = [];
foreach ($slots as $s) {
    $byDate[$s['date']][] = $s;
}

$booked = get('booked');
$page_title = 'Book an Appointment';
$page_desc = 'Request a massage appointment online with Huntsville Massage Professionals.';
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>

<section class="bg-green" style="padding-block:clamp(3rem,7vw,5rem)">
  <div class="wrap center">
    <span class="eyebrow">Appointments</span>
    <h1>Request a Booking<?= $filterTherapist ? ' with ' . e(explode(' ', $filterTherapist['name'])[0]) : '' ?></h1>
    <p style="max-width:58ch;margin:1rem auto 0;color:rgba(255,255,255,.85)">Pick an available time below and tell us how to reach you. Your therapist confirms every request directly — this holds your preferred slot.</p>
  </div>
</section>

<section>
  <div class="wrap" style="max-width:920px">
    <?php if ($booked === '1'): ?>
      <div class="notice notice--ok"><strong>Request received!</strong> Your preferred time is on hold. Your therapist will reach out to confirm. You can also call us at <a href="tel:<?= e(BUSINESS['phone_e164']) ?>"><?= e(BUSINESS['phone']) ?></a>.</div>
    <?php elseif ($booked === 'taken'): ?>
      <div class="notice notice--err">Sorry — that time was just taken. Please choose another available slot.</div>
    <?php elseif ($booked === 'err'): ?>
      <div class="notice notice--err">Please choose a time and give us a name plus an email or phone number.</div>
    <?php endif; ?>

    <div class="toolbar-row" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.5rem">
      <a class="btn btn--ghost btn--small<?= $filterSlug ? '' : ' btn--active' ?>" href="/book.php" style="<?= $filterSlug ? '' : 'background:var(--green-700);color:#fff' ?>">All therapists</a>
      <?php foreach ($therapists as $t): ?>
        <a class="btn btn--ghost btn--small" href="/book.php?t=<?= e($t['slug']) ?>" style="<?= $filterSlug === $t['slug'] ? 'background:var(--green-700);color:#fff' : '' ?>"><?= e(explode(' ', $t['name'])[0]) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$byDate): ?>
      <div class="panel center">
        <h3>No open times in the next few weeks</h3>
        <p class="muted">Please call us at <a href="tel:<?= e(BUSINESS['phone_e164']) ?>"><?= e(BUSINESS['phone']) ?></a> and we'll find a time that works.</p>
      </div>
    <?php else: ?>
      <form class="panel" method="post" action="/book.php<?= $filterSlug ? '?t=' . e($filterSlug) : '' ?>">
        <?= csrf_field() ?>
        <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <h2 style="margin-top:0">1. Choose a time</h2>
        <?php foreach ($byDate as $date => $daySlots): ?>
          <div class="slot-day">
            <h3 class="slot-day__label"><?= e(date('l, F j', strtotime($date))) ?></h3>
            <div class="slot-grid">
              <?php foreach ($daySlots as $s): ?>
                <label class="slot">
                  <input type="radio" name="slot_id" value="<?= (int) $s['id'] ?>" required>
                  <span class="slot__time"><?= e(date('g:i A', strtotime($s['start_time']))) ?></span>
                  <span class="slot__who"><?= e($s['therapist_name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <hr class="divider">
        <h2>2. Your details</h2>
        <div class="row-2">
          <div class="field"><label for="b-name">Name <span class="hint">(required)</span></label><input type="text" id="b-name" name="name" required></div>
          <div class="field"><label for="b-service">Service</label>
            <select id="b-service" name="service"><?php foreach ($services as $svc): ?><option><?= e($svc) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row-2">
          <div class="field"><label for="b-email">Email</label><input type="email" id="b-email" name="email"></div>
          <div class="field"><label for="b-phone">Phone</label><input type="tel" id="b-phone" name="phone"></div>
        </div>
        <p class="hint" style="margin:-.4rem 0 0">Please give us at least one way to reach you.</p>
        <div class="field"><label for="b-notes">Anything we should know? <span class="hint">(optional)</span></label><textarea id="b-notes" name="notes" placeholder="Focus areas, pressure preference, first visit, etc."></textarea></div>
        <label class="checkbox"><input type="checkbox" name="marketing_opt_in" value="1"><span>Send me occasional offers &amp; news by email.</span></label>
        <button type="submit" class="btn btn--gold btn--block">Request This Appointment</button>
        <p class="hint center">Requesting holds the slot; your therapist will confirm and arrange payment directly.</p>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
