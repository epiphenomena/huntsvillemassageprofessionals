<?php
/**
 * Shared availability calendar for therapists.
 *
 * Every therapist can see everyone's free/busy times so they can coordinate.
 * Privacy: booked slots show the service only — never the client's name.
 */

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
$pdo = db();

/** Render one week of the calendar (shared by full page + HTMX swaps). */
function render_calendar(PDO $pdo): void
{
    $off = (int) (get('off') ?: ($_POST['off'] ?? 0));
    $filter = get('therapist') ?: ($_POST['therapist'] ?? '');
    $monday = strtotime('monday this week ' . ($off ? ($off > 0 ? "+$off week" : "$off week") : ''));
    $start = date('Y-m-d', $monday);
    $end = date('Y-m-d', strtotime('+6 days', $monday));

    $sql = "SELECT s.*, t.name AS therapist_name, b.service AS booked_service
            FROM slots s JOIN therapists t ON t.id=s.therapist_id
            LEFT JOIN bookings b ON b.slot_id=s.id
            WHERE s.date BETWEEN ? AND ?";
    $args = [$start, $end];
    if ($filter !== '') { $sql .= ' AND t.id=?'; $args[] = (int) $filter; }
    $sql .= ' ORDER BY s.date, s.start_time, t.name';
    $st = $pdo->prepare($sql); $st->execute($args);
    $slots = $st->fetchAll();

    $byDate = [];
    foreach ($slots as $s) { $byDate[$s['date']][] = $s; }

    echo '<div class="cal-head">';
    echo '<button class="btn btn--ghost btn--sm" hx-get="/crm/calendar.php" hx-vals=\'{"off":' . ($off - 1) . '}\' hx-include="#cal-therapist" hx-target="#calendar">&larr; Prev</button>';
    echo '<strong>' . e(date('M j', $monday)) . ' – ' . e(date('M j, Y', strtotime('+6 days', $monday))) . '</strong>';
    echo '<button class="btn btn--ghost btn--sm" hx-get="/crm/calendar.php" hx-vals=\'{"off":' . ($off + 1) . '}\' hx-include="#cal-therapist" hx-target="#calendar">Next &rarr;</button>';
    if ($off !== 0) {
        echo '<button class="btn btn--ghost btn--sm" hx-get="/crm/calendar.php" hx-vals=\'{"off":0}\' hx-include="#cal-therapist" hx-target="#calendar">Today</button>';
    }
    echo '<input type="hidden" name="off" value="' . $off . '" form="addslot">';
    echo '</div>';

    echo '<div class="cal-grid">';
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days", $monday));
        $isToday = $date === date('Y-m-d');
        echo '<div class="cal-day' . ($isToday ? ' cal-day--today' : '') . '">';
        echo '<div class="cal-day__label">' . e(date('D', strtotime($date))) . '<span>' . e(date('j', strtotime($date))) . '</span></div>';
        $daySlots = $byDate[$date] ?? [];
        if (!$daySlots) {
            echo '<p class="cal-empty">—</p>';
        }
        foreach ($daySlots as $s) {
            $time = date('g:i A', strtotime($s['start_time']));
            if ($s['status'] === 'open') {
                echo '<div class="cal-slot cal-slot--open">';
                echo '<span class="cal-slot__time">' . e($time) . '</span><span class="cal-slot__who">' . e($s['therapist_name']) . '</span>';
                echo '<span class="cal-slot__free">Free</span>';
                echo '<div class="cal-slot__act">';
                echo '<button title="Mark as time off" hx-post="/crm/calendar.php" hx-vals=\'{"action":"block","sid":' . (int) $s['id'] . ',"off":' . $off . '}\' hx-include="#cal-therapist" hx-target="#calendar">block</button>';
                echo '<button title="Remove slot" hx-post="/crm/calendar.php" hx-vals=\'{"action":"delete","sid":' . (int) $s['id'] . ',"off":' . $off . '}\' hx-include="#cal-therapist" hx-target="#calendar" hx-confirm="Remove this open slot?">✕</button>';
                echo '</div></div>';
            } elseif ($s['status'] === 'booked') {
                echo '<div class="cal-slot cal-slot--booked" title="Booked (client details hidden)">';
                echo '<span class="cal-slot__time">' . e($time) . '</span><span class="cal-slot__who">' . e($s['therapist_name']) . '</span>';
                echo '<span class="cal-slot__booked">Booked' . ($s['booked_service'] ? ' · ' . e($s['booked_service']) : '') . '</span>';
                echo '</div>';
            } else { // blocked
                echo '<div class="cal-slot cal-slot--blocked">';
                echo '<span class="cal-slot__time">' . e($time) . '</span><span class="cal-slot__who">' . e($s['therapist_name']) . '</span>';
                echo '<span class="cal-slot__off">Time off</span>';
                echo '<div class="cal-slot__act">';
                echo '<button hx-post="/crm/calendar.php" hx-vals=\'{"action":"open","sid":' . (int) $s['id'] . ',"off":' . $off . '}\' hx-include="#cal-therapist" hx-target="#calendar">open</button>';
                echo '<button hx-post="/crm/calendar.php" hx-vals=\'{"action":"delete","sid":' . (int) $s['id'] . ',"off":' . $off . '}\' hx-include="#cal-therapist" hx-target="#calendar" hx-confirm="Remove this slot?">✕</button>';
                echo '</div></div>';
            }
        }
        echo '</div>';
    }
    echo '</div>';
}

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'add') {
        $tid = (int) post('therapist_id');
        $date = post('date');
        $start = post('start_time');
        $end = post('end_time') ?: $start;
        if ($tid && $date && $start) {
            $st = $pdo->prepare('INSERT INTO slots (therapist_id, date, start_time, end_time, status) VALUES (?,?,?,?,?)');
            $st->execute([$tid, $date, $start, $end, 'open']);
        }
    } elseif ($action === 'block') {
        $pdo->prepare("UPDATE slots SET status='blocked' WHERE id=? AND status='open'")->execute([(int) post('sid')]);
    } elseif ($action === 'open') {
        $pdo->prepare("UPDATE slots SET status='open' WHERE id=? AND status='blocked'")->execute([(int) post('sid')]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM slots WHERE id=? AND status!='booked'")->execute([(int) post('sid')]);
    }
    render_calendar($pdo);
    exit;
}

