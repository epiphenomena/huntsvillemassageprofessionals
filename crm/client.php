<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
$pdo = db();

$id = (int) (get('id') ?: ($_POST['id'] ?? 0));
$client = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $st->execute([$id]);
    $client = $st->fetch();
}
if (!$client) {
    http_response_code(404);
    crm_header('Not found', 'clients');
    echo '<p><a class="back-link" href="/crm/clients.php">&larr; Clients</a></p><div class="card empty">Client not found.</div>';
    crm_footer();
    exit;
}

// ---- Fragment renderers (shared by full page + HTMX) ----
function render_massage_rows(PDO $pdo, int $id): void
{
    $st = $pdo->prepare('SELECT * FROM massages WHERE client_id=? ORDER BY date DESC, id DESC');
    $st->execute([$id]);
    $rows = $st->fetchAll();
    if (!$rows) { echo '<tr><td colspan="5" class="empty">No sessions logged yet.</td></tr>'; return; }
    foreach ($rows as $m) {
        echo '<tr><td>' . fmt_date($m['date']) . '</td>';
        echo '<td>' . e($m['service']) . '<span class="sub">' . e($m['therapist']) . '</span></td>';
        echo '<td>' . (int) $m['duration'] . ' min</td>';
        echo '<td>' . ($m['price'] !== null ? '$' . e(number_format((float) $m['price'], 0)) : '—');
        if (!empty($m['notes'])) { echo '<span class="sub">' . e($m['notes']) . '</span>'; }
        echo '</td>';
        echo '<td><button class="btn btn--danger btn--sm" hx-post="/crm/client.php?action=delete_massage&id=' . $id . '" hx-vals=\'{"mid":' . (int) $m['id'] . '}\' hx-target="#massage-rows" hx-confirm="Delete this session?">✕</button></td>';
        echo '</tr>';
    }
}

function render_followup_rows(PDO $pdo, int $id): void
{
    $st = $pdo->prepare('SELECT * FROM followups WHERE client_id=? ORDER BY status="done", due_date IS NULL, due_date ASC, id DESC');
    $st->execute([$id]);
    $rows = $st->fetchAll();
    if (!$rows) { echo '<tr><td colspan="4" class="empty">No follow-ups.</td></tr>'; return; }
    foreach ($rows as $f) {
        $done = $f['status'] === 'done';
        echo '<tr' . ($done ? ' style="opacity:.55"' : '') . '>';
        echo '<td><span class="pill pill--' . e($f['kind']) . '">' . e(ucfirst($f['kind'])) . '</span></td>';
        echo '<td>' . e($f['message']) . '</td>';
        echo '<td>' . ($f['due_date'] ? fmt_date($f['due_date']) : '<span class="muted">—</span>') . '</td>';
        echo '<td>';
        if ($done) {
            echo '<span class="pill pill--done">Done</span>';
        } else {
            echo '<button class="btn btn--ghost btn--sm" hx-post="/crm/client.php?action=resolve_followup&id=' . $id . '" hx-vals=\'{"fid":' . (int) $f['id'] . '}\' hx-target="#followup-rows">Mark done</button>';
        }
        echo '</td></tr>';
    }
}

function render_giftcard_rows(PDO $pdo, array $client): void
{
    // Gift cards linked to this client by purchaser email or recipient name.
    $st = $pdo->prepare('SELECT * FROM gift_cards WHERE (purchaser_email IS NOT NULL AND purchaser_email=?) OR purchaser_name=? OR recipient_name=? ORDER BY created_at DESC');
    $st->execute([$client['email'], $client['name'], $client['name']]);
    $rows = $st->fetchAll();
    if (!$rows) { echo '<tr><td colspan="4" class="empty">No gift cards on file.</td></tr>'; return; }
    foreach ($rows as $g) {
        echo '<tr><td><strong>' . e($g['code'] ?: '—') . '</strong><span class="sub">to ' . e($g['recipient_name'] ?: '—') . '</span></td>';
        echo '<td>$' . e(number_format((float) $g['amount'], 0)) . '<span class="sub">bal $' . e(number_format((float) $g['balance'], 0)) . '</span></td>';
        echo '<td><span class="pill pill--' . ($g['status'] === 'active' ? 'active' : 'inactive') . '">' . e(ucfirst($g['status'])) . '</span></td>';
        echo '<td>' . fmt_date($g['issued_date']) . '</td></tr>';
    }
}

