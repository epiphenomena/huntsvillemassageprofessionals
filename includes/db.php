<?php
/**
 * SQLite connection + schema bootstrap (idempotent).
 *
 * One unified data model is shared by the public site and the CRM so that a
 * contact-form lead, a self-booked client, and a manually-added customer are
 * all the SAME `clients` record — which is what makes the promotional export
 * list actually complete.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/data.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = HMP_DB_PATH;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    db_migrate($pdo);
    db_seed($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS clients (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        name            TEXT NOT NULL,
        email           TEXT,
        phone           TEXT,
        status          TEXT NOT NULL DEFAULT 'lead',      -- lead | active | inactive
        source          TEXT,                              -- contact_form | booking | intake | manual | referral
        marketing_opt_in INTEGER NOT NULL DEFAULT 0,
        preferences     TEXT,                              -- massage preferences / pressure / allergies
        notes           TEXT,
        created_at      TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS followups (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id   INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        kind        TEXT NOT NULL DEFAULT 'callback',      -- callback | email
        message     TEXT,
        status      TEXT NOT NULL DEFAULT 'open',          -- open | done
        due_date    TEXT,
        created_at  TEXT NOT NULL DEFAULT (datetime('now')),
        resolved_at TEXT
    );

    CREATE TABLE IF NOT EXISTS massages (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id   INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
        therapist   TEXT,
        service     TEXT,
        duration    INTEGER,                               -- minutes
        date        TEXT,                                  -- YYYY-MM-DD
        price       REAL,
        notes       TEXT,
        created_at  TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS gift_cards (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        code            TEXT,
        purchaser_name  TEXT,
        purchaser_email TEXT,
        recipient_name  TEXT,
        amount          REAL,
        balance         REAL,
        status          TEXT NOT NULL DEFAULT 'active',    -- active | redeemed | expired
        issued_date     TEXT,
        notes           TEXT,
        created_at      TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS therapists (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        slug        TEXT UNIQUE,
        name        TEXT NOT NULL,
        license     TEXT,
        active      INTEGER NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS slots (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        therapist_id INTEGER NOT NULL REFERENCES therapists(id) ON DELETE CASCADE,
        date         TEXT NOT NULL,                        -- YYYY-MM-DD
        start_time   TEXT NOT NULL,                        -- HH:MM (24h)
        end_time     TEXT NOT NULL,
        status       TEXT NOT NULL DEFAULT 'open',         -- open | booked | blocked
        created_at   TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS bookings (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        slot_id     INTEGER NOT NULL REFERENCES slots(id) ON DELETE CASCADE,
        client_id   INTEGER REFERENCES clients(id) ON DELETE SET NULL,
        service     TEXT,
        notes       TEXT,
        created_at  TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS intakes (
        id                INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id         INTEGER REFERENCES clients(id) ON DELETE SET NULL,
        name              TEXT,
        email             TEXT,
        phone             TEXT,
        birthdate         TEXT,
        emergency_contact TEXT,
        referred_by       TEXT,
        allergies         TEXT,
        medications       TEXT,
        conditions        TEXT,                            -- JSON array
        other_conditions  TEXT,
        recent_illnesses  TEXT,
        recent_surgeries  TEXT,
        pregnant          TEXT,
        due_date          TEXT,
        reason            TEXT,
        had_massage_before TEXT,
        marketing_opt_in  INTEGER NOT NULL DEFAULT 0,
        consent_agreed    INTEGER NOT NULL DEFAULT 0,
        consent_signature TEXT,
        consent_date      TEXT,
        created_at        TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE INDEX IF NOT EXISTS idx_clients_status ON clients(status);
    CREATE INDEX IF NOT EXISTS idx_followups_status ON followups(status);
    CREATE INDEX IF NOT EXISTS idx_massages_client ON massages(client_id);
    CREATE INDEX IF NOT EXISTS idx_slots_date ON slots(date);
    SQL);
}

/** Seed therapists (from canonical data) and a little demo content for the CRM. */
function db_seed(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM therapists')->fetchColumn();
    if ($count === 0) {
        $ins = $pdo->prepare('INSERT INTO therapists (slug, name, license) VALUES (?, ?, ?)');
        foreach (hmp_therapists() as $t) {
            $ins->execute([$t['slug'], $t['name'], $t['license']]);
        }
    }

    // Demo CRM data only when completely empty, so the UI isn't blank on first run.
    $clients = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
    if ($clients === 0 && getenv('HMP_NO_DEMO') !== '1') {
        db_seed_demo($pdo);
    }
}

