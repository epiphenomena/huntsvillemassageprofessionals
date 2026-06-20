<?php
require __DIR__ . '/bootstrap.php';
$pdo = db();

$stats = [
    'clients'   => (int) $pdo->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn(),
    'leads'     => (int) $pdo->query("SELECT COUNT(*) FROM clients WHERE status='lead'")->fetchColumn(),
    'followups' => (int) $pdo->query("SELECT COUNT(*) FROM followups WHERE status='open'")->fetchColumn(),
    'optin'     => (int) $pdo->query("SELECT COUNT(*) FROM clients WHERE marketing_opt_in=1")->fetchColumn(),
];

$dueToday = $pdo->query(
    "SELECT f.*, c.name AS client_name, c.email, c.phone
     FROM followups f JOIN clients c ON c.id = f.client_id
     WHERE f.status='open' AND (f.due_date IS NULL OR f.due_date <= date('now'))
     ORDER BY f.due_date IS NULL, f.due_date ASC LIMIT 10"
)->fetchAll();

$recentMassages = $pdo->query(
    "SELECT m.*, c.name AS client_name FROM massages m JOIN clients c ON c.id=m.client_id
     ORDER BY m.date DESC, m.id DESC LIMIT 6"
)->fetchAll();

crm_header('Dashboard', 'dashboard');
?>
<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="muted">A quick look at your clients, leads and to-dos.</p>
  </div>
  <a class="btn" href="/crm/clients.php?new=1">+ Add Client</a>
</div>

<div class="grid cols-4" style="margin-bottom:1.6rem">
  <div class="card stat"><span class="num"><?= $stats['clients'] ?></span><span class="lbl">Active clients</span><br><a href="/crm/clients.php?status=active">View</a></div>
  <div class="card stat"><span class="num"><?= $stats['leads'] ?></span><span class="lbl">Leads</span><br><a href="/crm/clients.php?status=lead">View</a></div>
  <div class="card stat"><span class="num"><?= $stats['followups'] ?></span><span class="lbl">Open follow-ups</span><br><a href="/crm/leads.php">View</a></div>
  <div class="card stat"><span class="num"><?= $stats['optin'] ?></span><span class="lbl">Email opt-ins</span><br><a href="/crm/export.php?optin=1">Export</a></div>
</div>

<div class="grid cols-2">
  <div class="card">
    <h2 style="border-bottom:2px solid var(--gold);padding-bottom:.4rem">Follow-ups due</h2>
    <?php if (!$dueToday): ?>
      <p class="empty">🎉 You're all caught up — no follow-ups due.</p>
    <?php else: ?>
      <table>
        <tbody>
        <?php foreach ($dueToday as $f): ?>
          <tr>
            <td>
              <a href="/crm/client.php?id=<?= (int) $f['client_id'] ?>"><strong><?= e($f['client_name']) ?></strong></a>
              <span class="sub"><?= e($f['message'] ?: 'Follow up') ?></span>
            </td>
            <td style="white-space:nowrap">
              <span class="pill pill--<?= e($f['kind']) ?>"><?= e(ucfirst($f['kind'])) ?></span>
              <span class="sub"><?= $f['due_date'] ? fmt_date($f['due_date']) : 'No date' ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 style="border-bottom:2px solid var(--gold);padding-bottom:.4rem">Recent sessions</h2>
    <?php if (!$recentMassages): ?>
      <p class="empty">No massage sessions logged yet.</p>
    <?php else: ?>
      <table>
        <tbody>
        <?php foreach ($recentMassages as $m): ?>
          <tr>
            <td><a href="/crm/client.php?id=<?= (int) $m['client_id'] ?>"><strong><?= e($m['client_name']) ?></strong></a>
              <span class="sub"><?= e($m['service']) ?> · <?= (int) $m['duration'] ?> min · <?= e($m['therapist']) ?></span></td>
            <td style="white-space:nowrap"><?= fmt_date($m['date']) ?><?php if ($m['price']): ?><span class="sub">$<?= e(number_format((float) $m['price'], 0)) ?></span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php crm_footer(); ?>