if (is_htmx()) { render_calendar($pdo); exit; }

$therapists = $pdo->query('SELECT id, name FROM therapists ORDER BY name')->fetchAll();
crm_header('Calendar', 'calendar');
?>
<div class="page-head">
  <div><h1>Shared calendar</h1>
  <p class="muted">Everyone's availability at a glance. Booked times never show client names.</p></div>
</div>

<div class="card section">
  <h2>Add availability</h2>
  <form id="addslot" class="inline-form" hx-post="/crm/calendar.php" hx-target="#calendar" hx-on::after-request="this.reset()">
    <input type="hidden" name="action" value="add">
    <div class="field" style="min-width:170px"><label>Therapist</label>
      <select name="therapist_id" required><?php foreach ($therapists as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Date</label><input type="date" name="date" value="<?= date('Y-m-d') ?>" required></div>
    <div class="field" style="max-width:130px"><label>Start</label><input type="time" name="start_time" value="10:00" required></div>
    <div class="field" style="max-width:130px"><label>End</label><input type="time" name="end_time" value="11:00"></div>
    <button class="btn btn--sm" type="submit">Add slot</button>
  </form>
</div>

<div class="toolbar">
  <label style="font-weight:600">Filter:&nbsp;</label>
  <select id="cal-therapist" name="therapist" hx-get="/crm/calendar.php" hx-target="#calendar" hx-trigger="change" style="width:auto">
    <option value="">All therapists</option>
    <?php foreach ($therapists as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
  </select>
  <span class="cal-legend"><span class="dot dot--open"></span>Free <span class="dot dot--booked"></span>Booked <span class="dot dot--blocked"></span>Time off</span>
</div>

<div id="calendar" class="card"><?php render_calendar($pdo); ?></div>

<?php crm_footer(); ?>
