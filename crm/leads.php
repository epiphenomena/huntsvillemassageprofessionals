<?php
require __DIR__ . '/bootstrap.php';
$pdo = db();

// Resolve a follow-up (HTMX) → return refreshed rows.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'resolve') {
    $st = $pdo->prepare('UPDATE followups SET status="done", resolved_at=datetime("now") WHERE id=?');
    $st->execute([(int) post('fid')]);
    render_lead_rows($pdo);
    exit;
}

$show = get('show') ?: 'open';   // open | all
$kind = get('kind');             // '' | callback | email

function render_lead_rows(PDO $pdo): void
{
    $show = get('show') ?: 'open';
    $kind = get('kind');
    $sql = 'SELECT f.*, c.name AS client_name, c.email, c.phone FROM followups f JOIN clients c ON c.id=f.client_id WHERE 1=1';
    $args = [];
    if ($show !== 'all') { $sql .= " AND f.status='open'"; }
    if (in_array($kind, ['callback', 'email'], true)) { $sql .= ' AND f.kind=?'; $args[] = $kind; }
    $sql .= ' ORDER BY f.status="done", f.due_date IS NULL, f.due_date ASC, f.id DESC';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $rows = $st->fetchAll();

    if (!$rows) { echo '<tr><td colspan="5" class="empty">Nothing here — you\'re all caught up. 🎉</td></tr>'; return; }
    $today = date('Y-m-d');
    foreach ($rows as $f) {
        $done = $f['status'] === 'done';
        $overdue = !$done && $f['due_date'] && $f['due_date'] < $today;
        echo '<tr' . ($done ? ' style="opacity:.55"' : '') . '>';
        echo '<td><a href="/crm/client.php?id=' . (int) $f['client_id'] . '"><strong>' . e($f['client_name']) . '</strong></a>';
        $contact = $f['email'] ?: $f['phone'];
        if ($contact) { echo '<span class="sub">' . e($contact) . '</span>'; }
        echo '</td>';
        echo '<td><span class="pill pill--' . e($f['kind']) . '">' . e(ucfirst($f['kind'])) . '</span></td>';
        echo '<td>' . e($f['message']) . '</td>';
        echo '<td' . ($overdue ? ' style="color:var(--danger);font-weight:600"' : '') . '>' . ($f['due_date'] ? fmt_date($f['due_date']) : '<span class="muted">—</span>') . ($overdue ? ' ⚠' : '') . '</td>';
        echo '<td>';
        if ($done) { echo '<span class="pill pill--done">Done</span>'; }
        else { echo '<button class="btn btn--ghost btn--sm" hx-post="/crm/leads.php" hx-vals=\'{"action":"resolve","fid":' . (int) $f['id'] . '}\' hx-target="#lead-rows" hx-include="#leadfilter">Mark done</button>'; }
        echo '</td></tr>';
    }
}

if (is_htmx()) { render_lead_rows($pdo); exit; }

$openCount = (int) $pdo->query("SELECT COUNT(*) FROM followups WHERE status='open'")->fetchColumn();
crm_header('Follow-ups', 'leads');
?>
<div class="page-head">
  <div><h1>Follow-ups &amp; callbacks</h1>
  <p class="muted"><?= $openCount ?> open · clients waiting on a call or email reply.</p></div>
</div>

<form id="leadfilter" class="toolbar" hx-get="/crm/leads.php" hx-target="#lead-rows" hx-trigger="change">
  <select name="show">
    <option value="open"<?= $show === 'open' ? ' selected' : '' ?>>Open only</option>
    <option value="all"<?= $show === 'all' ? ' selected' : '' ?>>Show all</option>
  </select>
  <select name="kind">
    <option value="">All types</option>
    <option value="callback"<?= $kind === 'callback' ? ' selected' : '' ?>>Callbacks</option>
    <option value="email"<?= $kind === 'email' ? ' selected' : '' ?>>Emails</option>
  </select>
</form>

<div class="table-wrap">
  <table>
    <thead><tr><th>Client</th><th>Type</th><th>What's needed</th><th>Due</th><th></th></tr></thead>
    <tbody id="lead-rows"><?php render_lead_rows($pdo); ?></tbody>
  </table>
</div>

<?php crm_footer(); ?>