// ---- Actions ----
$action = post('action') ?: get('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_client') {
        $st = $pdo->prepare('UPDATE clients SET name=?, email=?, phone=?, status=?, marketing_opt_in=?, preferences=?, notes=?, updated_at=datetime("now") WHERE id=?');
        $st->execute([
            post('name') ?: $client['name'], post('email') ?: null, post('phone') ?: null,
            in_array(post('status'), ['lead', 'active', 'inactive'], true) ? post('status') : $client['status'],
            isset($_POST['marketing_opt_in']) ? 1 : 0, post('preferences') ?: null, post('notes') ?: null, $id,
        ]);
        header('Location: /crm/client.php?id=' . $id . '&saved=1');
        exit;
    }
    if ($action === 'add_massage') {
        $st = $pdo->prepare('INSERT INTO massages (client_id, therapist, service, duration, date, price, notes) VALUES (?,?,?,?,?,?,?)');
        $st->execute([$id, post('therapist') ?: null, post('service') ?: null,
            (int) post('duration') ?: null, post('date') ?: date('Y-m-d'),
            post('price') !== '' ? (float) post('price') : null, post('notes') ?: null]);
        // Logging a session implies an active client.
        $pdo->prepare('UPDATE clients SET status="active", updated_at=datetime("now") WHERE id=? AND status="lead"')->execute([$id]);
        render_massage_rows($pdo, $id);
        exit;
    }
    if ($action === 'delete_massage') {
        $st = $pdo->prepare('DELETE FROM massages WHERE id=? AND client_id=?');
        $st->execute([(int) post('mid'), $id]);
        render_massage_rows($pdo, $id);
        exit;
    }
    if ($action === 'add_followup') {
        $st = $pdo->prepare('INSERT INTO followups (client_id, kind, message, status, due_date) VALUES (?,?,?,?,?)');
        $st->execute([$id, post('kind') === 'email' ? 'email' : 'callback', post('message') ?: 'Follow up', 'open', post('due_date') ?: null]);
        render_followup_rows($pdo, $id);
        exit;
    }
    if ($action === 'resolve_followup') {
        $st = $pdo->prepare('UPDATE followups SET status="done", resolved_at=datetime("now") WHERE id=? AND client_id=?');
        $st->execute([(int) post('fid'), $id]);
        render_followup_rows($pdo, $id);
        exit;
    }
    if ($action === 'add_giftcard') {
        $amt = (float) post('amount');
        $st = $pdo->prepare('INSERT INTO gift_cards (code, purchaser_name, purchaser_email, recipient_name, amount, balance, status, issued_date, notes) VALUES (?,?,?,?,?,?,?,?,?)');
        $st->execute([post('code') ?: null, $client['name'], $client['email'] ?: null,
            post('recipient_name') ?: $client['name'], $amt, $amt, 'active', post('issued_date') ?: date('Y-m-d'), post('notes') ?: null]);
        render_giftcard_rows($pdo, $client);
        exit;
    }
}

$therapists = hmp_therapists();
$services = hmp_service_options();
$saved = get('saved') === '1';

crm_header($client['name'], 'clients');
?>
<a class="back-link" href="/crm/clients.php">&larr; All clients</a>
<?php if ($saved): ?><div class="notice notice--ok">Saved.</div><?php endif; ?>

<div class="page-head">
  <h1><?= e($client['name']) ?> <?= status_pill($client['status']) ?></h1>
</div>

