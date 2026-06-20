<?php
require __DIR__ . '/bootstrap.php';
$pdo = db();

// --- Create a new client (standard POST + redirect) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'add_client') {
    $name = post('name');
    if ($name !== '') {
        $st = $pdo->prepare(
            'INSERT INTO clients (name, email, phone, status, source, marketing_opt_in, preferences, notes)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $name, post('email') ?: null, post('phone') ?: null,
            in_array(post('status'), ['lead', 'active', 'inactive'], true) ? post('status') : 'lead',
            'manual', isset($_POST['marketing_opt_in']) ? 1 : 0,
            post('preferences') ?: null, post('notes') ?: null,
        ]);
        header('Location: /crm/client.php?id=' . (int) $pdo->lastInsertId() . '&saved=1');
        exit;
    }
}

// --- Query (search + filters) ---
$q      = get('q');
$status = get('status');
$optin  = get('optin') === '1';

$sql = 'SELECT * FROM clients WHERE 1=1';
$args = [];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like);
}
if (in_array($status, ['lead', 'active', 'inactive'], true)) {
    $sql .= ' AND status = ?';
    $args[] = $status;
}
if ($optin) {
    $sql .= ' AND marketing_opt_in = 1';
}
$sql .= ' ORDER BY updated_at DESC, id DESC';
$st = $pdo->prepare($sql);
$st->execute($args);
$clients = $st->fetchAll();

/** Render just the table rows (shared by full page + HTMX). */
function render_client_rows(array $clients): void
{
    if (!$clients) {
        echo '<tr><td colspan="4" class="empty">No clients match your search.</td></tr>';
        return;
    }
    foreach ($clients as $c) {
        $id = (int) $c['id'];
        echo '<tr>';
        echo '<td><a href="/crm/client.php?id=' . $id . '"><strong>' . e($c['name']) . '</strong></a>';
        if (!empty($c['preferences'])) {
            echo '<span class="sub">' . e(mb_strimwidth($c['preferences'], 0, 70, '…')) . '</span>';
        }
        echo '</td>';
        echo '<td>';
        if (!empty($c['email'])) { echo '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>'; }
        if (!empty($c['phone'])) { echo '<span class="sub">' . e($c['phone']) . '</span>'; }
        if (empty($c['email']) && empty($c['phone'])) { echo '<span class="muted">—</span>'; }
        echo '</td>';
        echo '<td>' . status_pill($c['status']);
        if ((int) $c['marketing_opt_in'] === 1) { echo ' <span class="pill pill--active" title="Email opt-in">✉</span>'; }
        echo '<span class="sub">' . e(ucfirst((string) $c['source'])) . '</span></td>';
        echo '<td style="white-space:nowrap"><a class="btn btn--ghost btn--sm" href="/crm/client.php?id=' . $id . '">Open</a></td>';
        echo '</tr>';
    }
}

// HTMX request → return only the rows fragment.
if (is_htmx()) {
    render_client_rows($clients);
    exit;
}

crm_header('Clients', 'clients');
$showNew = get('new') === '1';
?>
<div class="page-head">
  <div>
    <h1>Clients</h1>
    <p class="muted"><?= count($clients) ?> shown · search and filter your full client &amp; lead list.</p>
  </div>
  <a class="btn" href="/crm/clients.php?new=1#newclient">+ Add Client</a>
</div>

<?php if ($showNew): ?>
<div class="card" id="newclient" style="margin-bottom:1.4rem">
  <h2 style="border-bottom:2px solid var(--gold);padding-bottom:.4rem">New client</h2>
  <form method="post" action="/crm/clients.php">
    <input type="hidden" name="action" value="add_client">
    <div class="grid cols-2">
      <div class="field"><label>Name *</label><input name="name" required autofocus></div>
      <div class="field"><label>Status</label>
        <select name="status"><option value="lead">Lead</option><option value="active">Active client</option><option value="inactive">Inactive</option></select></div>
      <div class="field"><label>Email</label><input type="email" name="email"></div>
      <div class="field"><label>Phone</label><input type="tel" name="phone"></div>
    </div>
    <div class="field"><label>Massage preferences</label><input name="preferences" placeholder="Pressure, focus areas, allergies, room preferences…"></div>
    <div class="field"><label>Notes</label><textarea name="notes"></textarea></div>
    <label style="display:flex;gap:.5rem;align-items:center;font-weight:400;margin-bottom:1rem"><input type="checkbox" name="marketing_opt_in" value="1" style="width:auto"> Opted in to email marketing</label>
    <div class="row">
      <button class="btn" type="submit">Save client</button>
      <a class="btn btn--ghost" href="/crm/clients.php">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<form id="filterbar" class="toolbar" hx-get="/crm/clients.php" hx-target="#client-rows"
      hx-trigger="keyup changed delay:300ms from:input[type=search], change" hx-indicator="#searching">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone…" aria-label="Search clients">
  <select name="status" aria-label="Filter by status">
    <option value="">All statuses</option>
    <option value="lead"<?= $status === 'lead' ? ' selected' : '' ?>>Leads</option>
    <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active clients</option>
    <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactive</option>
  </select>
  <label style="display:flex;gap:.4rem;align-items:center;font-weight:500">
    <input type="checkbox" name="optin" value="1" style="width:auto"<?= $optin ? ' checked' : '' ?>> Email opt-ins only
  </label>
  <span id="searching" class="htmx-indicator search-status">Searching…</span>
  <span style="margin-left:auto"></span>
  <a class="btn btn--ghost btn--sm" href="/crm/export.php">Export all (CSV)</a>
  <a class="btn btn--gold btn--sm" href="/crm/export.php?optin=1">Export opt-ins</a>
</form>

<div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Contact</th><th>Status</th><th></th></tr></thead>
    <tbody id="client-rows"><?php render_client_rows($clients); ?></tbody>
  </table>
</div>

<?php crm_footer(); ?>
