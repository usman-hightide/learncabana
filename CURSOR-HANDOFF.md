# CURSOR-HANDOFF — Learn Cabana (Complete Working Analysis)

**Audience:** New Cursor session on another laptop  
**Author of record:** Usman Zahid  
**Local path (source machine):** `/Users/macbookpro/Local Sites/learcabana`  
**Production:** `https://learncabana.com`  
**Local URL:** `http://learcabana.local`  
**GitHub:** `https://github.com/usman-hightide/learncabana.git` (`main`)  
**Handoff prepared:** 2026-11-01  

Read this file before changing code. Production is running the same custom code paths described below.

---

## 1. What this project is

**Learn Cabana** = Canna Cabana / High Tide employee LMS on WordPress.

| Layer | Stack |
|-------|--------|
| CMS | WordPress (single site, not multisite) |
| LMS | LearnDash (`sfwd-lms`) |
| Reporting UI | Tin Canny Reporting |
| Theme | Astra + **astra-child** (custom reports + email guards) |
| Security | Kadence Security Pro (`ithemes-security-pro`) — IP bans/lockouts |
| Account lock | Lock User Account plugin (`baba_user_locked` = `yes`) |
| Hosting (prod) | Nexcess + nginx (`x-cache-nxaccel`) |
| Local | Local WP (Flywheel), site ID `ZiCen3nYr` |

Primary stakeholders: **Nikki Linton** (Retail Training), Basecamp consulting (Usman / Basit / Bhaumik), store managers.

---

## 2. Critical custom code (do not “rewrite from scratch”)

### 2.1 Province + District/Area Manager filters (User Report)

**MU-plugin**

- `app/public/wp-content/mu-plugins/learcabana-group-province.php` (loader, v1.2.6+)
- `app/public/wp-content/mu-plugins/learcabana-group-province/`
  - `province-helpers.php` — province codes, group meta
  - `manager-helpers.php` — supervisor tree **UNION** LearnDash group membership
  - `province-reporting.php` — Tin Canny hooks / AJAX filter params
  - `templates/groups-drop-down.php` — UI filters
  - `assets/province-reporting.js` / `.css`

**Behavior**

- Province is stored on **LearnDash groups** (ACF), not every user profile.
- AM/DM filters originally used only `supervisor` user meta → incomplete for stores where SM supervisor meta was wrong/empty.
- **Fix (deployed):** `lc_get_user_ids_for_area_manager` / DM variants also include users in groups where that manager is a **group leader**.
- Verified on production for **Tomas Fischer (AM Ontario)** — previously missing stores (Collingwood, Fairway, Highland Road, Markdale, Waterloo Uptown, etc.) now appear.

### 2.2 Weekly learning emails

**Theme**

- `app/public/wp-content/themes/astra-child/custom-reports/custom-learning-reports.php`
- Templates: `learner-report.php`, `manager-report.php`
- Loaded from `astra-child/functions.php`

**Behavior**

- Learner weekly reminder cron: `send_learn_dash_weekly_report`
- Manager weekly (SM → AM → DM) cron: `send_learn_dash_weekly_manager_report` (Mondays)
- Skips locked accounts via `lc_is_user_account_locked()`

### 2.3 Locked users must not get LMS emails

**Theme**

- `app/public/wp-content/themes/astra-child/inc/locked-user-emails.php`
- Required early from `functions.php`

**Why**

- Lock User Account plugin only blocks **login**, not `wp_mail` / LearnDash Notifications.
- Ticket: **Mervin Leach** still received emails after “offboard”.
- Fix: strip/block locked recipients (`pre_wp_mail`, `wp_mail`, `learndash_notification_recipients_emails`, `learndash_notifications_send_notification`), plus license reminder skip.

**Important:** Emails stop only if `baba_user_locked === 'yes'`. Offboarding still requires lock + remove from groups on production.

---

## 3. Recurring production support pattern: store “Forbidden / locked out”

Stores report: *“Learn Cabana locked / Forbidden on every device.”*

Screenshot text is usually:

> You have been locked out of this site  

