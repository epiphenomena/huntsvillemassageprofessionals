# Huntsville Massage Professionals

A modern, mobile-friendly website + lightweight CRM + shared booking calendar for
[huntsvillemassageprofessionals.com](https://www.huntsvillemassageprofessionals.com/),
plus branded, downloadable client forms.

Built with **plain PHP 8 + SQLite + HTMX** — no build step, no framework, runs on
ordinary shared hosting.

---

## What's included

### Public website
| Page | File | Notes |
|------|------|-------|
| Home (landing) | `index.php` | Hero, features, about teaser, therapists, gift cards, CTAs |
| About | `about.php` | Philosophy + how it works |
| Therapists | `therapists.php` | Grid of all 7 therapists |
| Therapist profile | `therapist.php?t=<slug>` | Bio, specialties, booking link |
| Services & pricing | `services.php` | Full menu + spa packages |
| Gift cards | `gift-cards.php` | Info + request form |
| Book | `book.php` | Self-service slot picker → booking request |
| Contact | `contact.php` | Contact info + message form |
| Forms | `forms.php` | Digital intake + consent, plus `.docx` downloads |

Form handlers: `contact-handler.php`, `intake-handler.php` (booking is handled in
`book.php`). All public forms use a **honeypot field + CSRF token** for spam/abuse
protection and **prepared statements** for every DB write.

### CRM — `/crm/` (staff only, HTTP Basic Auth)
- **Dashboard** — at-a-glance stats, follow-ups due, recent sessions
- **Clients** — live search/filter, add/edit, massage preferences & notes, CSV export
- **Client detail** — editable profile + massage log + follow-ups + gift cards (HTMX)
- **Calendar** — shared availability across therapists (booked slots **never show
  client names**); add availability, mark time off
- **Follow-ups** — callback/email queue with overdue flags
- **Gift cards** — issue, search, change status
- **Export** — `export.php` → CSV for Mailchimp-style promo imports (respects filters
  and the email opt-in flag)

### Downloadable forms — `/forms/`
- `Client-Intake-Form.docx`
- `Consent-and-Service-Agreement.docx`

Regenerate them from `tools/generate_forms.py` (see below).

---

## Unified data model

Everything funnels into one `clients` table so the promotional list is complete:
a **contact-form lead**, a **self-booked client**, an **intake submission**, and a
**manually-added customer** are all the same record (de-duplicated by email/phone).
Tables: `clients`, `followups`, `massages`, `gift_cards`, `therapists`, `slots`,
`bookings`, `intakes`. Schema + seed live in `includes/db.php` and run automatically
on first request.

---

## Running locally

```bash
php -S 127.0.0.1:8000        # from the project root
```

Then visit http://127.0.0.1:8000/ . The SQLite DB is created automatically at
`storage/hmp.sqlite` with demo data on first load.

- CRM: http://127.0.0.1:8000/crm/ — default login **`admin` / `changeme`**
- To start with an empty DB (no demo content): `HMP_NO_DEMO=1 php -S 127.0.0.1:8000`

### Regenerating the DOCX forms
Requires `python-docx`:
```bash
python3 -m venv .venv && . .venv/bin/activate
pip install python-docx
python tools/generate_forms.py      # writes to ./forms/
```

---

## Deployment & security checklist

This app stores customer PII **and health information**. Before going live:

1. **Serve everything over HTTPS.** Basic Auth sends credentials in plaintext
   (Base64), so `/crm` is only safe over TLS.
2. **Change the CRM password.** Edit `CRM_PASSWORD_HASH` in `config.php`:
   ```bash
   php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```
3. **Keep the database out of the webroot.** Best: set `HMP_DB_PATH` to a directory
   above `public_html`. Otherwise the shipped `storage/.htaccess` and root
   `.htaccess` deny web access to it (Apache only — see #5).
4. **Lock down `config.php`, `includes/` and `storage/`** — the root `.htaccess`
   already returns 403 for these. Confirm they aren't downloadable.
5. **`.htaccess` is Apache-only.** On **nginx**, translate the rules into your
   server block: deny `/storage`, `/includes`, `config.php`; the `/crm` auth is
   enforced in PHP (`crm/bootstrap.php`) so it works regardless of web server.
6. **Email delivery (DreamHost SMTP).** Set the SMTP block in `config.php` (or the
   matching `HMP_SMTP_*` environment variables):
   ```
   HMP_SMTP_HOST=smtp.dreamhost.com
   HMP_SMTP_PORT=465            # 465 = implicit TLS, or 587 = STARTTLS
   HMP_SMTP_USER=smtp-auth@huntsvillemassageprofessionals.com
   HMP_SMTP_PASS=<mailbox password>
   ```
   With `SMTP_HOST` set, mail goes out via authenticated SMTP (verified working
   against `smtp.dreamhost.com` on both 465 and 587); leave it empty to fall back
   to PHP `mail()`. The recipient is `CONTACT_NOTIFY_EMAIL`. Submissions are always
   saved to the CRM even if mail fails. Don't commit the password — prefer the env
   var, or note that `config.php` is denied web access by `.htaccess`.
7. **Legal review.** The consent/liability wording in `forms.php` and the generated
   consent `.docx` is a modernized starting point — have a licensed professional
   review it before use.

---

## Portability — deploy on any domain

Nothing is hard-wired to a specific domain. All internal links are root-relative
(`/assets`, `/crm`, …), so the site works under any hostname (including a DreamHost
staging URL) with no find-and-replace. The only domain-specific values live in
`config.php` (`BUSINESS` details + the SMTP login) and `includes/data.php` (content).
Drop the folder in your webroot and it runs. Requirements: **PHP 8.0+** (8.1–8.3 fine
on DreamHost) with the **`pdo_sqlite`** and **`mbstring`** extensions — both standard
on DreamHost shared hosting (verify via a `phpinfo()` page if unsure).

## Customizing

- **Business details** (phone, email, address, hours, tagline): `config.php → BUSINESS`.
- **Therapists, services, packages, intake conditions**: `includes/data.php`.
- **Therapist photos**: replace the placeholders in `assets/img/therapists/<slug>.svg`
  with real headshots (keep the filename or update the slug in `data.php`).
- **Brand colors / fonts**: CSS custom properties at the top of `assets/css/style.css`
  (public) and `crm/assets/crm.css` (CRM).

## Notes & possible next steps
- Booking is a **request** flow: a client's chosen slot is held and a staff follow-up
  is created to confirm — therapists keep doing their own scheduling/payments, as today.
- For mass email, export the CSV and import into Mailchimp (or similar).
- A natural enhancement is automated booking-confirmation emails and per-therapist
  recurring availability templates.