function db_seed_demo(PDO $pdo): void
{
    $ago = static fn (string $rel): string => date('Y-m-d H:i:s', strtotime($rel));

    $pdo->beginTransaction();
    $c = $pdo->prepare('INSERT INTO clients (name, email, phone, status, source, marketing_opt_in, preferences, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');

    $c->execute(['Amanda Reyes', 'amanda.reyes@example.com', '256-555-0142', 'active', 'referral', 1,
        'Prefers firm/deep pressure, focus on shoulders and lower back. Allergic to lavender oil.',
        'Regular monthly client. Loves hot stone add-on.', $ago('-120 days'), $ago('-14 days')]);
    $amanda = (int) $pdo->lastInsertId();

    $c->execute(['Marcus Bell', 'marcus.bell@example.com', '256-555-0188', 'active', 'manual', 1,
        'Medium pressure. Quiet room preferred.',
        'Came in via Just For Him package.', $ago('-60 days'), $ago('-20 days')]);
    $marcus = (int) $pdo->lastInsertId();

    $c->execute(['Priya Nair', 'priya.nair@example.com', '256-555-0173', 'lead', 'contact_form', 1,
        null, 'Submitted website contact form — wants info on lymphatic drainage.', $ago('-3 days'), $ago('-3 days')]);
    $priya = (int) $pdo->lastInsertId();

    $c->execute(['Tom Whitaker', null, '256-555-0119', 'lead', 'booking', 0,
        null, 'Tried to self-book online; needs a callback to confirm a time.', $ago('-1 days'), $ago('-1 days')]);
    $tom = (int) $pdo->lastInsertId();

    $m = $pdo->prepare('INSERT INTO massages (client_id, therapist, service, duration, date, price, notes, created_at) VALUES (?,?,?,?,?,?,?,?)');
    $m->execute([$amanda, 'Karmen Scruggs', 'Deep Tissue', 90, date('Y-m-d', strtotime('-14 days')), 150, 'Focused on lower back; recommend follow-up in 3 weeks.', $ago('-14 days')]);
    $m->execute([$amanda, 'Karmen Scruggs', 'Hot Stone / Heated Bamboo', 60, date('Y-m-d', strtotime('-45 days')), 100, 'Relaxation session.', $ago('-45 days')]);
    $m->execute([$marcus, 'Shauna Gilley', 'Relaxation Massage', 60, date('Y-m-d', strtotime('-20 days')), 100, 'First visit, great feedback.', $ago('-20 days')]);

    $g = $pdo->prepare('INSERT INTO gift_cards (code, purchaser_name, purchaser_email, recipient_name, amount, balance, status, issued_date, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $g->execute(['HMP-1042', 'Amanda Reyes', 'amanda.reyes@example.com', 'Dana Reyes', 150, 150, 'active', date('Y-m-d', strtotime('-10 days')), 'Birthday gift.', $ago('-10 days')]);
    $g->execute(['HMP-1043', 'Marcus Bell', 'marcus.bell@example.com', 'Marcus Bell', 295, 0, 'redeemed', date('Y-m-d', strtotime('-55 days')), 'Just For Him package, fully redeemed.', $ago('-55 days')]);

    $f = $pdo->prepare('INSERT INTO followups (client_id, kind, message, status, due_date, created_at) VALUES (?,?,?,?,?,?)');
    $f->execute([$priya, 'email', 'Reply with lymphatic drainage info and pricing.', 'open', date('Y-m-d', strtotime('+1 day')), $ago('-3 days')]);
    $f->execute([$tom, 'callback', 'Call to confirm preferred appointment time.', 'open', date('Y-m-d'), $ago('-1 days')]);

    // Availability slots for the shared booking calendar — next 14 days, first
    // three therapists, three time blocks/day. A few are pre-booked to demo the
    // free/busy view (booked slots never reveal a client name in the shared view).
    $therapistIds = $pdo->query('SELECT id FROM therapists ORDER BY id LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
    $blocks = [['10:00', '11:00'], ['13:00', '14:00'], ['15:30', '16:30']];
    $slotStmt = $pdo->prepare('INSERT INTO slots (therapist_id, date, start_time, end_time, status) VALUES (?,?,?,?,?)');
    $bookStmt = $pdo->prepare('INSERT INTO bookings (slot_id, client_id, service) VALUES (?,?,?)');
    $bookableClients = [$amanda, $marcus];
    $n = 0;
    for ($d = 1; $d <= 14; $d++) {
        $date = date('Y-m-d', strtotime("+$d days"));
        if (in_array((int) date('N', strtotime($date)), [7], true)) {
            continue; // closed Sundays
        }
        foreach ($therapistIds as $ti => $tid) {
            foreach ($blocks as $bi => [$start, $end]) {
                $booked = (($d + $ti + $bi) % 5 === 0);
                $slotStmt->execute([$tid, $date, $start, $end, $booked ? 'booked' : 'open']);
                if ($booked) {
                    $bookStmt->execute([(int) $pdo->lastInsertId(), $bookableClients[$n % 2], 'Relaxation Massage']);
                    $n++;
                }
            }
        }
    }

    $pdo->commit();
}