URL: `learncabana.com/login/`

**This is almost always an IP lock in Kadence Security**, not a single WP user “Locked” column.

### Fix playbook

1. WP Admin → **Security → Dashboard** (`?page=itsec-dashboard`)
2. Check **Banned IPs** and **Active Lockouts**
3. Release / Remove Ban for the store IP
4. Prefer add IP to **whitelist** under Security settings
5. Ask store to confirm; if still broken, get IP via “what is my IP” and check **Nexcess** firewall (WP clear ≠ hosting clear)

### Tickets handled in this lineage

| Ticket / store | Outcome |
|----------------|---------|
| Circle Drive | IP bans cleared; Aletha confirmed access |
| Haysboro | No active ban at check time; asked store to confirm / send IP |
| Watters Road (`z92047cvaa`) | Found ban `181.118.57.18` (Brute Force); removed; Active Lockouts clear; asked store to retest |

---

## 4. Completed work summary (for continuity)

- [x] Circle Drive LMS IP unlock (urgent new-hire access)
- [x] Verified Reporting Structure Enhancement (province/DM/AM) implemented in mu-plugin
- [x] Verified Weekly Manager Report email implemented in astra-child
- [x] Tomas Fischer AM filter gap → group-leadership union → deployed + browser-tested on prod
- [x] Mervin Leach locked-user email leak → `locked-user-emails.php` → deployed
- [x] GitHub push of those fixes (`main`, author Usman Zahid)
- [x] Haysboro / Watters Road lockout investigations + ticket comments

---

## 5. Production vs local

| Item | Notes |
|------|--------|
| Custom PHP | Keep local = production for mu-plugin + astra-child paths above |
| Uploads / SCORM | Local uploads often thin; production has media |
| DB | Local dump in USB zip; refresh before long offline work if Local can start |
| Redis / object cache | Disabled/local-adjusted in local `wp-config.php` |
| Deploy | Typically FileZilla / Nexcess to `…/wp-content/themes/astra-child` and `mu-plugins` |

---

## 6. How to work safely on the new laptop

1. Follow `SETUP-ON-NEW-LAPTOP.md` exactly (especially `DB_HOST` socket).  
2. Open this handoff first.  
3. For store lockouts: Security dashboard first, don’t rewrite LMS code.  
4. For report filter bugs: start in `manager-helpers.php` + `province-reporting.js`.  
5. For “still getting emails”: confirm `baba_user_locked`, then `locked-user-emails.php`.  
6. Commits: **Usman Zahid** `<uzahid@basecampconsulting.co>` only — never Cursor agent names.  
7. Don’t commit secrets: `wp-config.php` and `app/sql/` are gitignored; they live in the USB zip only.

---

## 7. Key people

| Person | Role |
|--------|------|
| Usman Zahid | Dev ownership / LMS + ticket fixes |
| Muhammad Basit Munir | Teammate / updates |
| Nikki Linton | Director, Retail Training (client) |
| Bhaumik Dhandhukia | Web Dev leadership |
| Junaid Hassan | Internal (status / presentation) |

---

## 8. Quick file map

```text
app/public/wp-content/
  mu-plugins/
    learacabana-group-province.php
    learacabana-group-province/   ← province + AM/DM filters
  themes/astra-child/
    functions.php
    inc/locked-user-emails.php    ← locked-user mail block
    custom-reports/               ← weekly emails
  plugins/
    ithemes-security-pro/         ← IP ban/lockout (Kadence)
    lock-user-account/            ← baba_user_locked (login only)
    sfwd-lms/                     ← LearnDash
    tin-canny-learndash-reporting/
```

---

## 9. Definition of “same as this machine”

A successful clone means:

- Same WordPress + plugins + mu-plugins + astra-child custom code  
- Same DB content (or documented dump)  
- Local site boots without fatal errors  
- Git `main` tracks GitHub with Usman Zahid authorship  
- New Cursor can continue **from this handoff**, not from a blank audit  

If anything differs, prefer fixing Local config (`wp-config` / import) over changing production-parity PHP.