<div class="detail-grid">
  <!-- Left: contact + preferences (editable) -->
  <div>
    <div class="card section">
      <h2>Client details</h2>
      <form method="post" action="/crm/client.php">
        <input type="hidden" name="action" value="update_client">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="field"><label>Name</label><input name="name" value="<?= e($client['name']) ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($client['email']) ?>"></div>
        <div class="field"><label>Phone</label><input type="tel" name="phone" value="<?= e($client['phone']) ?>"></div>
        <div class="field"><label>Status</label>
          <select name="status">
            <?php foreach (['lead' => 'Lead', 'active' => 'Active client', 'inactive' => 'Inactive'] as $v => $l): ?>
              <option value="<?= $v ?>"<?= $client['status'] === $v ? ' selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>Massage preferences</label><textarea name="preferences" placeholder="Pressure, focus areas, allergies, room preferences…"><?= e($client['preferences']) ?></textarea></div>
        <div class="field"><label>Internal notes</label><textarea name="notes"><?= e($client['notes']) ?></textarea></div>
        <label style="display:flex;gap:.5rem;align-items:center;font-weight:400;margin-bottom:1rem"><input type="checkbox" name="marketing_opt_in" value="1" style="width:auto"<?= (int) $client['marketing_opt_in'] ? ' checked' : '' ?>> Opted in to email marketing</label>
        <button class="btn" type="submit">Save changes</button>
      </form>
      <p class="muted" style="font-size:.8rem;margin-top:.8rem">Source: <?= e(ucfirst((string) $client['source'])) ?> · Added <?= fmt_date($client['created_at']) ?></p>
    </div>
  </div>

  <!-- Right: logs -->
  <div>
    <!-- Massage log -->
    <div class="card section">
      <h2>Massage log</h2>
      <form class="inline-form" hx-post="/crm/client.php?action=add_massage&id=<?= $id ?>" hx-target="#massage-rows" hx-on::after-request="this.reset()" style="margin-bottom:1rem">
        <div class="field"><label>Date</label><input type="date" name="date" value="<?= date('Y-m-d') ?>"></div>
        <div class="field" style="min-width:170px"><label>Service</label>
          <select name="service"><?php foreach ($services as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select></div>
        <div class="field" style="min-width:150px"><label>Therapist</label>
          <select name="therapist"><option value="">—</option><?php foreach ($therapists as $t): ?><option><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field" style="max-width:90px"><label>Min</label><input type="number" name="duration" value="60" min="0"></div>
        <div class="field" style="max-width:90px"><label>$</label><input type="number" name="price" step="1" min="0"></div>
        <div class="field" style="flex-basis:100%"><label>Notes</label><input name="notes" placeholder="Session notes (focus areas, follow-up recommendations…)"></div>
        <button class="btn btn--sm" type="submit">Add session</button>
      </form>
      <div class="table-wrap">
        <table><thead><tr><th>Date</th><th>Service</th><th>Length</th><th>Price / notes</th><th></th></tr></thead>
        <tbody id="massage-rows"><?php render_massage_rows($pdo, $id); ?></tbody></table>
      </div>
    </div>

    <!-- Follow-ups -->
    <div class="card section">
      <h2>Follow-ups</h2>
      <form class="inline-form" hx-post="/crm/client.php?action=add_followup&id=<?= $id ?>" hx-target="#followup-rows" hx-on::after-request="this.reset()" style="margin-bottom:1rem">
        <div class="field" style="max-width:130px"><label>Type</label>
          <select name="kind"><option value="callback">Callback</option><option value="email">Email</option></select></div>
        <div class="field"><label>What's needed</label><input name="message" placeholder="e.g. Call to confirm time"></div>
        <div class="field" style="max-width:160px"><label>Due</label><input type="date" name="due_date"></div>
        <button class="btn btn--sm" type="submit">Add</button>
      </form>
      <div class="table-wrap">
        <table><thead><tr><th>Type</th><th>What's needed</th><th>Due</th><th></th></tr></thead>
        <tbody id="followup-rows"><?php render_followup_rows($pdo, $id); ?></tbody></table>
      </div>
    </div>

    <!-- Gift cards -->
    <div class="card section">
      <h2>Gift cards</h2>
      <form class="inline-form" hx-post="/crm/client.php?action=add_giftcard&id=<?= $id ?>" hx-target="#giftcard-rows" hx-on::after-request="this.reset()" style="margin-bottom:1rem">
        <div class="field" style="max-width:120px"><label>Code</label><input name="code" placeholder="HMP-…"></div>
        <div class="field"><label>Recipient</label><input name="recipient_name" placeholder="<?= e($client['name']) ?>"></div>
        <div class="field" style="max-width:100px"><label>Amount $</label><input type="number" name="amount" min="0" step="1"></div>
        <button class="btn btn--sm" type="submit">Add</button>
      </form>
      <div class="table-wrap">
        <table><thead><tr><th>Code</th><th>Value</th><th>Status</th><th>Issued</th></tr></thead>
        <tbody id="giftcard-rows"><?php render_giftcard_rows($pdo, $client); ?></tbody></table>
      </div>
    </div>
  </div>
</div>

<?php crm_footer(); ?>
