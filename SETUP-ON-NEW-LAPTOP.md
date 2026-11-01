# LearnCabana — Setup on a New Laptop (Same as This Machine)

This package is a **full local copy** of the Learn Cabana WordPress LMS that matches production code behavior. Use it so Cursor on the new laptop starts from the **same** codebase + database baseline.

## What is in the USB zip

| Path | Purpose |
|------|---------|
| `app/public/` | Full WordPress site (core, plugins, themes, mu-plugins) |
| `app/sql/local.sql.gz` | MySQL dump of database `local` (gzipped; dated **2025-09-18** — Local MySQL was stopped at package time so this is the latest on-disk dump) |
| `conf/` | LocalWP nginx/php/mysql conf (reference; Local recreates on import) |
| `wp-config.php` inside `app/public/` | Local credentials (DB host must be updated on new machine) |
| `CURSOR-HANDOFF.md` | Working analysis for the new Cursor agent |
| `SETUP-ON-NEW-LAPTOP.md` | This file |
| `.git/` | Git history (remote: `origin` → GitHub) |

**Production site:** `https://learncabana.com`  
**Local URL (this machine):** `http://learcabana.local`

---

## Requirements on the new laptop

1. [Local WP](https://localwp.com/) (formerly Local by Flywheel) installed  
2. Cursor installed  
3. Enough free disk (~8–10 GB recommended)  
4. GitHub access to `https://github.com/usman-hightide/learncabana.git` (optional if zip includes `.git`)

---

## Setup steps (do in order)

### A. Create a Local site shell

1. Open **Local**.
2. **Create a new site** named e.g. `learcabana`.
3. Preferred PHP/MySQL close to this machine (PHP 8.x, MySQL 8).
4. Note the site path Local creates (e.g. `~/Local Sites/learcabana`).

### B. Replace site files from the zip

1. **Stop** the site in Local.
2. Unzip the USB archive.
3. Replace the new site’s `app/public` with the zip’s `app/public` (overwrite).
4. Copy `app/sql/` into the new site folder as well.

### C. Import the database

1. Start the site in Local (so MySQL is running).
2. Open **Database** → Adminer / Sequel Ace / TablePlus, **or** use Local’s DB tools.
3. Import `app/sql/local.sql` (if `.gz`, decompress first: `gunzip local.sql.gz`).
4. Database name / user / password for Local are usually:
   - **DB name:** `local`
   - **User:** `root`
   - **Password:** `root`

### D. Fix `wp-config.php` on the new machine (critical)

Open `app/public/wp-config.php` and set `DB_HOST` to **this machine’s** Local MySQL socket (or TCP).

On Mac Local, it looks like:

```php
define( 'DB_HOST', 'localhost:/Users/YOURUSER/Library/Application Support/Local/run/SITEID/mysql/mysqld.sock' );
```

Find the real socket:

- Local → site → **Database** tab, or  
- Path under `~/Library/Application Support/Local/run/*/mysql/mysqld.sock`

Also set site URLs if needed (WP Admin → Settings, or WP-CLI):

```bash
wp option update home 'http://learcabana.local'
wp option update siteurl 'http://learcabana.local'
```

(Use your Local domain if different.)

### E. Open in Cursor

1. Open the Local site folder in Cursor.
2. Read **`CURSOR-HANDOFF.md` first** — do not reinvent completed work.
3. Prefer `git pull` from `origin/main` before new feature work.

### F. Verify sameness checklist

- [ ] Homepage / login loads on local domain  
- [ ] WP Admin login works  
- [ ] Reports Dashboard → User Report shows Province / DM / AM filters  
- [ ] `mu-plugins/learcabana-group-province*` present  
- [ ] `themes/astra-child/inc/locked-user-emails.php` present  
- [ ] `git log -1` shows latest commits (Usman Zahid)  
- [ ] No Cursor/agent name in git author  

---

## Do NOT change casually

These match production behavior:

- `wp-content/mu-plugins/learcabana-group-province*` — province + AM/DM report filters  
- `wp-content/themes/astra-child/inc/locked-user-emails.php` — block emails to locked users  
- `wp-content/themes/astra-child/custom-reports/*` — weekly learner/manager emails  

---

## Git / GitHub

```text
Remote: https://github.com/usman-hightide/learncabana.git
Branch: main
Author for commits: Usman Zahid <uzahid@basecampconsulting.co>
```

Never commit as a Cursor agent name. Prefer:

```bash
git commit --author="Usman Zahid <uzahid@basecampconsulting.co>" -m "message"
```

`.gitignore` excludes uploads, `wp-config.php`, `app/sql/`, `conf/`, `logs/` — the **USB zip** still includes them for full local parity.

---

## Notes / known limits

1. Local `uploads` may be incomplete vs production (SCORM packages often missing) — code/DB still match.  
2. If SQL dump date is older than “today”, start Local on the **source** machine, re-export DB, then re-zip before major new work.  
3. Production FTP/deploy remains separate; local zip is for development continuity only.
