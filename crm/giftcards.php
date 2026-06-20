<?php
require __DIR__ . '/bootstrap.php';
$pdo = db();

function render_gc_rows(PDO $pdo): void
{
    $status = get('status');
    $q = get('q');
    $sql = 'SELECT * FROM gift_cards WHERE 1=1';
    $args = [];
    if (in_array($status, ['active', 'redeemed', 'expired'], true)) { $sql .= ' AND status=?'; $args[] = $status; }
    if ($q !== '') { $sql .= ' AND (code LIKE ? OR purchaser_name LIKE ? OR recipient_name LIKE ?)'; $l = "%$q%"; array_push($args, $l, $l, $l); }
    $sql .= ' ORDER BY created_at DESC, id DESC';
    $st = $pdo->prepare($sql); $st->execute($args);
    $rows = $st->fetchAll();
    if (!$rows) { echo '<tr><td colspan="6" class="empty">No gift cards found.</td></tr>'; return; }
    foreach ($rows as $g) {
        echo '<tr><td><strong>' . e($g['code'] ?: '—') . '</strong></td>';
        echo '<td>' . e($g['purchaser_name'] ?: '—');
        if (!empty($g['purchaser_email'])) { echo '<span class="sub">' . e($g['purchaser_email']) . '</span>'; }
        echo '</td>';
        echo '<td>' . e($g['recipient_name'] ?: '—') . '</td>';
        echo '<td>$' . e(number_format((float) $g['amount'], 0)) . '<span class="sub">bal $' . e(number_format((float) $g['balance'], 0)) . '</span></td>';
        $cls = $g['status'] === 'active' ? 'active' : ($g['status'] === 'redeemed' ? 'done' : 'inactive');
        echo '<td><span class="pill pill--' . $cls . '">' . e(ucfirst($g['status'])) . '</span><span class="sub">' . fmt_date($g['issued_date']) . '</span></td>';
        echo '<td>';
        echo '<select class="btn--sm" hx-post="/crm/giftcards.php" hx-vals=\'{"action":"set_status","gid":' . (int) $g['id'] . '}\' hx-target="#gc-rows" hx-include="#gcfilter" name="newstatus" style="padding:.3rem;width:auto">';
        foreach (['active', 'redeemed', 'expired'] as $s) {
            echo '<option value="' . $s . '"' . ($g['status'] === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>';
        }
        echo '</select></td></tr>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('action') === 'add') {
        $amt = (float) post('amount');
        $st = $pdo->prepare('INSERT INTO gift_cards (code, purchaser_name, purchaser_email, recipient_name, amount, balance, status, issued_date, notes) VALUES (?,?,?,?,?,?,?,?,?)');
        $st->execute([post('code') ?: null, post('purchaser_name') ?: null, post('purchaser_email') ?: null,
            post('recipient_name') ?: null, $amt, post('balance') !== '' ? (float) post('balance') : $amt,
            'active', post('issued_date') ?: date('Y-m-d'), post('notes') ?: null]);
        render_gc_rows($pdo);
        exit;
    }
    if (post('action') === 'set_status') {
        $ns = in_array(post('newstatus'), ['active', 'redeemed', 'expired'], true) ? post('newstatus') : 'active';
        $bal = $ns === 'redeemed' ? 0 : null;
        if ($bal === null) {
            $pdo->prepare('UPDATE gift_cards SET status=? WHERE id=?')->execute([$ns, (int) post('gid')]);
        } else {
            $pdo->prepare('UPDATE gift_cards SET status=?, balance=0 WHERE id=?')->execute([$ns, (int) post('gid')]);
        }
        render_gc_rows($pdo);
        exit;
    }
}

if (is_htmx()) { render_gc_rows($pdo); exit; }

$total = (float) $pdo->query("SELECT COALESCE(SUM(balance),0) FROM gift_cards WHERE status='active'")->fetchColumn();
crm_header('Gift Cards', 'giftcards');
?>
<div class="page-head">
  <div><h1>Gift cards</h1>
  <p class="muted">$<?= number_format($total, 0) ?> in outstanding active balances.</p></div>
</div>

<div class="card section">
  <h2>Log a gift card</h2>
  <form hx-post="/crm/giftcards.php" hx-target="#gc-rows" hx-on::after-request="this.reset()">
    <input type="hidden" name="action" value="add">
    <div class="grid cols-4">
      <div class="field"><label>Code</label><input name="code" placeholder="HMP-1044"></div>
      <div class="field"><label>Amount $</label><input type="number" name="amount" min="0" step="1" required></div>
      <div class="field"><label>Purchaser</label><input name="purchaser_name"></div>
      <div class="field"><label>Purchaser email</label><input type="email" name="purchaser_email"></div>
      <div class="field"><label>Recipient</label><input name="recipient_name"></div>
      <div class="field"><label>Issued</label><input type="date" name="issued_date" value="<?= date('Y-m-d') ?>"></div>
      <div class="field" style="grid-column:span 2"><label>Notes</label><input name="notes"></div>
    </div>
    <button class="btn" type="submit">Add gift card</button>
  </form>
</div>

<form id="gcfilter" class="toolbar" hx-get="/crm/giftcards.php" hx-target="#gc-rows" hx-trigger="keyup changed delay:300ms from:input[type=search], change">
  <input type="search" name="q" placeholder="Search code, purchaser, recipient…">
  <select name="status">
    <option value="">All statuses</option>
    <option value="active">Active</option>
    <option value="redeemed">Redeemed</option>
    <option value="expired">Expired</option>
  </select>
</form>

<div class="table-wrap">
  <table>
    <thead><tr><th>Code</th><th>Purchaser</th><th>Recipient</th><th>Value</th><th>Status</th><th>Set status</th></tr></thead>
    <tbody id="gc-rows"><?php render_gc_rows($pdo); ?></tbody>
  </table>
</div>

<?php crm_footer(); ?>
