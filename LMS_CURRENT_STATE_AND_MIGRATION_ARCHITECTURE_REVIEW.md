# LearnCabana LMS — Current State and Migration Architecture Review

**Document status: FINAL** — LearnCabana (this store) Step 1 audit  
**Document type:** Evidence-based technical audit (client Step 1)  
**Subject system:** Canna Cabana E-Learning — local production clone at `http://learcabana.local`  
**WordPress root:** `/Users/macbookpro/Local Sites/learcabana/app/public`  
**Client proposal reviewed:** *LMS rebuild — architecture & migration plan* (High Tide Inc. — Web Development, 8 pages)  
**Audit date:** 12 September 2026  
**Re-verified:** 14 September 2026 (DB + source). Theme lock: 14 September 2026.  
**Evidence base:** Updraft production backup of 11 September 2026 17:51 (database + plugins + themes + mu-plugins) restored locally. `wp-content/uploads` on this clone is **436 KB** — SCORM/xAPI packages, videos, and issued certificate PDFs are **not** here.  
**Classification key:** `[VERIFIED]` · `[PARTIALLY VERIFIED]` · `[UNVERIFIED]` · `[PROPOSED]` · `[RECOMMENDATION]` · `[BUSINESS DECISION REQUIRED]`

---

## 1. Executive Summary

This audit answers the client’s Step 1 request: catalog **what this WordPress + LearnDash store actually does**, especially custom completion, certificates, quiz logic, enrollment, and plugins that hook LearnDash — before any in-house multi-tenant LMS is designed.

**[VERIFIED] This local site is a usable production clone of LearnCabana** (one store: Canna Cabana E-Learning) for **code + database**. LearnDash 4.25.7, Astra Child, production plugins, and the production DB are present. **It is not file-complete:** `uploads/` is 436 KB, so Tin Canny/SCORM packages, videos, and issued PDFs cannot be opened here.

The most important findings (re-verified 14 Sep 2026):

1. **This repository is one store, not 228.** Single-site WordPress (not multisite). **No** `tenant_id`, **no** store registry, **no** CourseUpon in code or `wp_options`, **no** learner SSO/JWT/Keycloak. Those PDF items are **proposal assumptions**.
2. **Live SCORM/xAPI on this store is Tin Canny, not GrassBlade content.** **163 / 245** published lessons embed Gutenberg block `tincanny/content`. GrassBlade CPT `gb_xapi_content` = 1 draft; `show_xapi_content` meta is empty; `wp_grassblade_completions` = 0. GrassBlade’s `learndash_process_mark_complete` filter **is registered** but has nothing attached to gate. Tin Canny **calls** `learndash_process_mark_complete` when its content finishes (`Services.php`).
3. **Custom WP-side behavior (client Step 1):** Tin Canny auto-complete; Manual Completions (force complete + **100%** quiz writes — **682 users** have `m_edit_by` in `_sfwd-quizzes`); Manage Enrollment; Astra Child (enrollment-email suppress, EN/DE notification language gate, weekly reports, assignment download); Lock User Account (**3,943 / 6,290 locked**); LearnDash Notifications (welcome, assignment upload, renewal). Visibility Control is **enabled** but **0** posts/menus use its CSS classes.
4. **Learner history is large.** 6,290 users; course activity complete 93,271; lesson complete 238,788; quiz complete 4,822; Tin Canny reporting **4,986,608** rows; assignments published **28,503**; certificate template **3614** assigned on **125** published courses (not 163 — the extra rows only contain an empty certificate key).
5. **Uncanny Automator is not live** (10 recipes, all `draft`). Achievements table = 0. Can be retired unless reactivated.
6. **Do not lock Laravel.** Client left stack TBD. Extractor can be PHP/WP-CLI either way.

**[RECOMMENDATION]** Treat LearnCabana as **Store 0 / reference tenant** for the extraction schema. Do not start the multi-tenant build until CourseUpon API contracts, SSO existence, and whether the other 227 stores even exist as independent WP installs are confirmed outside this codebase.

---

## 2. Client Request Interpretation

The client provided the 8-page architecture PDF and this instruction (paraphrased from the engagement):

> Research an in-house LMS. Deep-analyze custom things built on the WP side. The document starts the architecture review. Not in favor of Laravel; leave stack TBD.

| Item | Source | Label |
|---|---|---|
| Replace WP + LearnDash LMS functions with an in-house LMS | PDF §1 | `[PROPOSED]` |
| Keep CourseUpon for authoring; new platform owns enrollment, progress, certificates, reporting | PDF §1 | `[PROPOSED]` — CourseUpon **not present** in this store |
| Centralize 228 stores into one multi-tenant LMS | PDF §1 | `[PROPOSED]` — **not this repository** |
| Migrate historical learner data (users, enrollments, progress, certificates) | PDF §1 / §4 | `[PROPOSED]` — **this store has that data** `[VERIFIED]` |
| Account for minor LearnDash customizations | PDF §1 | **This document catalogs them** |
| Course/lesson *content* does not migrate (rebuilt in CourseUpon) | PDF §4 | `[PROPOSED]` — `[BUSINESS DECISION REQUIRED]` for this store (161 published courses + 28k assignments exist) |
| Step 1 — Audit custom completion, certificates, quiz logic, plugins | PDF §4 | **This document** |
| Do not lock Laravel | Client message | Honored |

This review treats the PDF as a **proposal**, not as a description of the current system.

### 2.1 Client Step 1 catalog (this store only)

PDF Step 1: *“Catalog the customizations already present per store (custom completion rules, custom certificate templates, custom quiz logic, any custom plugins hooking into LearnDash).”*

| PDF item | This store (LearnCabana) | Confidence |
|---|---|---|
| Custom completion rules | **Tin Canny** auto-marks the LD step when embedded `tincanny/content` finishes. **GrassBlade filter exists** but no GrassBlade content is attached, so it does not currently block. **Manual Completions** can force-complete and bypass the GrassBlade filter. | VERIFIED |
| Custom certificate templates | **One** template: “Certificate of Achievement” (post **3614**). Certificate Builder generates PDF via `learndash_tcpdf_init`. **125** published courses reference 3614 in `_sfwd-courses`. No Astra Child PDF override. Issued PDF files: **not on this clone**. | VERIFIED (template/map); PARTIALLY VERIFIED (files) |
| Custom quiz logic | **No** theme scoring formula. Manual Completions writes **score=100, percentage=100** and fires `learndash_quiz_completed`. **682** users have at least one `m_edit_by` attempt. ProQuiz native stats also exist (4,900 statistic_ref). | VERIFIED |
| Custom plugins hooking LearnDash | GrassBlade, Tin Canny, Manage Enrollment, Manual Completions, Visibility Control, LearnDash Notifications, Certificate Builder, Achievements (unused), Design Upgrade (CSS only), Astra Child filters listed in §8. WPComplete / Content Control: **no** `learndash_` hooks. Automator: integration exists, recipes all draft. | VERIFIED |
| Also present (not named in Step 1 but affects migration) | Force Login; Lock User (`baba_user_locked`); WPML EN/DE; ACF `course_language` / `job_titles` / `supervisor` / `expiry_date`; **144 closed + 17 free** published courses; **16** courses with non-empty prerequisites; 302 groups | VERIFIED |

---

## 3. Scope of Audit

**In scope**

- Local production clone: `/Users/macbookpro/Local Sites/learcabana/app/public`
- Production database imported from Updraft `backup_2026-09-11-1751_…-db.gz`
- Production plugins, themes (`astra` active parent, `astra-child` active child; `kadence` is **installed but not active**), mu-plugins
- Custom LMS plugins and Astra Child source
- WP-CLI / MySQL row counts and settings
- Client PDF *LMS rebuild — architecture & migration plan*

**Out of scope / not available**

- Production `uploads/` (436 KB locally) — Tin Canny packages, videos, certificate PDFs
- Live CourseUpon account / API
- The other 227 stores named in the PDF
- Legal certificate retention rules, HR policy for locked users
- Team skillset / hosting contract for the future platform

**Rule:** If a behavior cannot be proven from this clone, it is marked `NOT VERIFIED FROM AVAILABLE SOURCE`.

---

## 4. Evidence & Methodology

### 4.1 Method

1. Restore production Updraft backup (DB + plugins + themes + mu-plugins) onto LocalWP; remap URLs to `http://learcabana.local`.
2. Inventory filesystem, `wp-config.php`, themes, plugins, mu-plugins.
3. Search the tree for CourseUpon, tenant/SSO/JWT/Keycloak, LearnDash hooks, xAPI/SCORM, enrollment, completion, certificates.
4. Deep-read custom plugins and `astra-child`.
5. Query MySQL / WP-CLI for post types, users, roles, activity, custom tables, options.
6. Separate current-state evidence from the client proposal and from recommendations.

### 4.2 Primary evidence sources

| Source | Artifact | Use |
|---|---|---|
| Production WP version (dump header) | Updraft SQL header | WP 6.8.6, PHP 8.3.8, MariaDB 10.5.22 on Nexcess |
| Local core | `wp-includes/version.php` | LocalWP is WordPress **7.0.2** (DB upgraded locally) |
| LearnDash | `plugins/sfwd-lms/sfwd_lms.php` | Version **4.25.7**, active |
| Runtime | LocalWP `learcabana.local`, PHP 8.2.29, MySQL 8.4 | Clone host |
| Theme | `themes/astra-child/` | Active stylesheet `astra-child` |
| Custom LMS plugins | GrassBlade, Manage Enrollment, Manual Completions, Visibility Control | Completion / enrollment |
| Tin Canny | `tin-canny-learndash-reporting` + `wp_uotincan_*` | xAPI history |
| Client PDF | `/Users/macbookpro/Downloads/LMS_Refactor_2.0.pdf` | Proposed future architecture |

### 4.3 Confidence model

- **VERIFIED** — file, hook, table, or query result from this clone.
- **PARTIALLY VERIFIED** — code exists but live use is thin or uploads are missing.
- **UNVERIFIED** — no direct evidence in this store.

---

## 5. Current System Architecture

### 5.1 [VERIFIED] Current architecture of LearnCabana (this store)

```
Browser
  → nginx (LocalWP) / Apache (production Nexcess LearnDash Cloud)
  → PHP 8.2.29 local / 8.3.8 production
  → WordPress (local core 7.0.2; production dump 6.8.6)
       → Theme: Astra + Astra Child
       → LearnDash 4.25.7 (LD30 theme)
       → GrassBlade 6.2.12 + Tin Canny reporting
       → Custom admin plugins (enroll / force-complete / visibility)
       → WPML EN + DE
       → Force Login + Theme My Login
       → MySQL / MariaDB, prefix wp_, single site
```

There is **no** application layer besides WordPress. There is **no** CourseUpon sync service. There is **no** Next.js learner portal. There is **no** PostgreSQL tenant schema. There is **no** JWT issuer for learners.

### 5.2 [VERIFIED] Production hosting shape

| Fact | Evidence |
|---|---|
| Public site | `https://learncabana.com` — Force Login 302 → `/login/` |
| Host | Nexcess / LearnDash Cloud (`797d70a188.nxcli.io`) |
| MU plugin | `mu-plugins/learndash-cloud.php` v1.0.18 |
| Production cache | Redis (Object Cache Pro) + Cache Enabler — **disabled on local** |
| Languages | WPML `en` + `de`, directory negotiation |

### 5.3 [PROPOSED] Client future architecture (PDF — not current)

228 store frontends → one self-hosted LMS (API + queue + object storage) ← CourseUpon authoring; one-time LearnDash export; Next.js learner portal; SSO/JWT; PostgreSQL + Redis.

That is the **target proposal**, not LearnCabana today.

---

## 6. WordPress Architecture

| Item | Value | Confidence |
|---|---|---|
| WordPress root | `/Users/macbookpro/Local Sites/learcabana/app/public` | VERIFIED |
| Production WP (dump) | 6.8.6 | VERIFIED |
| Local WP core | 7.0.2 | VERIFIED |
| PHP | 8.2.29 local / 8.3.8 production | VERIFIED |
| DB | MySQL 8.4 local; MariaDB 10.5.22 production dump; name `local`; prefix `wp_` | VERIFIED |
| Multisite | No | VERIFIED |
| Active theme | **Astra Child** (`astra-child` 4.1.6.1688662550), parent **Astra**. Same on production dump (`stylesheet` / `template`) and local Appearance screen. Kadence + default Twenty* themes are installed, **not active**. | VERIFIED |
| Table prefix | `wp_` | VERIFIED |
| Environment | `WP_ENVIRONMENT_TYPE = local` | VERIFIED |
| Project Composer / npm / Docker / CI at site root | Absent | VERIFIED |
| Local drop-ins | No `object-cache.php` / `advanced-cache.php` | VERIFIED |

### 6.1 `wp-config.php` constants that matter

Local file keeps production key **names** (`WP2FA_ENCRYPT_KEY`, `ITSEC_ENCRYPTION_KEY`) so imported settings can decrypt. Local overrides: `WP_CACHE=false`, `WP_REDIS_DISABLED=true`, `DISABLE_WP_CRON=true`, `WP_HOME`/`WP_SITEURL`=`http://learcabana.local`. Secrets are **not** repeated here.

---

## 7. Plugin Inventory

Local Plugins screen after restore: **71 items** (matches production). After local hardening, cache/2FA/security plugins were deactivated so the clone stays usable.

### 7.1 Active plugins (local, after restore hardening)

LearnDash / LMS-critical: `sfwd-lms` 4.25.7, `grassblade` 6.2.12, `tin-canny-learndash-reporting`, `manage-enrollment-learndash`, `manual-completions-learndash`, `visibility-control-for-learndash`, `learndash-notifications`, `learndash-certificate-builder`, `learndash-achievements`, `design-upgrade-learndash`, `design-upgrade-pro-learndash`.

Identity / access: `wp-force-login`, `theme-my-login`, `lock-user-account`, `content-control`, `user-role-editor`, `user-switching`.

Content / UI: `advanced-custom-fields`, `acfml`, `sitepress-multilingual-cms`, `wpml-string-translation`, `astra-sites`, `ultimate-addons-for-gutenberg`, `presto-player`, `wpcomplete`.

Ops (not learner logic): Fluent Forms, WPForms Lite, WP Mail SMTP Pro, UpdraftPlus, WPVivid, Backuply, All-in-One WP Migration, File Manager, Crontrol, When Last Login, TrustedLogin, GPT3 AI, Adminer, Media Cleaner, Chart/TOC blocks, avatars, Reveal IDs, Uncanny Automator (recipes all draft).

**MU-plugins:** `learndash-cloud` 1.0.18; Adminer conflict shim.

### 7.2 Intentionally inactive on local (still in the tree)

`object-cache-pro`, `cache-enabler`, `ithemes-security-pro`, `wp-2fa`, plus unused ops plugins (Branda, Classic Editor, Course Grid, LearnDash Hub, WP Activity Log, etc.).

Deactivating Redis/2FA/iThemes is a **local safety choice**. Production still uses those.

### 7.3 Plugin-by-plugin LMS analysis

See §§8–18. Summary of criticality:

| Plugin | Learner-critical? | Migration |
|---|---|---|
| LearnDash core | Yes | Replace; extract data |
| GrassBlade + Tin Canny | Yes (xAPI gate + 4.9M rows) | Critical |
| Manage Enrollment | Admin ops | Reimplement |
| Manual Completions | Admin ops + dirty quiz history | Critical |
| Visibility Control | Code yes; **0 content uses classes** | Likely retire |
| LearnDash Notifications | Yes (9 notifications, EN/DE) | Rebuild |
| Certificate Builder + 1 template | Yes | Rebuild renderer + keep template meaning |
| Achievements | Table empty | Retire unless re-enabled |
| Uncanny Automator | 10 draft recipes | Retire unless published |
| Force Login / TML / Lock User | Yes | Reimplement auth |
| ACF + WPML | Yes (language + org fields) | Map fields |
| Design Upgrade | Visual | Retire with WP |

---

## 8. Custom Code Inventory

**[VERIFIED]** There is no separate first-party app. House customizations live in **Astra Child** plus third-party GrassBlade/Next Software plugins.

| Component | File | Hook | Purpose | Data | Migration | Confidence |
|---|---|---|---|---|---|---|
| GrassBlade gate | `grassblade/addons/learndash/functions.php` `grassblade_learndash_process_mark_complete` | `learndash_process_mark_complete` | Block LD complete until xAPI done | Completions | Reimplement or drop | VERIFIED |
| GrassBlade outbound xAPI | same | `learndash_*_completed` | Statements to LRS | LRS / Tin Canny | Retain LRS history | VERIFIED |
| Manage Enrollment | `manage-enrollment-learndash/functions.php` | AJAX enroll/unenroll | Bulk `ld_update_course_access` / `ld_update_group_access` | Access meta | Reimplement admin | VERIFIED |
| Manual Completions | `manual-completions-learndash/functions.php` | AJAX mark complete | Force complete; quiz **100%** + `learndash_quiz_completed` | `_sfwd-quizzes`, activity | Detect `m_edit_by` | VERIFIED |
| Visibility CSS | `visibility_control_for_learndash.php` | `wp_head` | Hide DOM by course/role | None | Unused in posts | VERIFIED code; unused content |
| Force Login | `wp-force-login.php` | `template_redirect` | Site-wide login wall | None | Portal auth | VERIFIED |
| Lock User | `lock-user-account.php` | `wp_authenticate_user` | Block if `baba_user_locked=yes` | 3,943 users | **Must migrate flag** | VERIFIED |
| Suppress enroll email | `astra-child/functions.php` `disable_course_enrollment_email` | `learndash_email_send_on_user_enrolled` | Always false | None | Recreate policy | VERIFIED |
| Language-gate notifications | `lc_learndash_send_notification` | `learndash_notifications_send_notification` prio 9999 | User locale vs `course_language` vs `notification_language` | Emails | Reimplement | VERIFIED |
| Weekly reports | `custom-reports/custom-learning-reports.php` | cron `send_learn_dash_weekly_report` | Learner + manager emails | `job_titles`, `supervisor` | Rebuild reporting | VERIFIED |
| Profile course order | `astra-child/learndash/ld30/shortcodes/profile.php` | template override | Sort by `learndash_course_order` | post meta | Portal catalog | VERIFIED |
| Temp password / first login | `send_temp_password`, `force_password_change_on_first_login` | `user_register`, `wp_login` | Onboarding | usermeta | Recreate if kept | VERIFIED |
| Assignment download | `ld_download_assignment` | `init` | Group-leader file access | assignments | Rebuild | VERIFIED |
| Hide unused roles | `hide_unused_roles` | `editable_roles` | Only subscriber, group_leader, administrator, sub_admin | UI | Role map | VERIFIED |
| Login redirect | `admin_default_page` | `login_redirect` | → `/my-courses` | None | Portal home | VERIFIED |
| Force Login bypass | `my_forcelogin_bypass` | `v_forcelogin_bypass` | Allow lost/reset password pages | None | Auth exceptions | VERIFIED |

Broken / dead code (do not migrate as features):

- `get_locked_users()` is **called but not defined** (`lms_email_recipients` filter is inert).
- `manage_enrollment_learndash_course_selected` AJAX registered with **no handler**.
- `create_users()` in Manage Enrollment is **empty**.
- Commented `wp_course_enrollment_schedular` insert — table **does not exist**.
- Notification filter at priority 999 is `__return_true`; language filter at 9999 wins.

---

## 9. LearnDash Architecture

**[VERIFIED]** LearnDash 4.25.7, LD30 theme active.

| Object | Publish | Other | Notes |
|---|---|---|---|
| Courses `sfwd-courses` | 161 | 2 draft (163 total) | Published ACF `course_language`: **english 156 / german 11**. Access: **144 closed, 17 free**. **16** courses have a non-empty prerequisite list; **13** have prerequisite enabled. |
| Lessons `sfwd-lessons` | 245 | 139 draft | **163 published** embed `tincanny/content` |
| Topics `sfwd-topic` | 14 | — | Thin topic use |
| Quizzes `sfwd-quiz` | 24 | 149 draft | Most quizzes unpublished |
| Questions `sfwd-question` | 640 | — | |
| Groups | 302 | 7 draft/private | Org / store grouping |
| Certificates | 1 | — | “Certificate of Achievement” (ID 3614) |
| Assignments | 28,503 | 67 other | Heavy operational use |
| Essays | 1 graded | — | Negligible |
| Notifications `ld-notification` | 9 | 2 draft | EN/DE pairs |
| xAPI CPT `gb_xapi_content` | 0 | 1 draft | GrassBlade CPT almost unused |

APIs in use: `ld_update_course_access`, `ld_update_group_access`, `sfwd_lms_has_access`, `learndash_process_mark_complete`, `learndash_update_user_activity`, `learndash_quiz_completed`.

Meta keys: `course_{id}_access_from` (2,152 rows), `course_completed_%` (93,790), `learndash_group_users_%` (4,140), `_sfwd-quizzes` (3,591 users).

163 course records contain the `_sfwd-courses` serialized blob (always includes a certificate **key**). **125 published courses** actually reference certificate post **3614**. 37 blobs have an empty certificate value.

---

## 10. Custom LearnDash Functionality

This is the catalog the PDF Step 1 asked for.

### 10.1 Custom completion — [VERIFIED]

**A. Tin Canny (live automatic path on this store)**

**163 / 245** published lessons contain Gutenberg block `tincanny/content` (`contentId=…`). Tin Canny `Services.php` calls `learndash_process_mark_complete(…, true)` when module settings are `remove` or `autoadvance`. That is the completion rule learners actually hit for SCORM/xAPI modules. Package files for those `contentId`s live in **uploads** (missing on this clone). Statement history: `wp_uotincan_reporting` 4,986,608; `wp_uotincan_quiz` 472,864.

**B. GrassBlade gate (code present, not attached)**

```
learndash_process_mark_complete
  → grassblade_learndash_process_mark_complete
  → grassblade_xapi_content::post_contents_completed
     → if GrassBlade xAPI attached and incomplete: return false (block)
     → if complete or no GrassBlade content: allow
```

Live attachment: `gb_xapi_content` = 1 draft; `[grassblade]` shortcode = 0 posts; `show_xapi_content` nonempty = 0; `wp_grassblade_completions` = 0. **Do not treat GrassBlade as the current completion gate.** Keep the filter in the catalog because it would activate if content were attached, and Tin Canny’s mark-complete still runs through it (no-op today).

**C. Manual Completions (admin path)**

Admins can complete/incomplete course, lesson, topic, quiz. `force_completion` **removes** the GrassBlade filter. Quiz path writes score/percentage **100**, sets `m_edit_by` / `m_edit_time`, updates activity, fires `learndash_quiz_completed`. **682 users** have `m_edit_by` inside `_sfwd-quizzes` — this ran in production, not only in theory.

**D. Standard LearnDash** still records `wp_learndash_user_activity` (course 93,271 complete / 6,518 incomplete; lesson 238,788 / 5,092; quiz 4,822 / 347).

### 10.2 Custom certificates — [VERIFIED]

- One template: **Certificate of Achievement** (`sfwd-certificates` ID 3614).
- Certificate Builder plugin present; no Astra Child PDF override.
- **125 published courses** reference 3614. All 163 `_sfwd-courses` blobs contain a certificate *key*; 37 are empty.
- Issued PDF files would be in **uploads** (not restored).

### 10.3 Custom quiz logic — [VERIFIED]

- No theme-level scoring formula.
- Manual Completions fabricates 100% attempts (**682 users** with `m_edit_by`).
- GrassBlade can write quiz scores from xAPI **if** content is attached (not attached here). Tin Canny quiz statements: 472,864 rows.
- ProQuiz: 644 questions, 4,900 statistic_ref rows.
- 24 published quizzes vs 149 drafts — many quizzes are unused or WIP.

### 10.4 Custom enrollment — [VERIFIED]

- Published courses: **144 closed, 17 free** — closed means enrollment is not self-serve checkout.
- Primary write path: LearnDash + **Manage Enrollment** (`ld_update_course_access` / `ld_update_group_access`).
- Group enrollment is the scale path: 302 groups, 4,140 group-user meta rows vs 2,152 direct course access rows.
- **16** courses have a non-empty prerequisite list; **13** have the prerequisite flag enabled.
- Native LearnDash enrollment emails **suppressed** in Astra Child (`learndash_email_send_on_user_enrolled` → false). **Welcome / Willkommen** still send via LearnDash Notifications (`enroll_course` trigger) unless the language gate blocks them.
- Commented scheduler table never shipped.

### 10.5 Custom notifications — [VERIFIED]

| ID | Title | Status | Trigger meta |
|---|---|---|---|
| 7219 / 37155 | Welcome / Willkommen | publish | `enroll_course` |
| 40062 / 40166 | User uploads an assignment / DE translation | publish | `upload_assignment` (DE row has NULL trigger — WPML duplicate) |
| 3631 / 37137 | Respect in the Workplace renewal / DE | publish | `lesson_available` |
| 4875 / 37139 | Provincial License Renewal / DE | publish | `0` / NULL — confirm mapping before rebuild |
| 5589 | Course Enrolment | draft | `enroll_course` |

Language gate (`lc_learndash_send_notification`, priority 9999): sends only when user language, `course_language`, and `notification_language` match. Empty `locale` is treated as **english**. A redundant `__return_true` at priority 999 is overwritten. Cron: `learndash_notifications_cron` twicedaily.

### 10.6 Custom reporting — [VERIFIED]

Weekly cron `send_learn_dash_weekly_report`: emails every **unlocked subscriber** a progress digest; emails Store/Area/District managers a rollup of users whose `supervisor` meta points at them. Uses ACF `due_date`/`expiry_date`, `learndash_user_get_course_progress`. Snapshots stored in `lc_manager_stats` / `lc_manager_groups_stats`.

---

## 11. Enrollment & Access Control

| Mechanism | How it works | Live use |
|---|---|---|
| Course price type | `_sfwd-courses` `course_price_type` | **144 closed, 17 free** (all 161 published) |
| Prerequisites | serialized course settings | **16** non-empty lists; **13** enabled |
| Course access meta | `course_{id}_access_from` | 2,152 rows |
| Group access | `learndash_group_users_%` | 4,140 rows; 302 groups |
| LearnDash `sfwd_lms_has_access` | Core check used by Visibility + reports | VERIFIED |
| Manage Enrollment UI | Admin AJAX, `manage_options` | Process exists |
| Force Login | Entire front end requires login | VERIFIED (homepage 302) |
| Lock User | `baba_user_locked=yes` | **3,943 users (63%)** |
| Content Control plugin | Generic restriction rules | No LD hooks; 0 `cc_restriction` posts |
| Visibility Control | CSS hide; plugin option enabled (`1`) | **0** posts, **0** menus, **0** theme classes |
| Open registration | `users_can_register=0` | Invite/admin create only |

**[RECOMMENDATION]** New LMS must model **group-based enrollment + locked accounts** or the imported population will be wrong. Do not email locked users (theme already skips them in weekly reports).

---

## 12. User / Roles / Identity

| Role (approx.) | Count | Notes |
|---|---|---|
| subscriber | 5,650 | Learners |
| group_leader | 569 + 33 combined | Store/group managers |
| administrator | 15 | `wp_capabilities` contains administrator |
| sub_admin | 22 | Custom role; not defined in theme PHP (User Role Editor / DB) |
| Total users | 6,290 | |
| Locked | 3,943 | `baba_user_locked=yes` |

**Job titles [VERIFIED]** (`job_titles` meta, 5,519 users): Shift Leader 3,295; Sales Associate 933; Store Manager 470; Corporate 396; Assistant Store Manager 381; Area Manager 29; District Manager 7; plus a few MIT / warehouse / driver.

**Supervisor graph:** 4,270 users have `supervisor` set — this **is** the store hierarchy, not a `tenant_id`.

**Locales [VERIFIED]:** `locale` usermeta — empty **4,576**, `en_US` **1,712**, `de_DE` **2**. WPML `icl_admin_language` — `en` **2,771**, `de` **2**. Notification gate maps only `de_DE` → german; **empty locale is treated as english**. WPML site languages **en + de**.

**No learner SSO.** Login is WordPress users + Theme My Login (`/login/`, `/anmelden/`). GrassBlade `GET /wp-json/grassblade/v1/sso_auth` is **LRS admin** only. iThemes JWT is security library, not learner auth. Keycloak / OAuth learner flow: **NOT FOUND**.

Onboarding: `user_register` generates a temp password email; first `wp_login` can force profile password change. Login redirect → `/my-courses`.

---

## 13. Course / Lesson / Topic / Quiz Model

Standard LearnDash CPT graph. Topics are barely used (14). Quizzes are mostly draft (24 publish / 149 draft). Courses exist in **English and German pairs** (e.g. “Schritt 1: Kultur und Compliance” and “(English)” variants). ACF `course_language` = `english` | `german`.

Profile template sorts enrolled courses by `learndash_course_order`. Extra Courses page (`all-courses` / `zusaetzliche-kurse`) is the catalog.

**[PROPOSED] PDF says course content will be rebuilt in CourseUpon and should not migrate.** For this store that would discard 161 published courses, 245 lessons, 640 questions, and 28,503 assignments. That is a business decision, not a technical default.

---

## 14. Quiz System

| Item | Value |
|---|---|
| Published quizzes | 24 |
| Draft quizzes | 149 |
| Questions (CPT) | 640 |
| ProQuiz questions table | 644 |
| Users with `_sfwd-quizzes` | 3,591 |
| Activity quiz complete / incomplete | 4,822 / 347 |
| ProQuiz statistic_ref | 4,900 |
| Tin Canny quiz rows | 472,864 |
| Custom scoring in theme | None |
| Admin override | Manual Completions → 100% + `learndash_quiz_completed`; **682** users have `m_edit_by` |

**[RECOMMENDATION]** Extraction must keep raw ProQuiz statistics **and** flag rows with `m_edit_by` as admin-forced.

---

## 15. Completion System

Three layers, in order:

1. **xAPI/SCORM (GrassBlade / Tin Canny)** can block or auto-complete a step.
2. **LearnDash native** mark-complete + activity table.
3. **Admin force** via Manual Completions (bypasses 1).

Activity totals (status 1 = complete): course 93,271; lesson 238,788; topic 14; quiz 4,822; group_progress 2,553.

Course-completed usermeta: 93,790 keys (aligns with activity).

---

## 16. Certificate System

| Item | Evidence |
|---|---|
| Templates | 1 — Certificate of Achievement |
| Builder plugin | Active |
| Theme override | None |
| Courses referencing certificate | **125** published courses contain `3614` in `_sfwd-courses` (163 blobs have the key; 37 empty) |
| Issued PDF files | In uploads — **not on this clone** |
| Achievements table | 0 rows — not used |

Migration: keep template meaning + course→certificate map; PDFs need uploads or regenerate after import.

---

## 17. GrassBlade / xAPI / SCORM / Tin Canny

| Store / artifact | Rows / count | Meaning |
|---|---|---|
| Gutenberg `tincanny/content` | **163 published lessons**, 8 published courses | **Live embed.** `contentId` points at packages in uploads |
| `wp_uotincan_reporting` | 4,986,608 | Tin Canny xAPI statement log |
| `wp_uotincan_quiz` | 472,864 | xAPI quiz |
| Tin Canny `learndash_process_mark_complete` | `Services.php` ~508–674 | Auto-complete LD step on module finish |
| `wp_grassblade_completions` | 0 | Unused |
| `wp_grassblade_scorm_data` | 0 | Unused |
| `gb_xapi_content` posts | 1 draft | GrassBlade CPT unused |
| `[grassblade]` in post_content | 0 | No GrassBlade shortcode |
| `show_xapi_content` nonempty | 0 | No GrassBlade attach-to-lesson meta |

**[VERIFIED]** Tin Canny is the SCORM/xAPI runtime on this store. **[PARTIALLY VERIFIED]** package files (uploads). GrassBlade remains installed and hooked; it is not the content host.

GrassBlade LRS SSO is admin-only. No Keycloak.

---

## 18. Automations

| System | Live? | Evidence |
|---|---|---|
| Uncanny Automator | **No** | 10 `uo-recipe` posts, all `draft` |
| LearnDash Notifications | **Yes** | 9 published + cron |
| Weekly custom reports | **Yes** | `send_learn_dash_weekly_report` weekly |
| Action Scheduler | Yes | Plugin/ops queues |
| `send_email_event` | Scheduled far-future one-offs | License/expiry style events |

Do not design the new LMS around Automator unless recipes are published.

---

## 19. ACF / Custom Fields

| Field | Used on | Purpose |
|---|---|---|
| `course_language` | Courses | `english` / `german` — notification gating |
| `notification_language` | Notifications | Matches user + course language |
| `learndash_course_order` | Courses | Profile sort |
| `job_titles` | Users | Shift Leader, Store Manager, … |
| `supervisor` | Users | Manager report rollup |
| `expiry_date` | Users (462 set) | License / due date in reports |
| `province` | Groups | Provincial license notifications |
| `group_type` | Groups | Classification (values mostly empty) |

These fields **are** the store’s org and compliance model. They must appear in any extraction schema.

---

## 20. Frontend / Theme / Templates

**[VERIFIED — production and local are the same]** Active theme is **Astra Child**, parent **Astra**. Confirmed by:

1. Production database options imported from Updraft: `stylesheet=astra-child`, `template=astra`
2. Local Appearance → Themes (14 Sep 2026): **Active: Astra Child**; Astra, Kadence, Twenty Twenty-One/Two/Three installed and inactive

House LMS UI customizations are in `astra-child` (`functions.php`, `custom-reports/*`, `learndash/ld30/shortcodes/profile.php`, `custom-user-table.php`). Kadence is unused for this store.

- Published pages: Home/Startseite, Log In/Anmelden, Help/Hilfe, News, My Progress / Mein Fortschritt, Extra Courses, Reports Dashboard (EN/DE).
- Design Upgrade plugins style LD30.
- Presto Player present (video); files in uploads — missing locally.
- Force Login + TML themed login (red branded page).

---

## 21. REST / AJAX / APIs / Integrations

| Endpoint / AJAX | Auth | Purpose |
|---|---|---|
| `manage_enrollment_learndash_*` | `manage_options` | Bulk enroll |
| `manual_completions_learndash_*` | `manage_options` | Force complete |
| `grassblade_completion_tracking`, `grassblade_xapi_track` | user / nopriv | xAPI track |
| `grassblade_scorm`, `grassblade_scorm_commit` | session | SCORM |
| `GET /wp-json/grassblade/v1/sso_auth` | admin cap | LRS SSO token |
| `GET /wp-json/grassblade/v1/get_user_meta` | LRS | User export to LRS |
| Force Login `rest_authentication_errors` | anonymous blocked | Site lock |
| CourseUpon API | — | **NOT FOUND** |
| Learner JWT/OAuth | — | **NOT FOUND** |

WP Mail SMTP Pro sends mail. Fluent Forms / WPForms have **no LearnDash hooks** in source.

---

## 22. Cron / Background Processing

Notable LMS crons in `wp_options.cron`:

- `send_learn_dash_weekly_report` — weekly (Astra Child)
- `learndash_notifications_cron` — twicedaily
- Action Scheduler / Automator health (no live recipes)
- Hosting: `learndashcloud_daily_ocp_plugin_migration`

Local `DISABLE_WP_CRON=true` — reports will not fire on the clone unless cron is enabled.

---

## 23. Database / Data Model

**Scale [VERIFIED]**

| Entity | Count |
|---|---|
| Users | 6,290 |
| Usermeta | 689,377 |
| Posts | 40,092 |
| LearnDash activity (all types) | ~356k+ |
| Tin Canny reporting | 4,986,608 |
| Assignments published | 28,503 |

**LMS tables present:** `wp_learndash_user_activity(_meta)`, full `wp_learndash_pro_quiz_*`, `wp_uotincan_*`, `wp_grassblade_*` (empty), `wp_ld_notifications_delayed_emails` (0), `wp_ld_achievements` (0), `wp_ld_course_time_spent`, `wp_ld_quiz_entries`, `wp_ld_time_entries`, Uncanny `wp_uap_*`.

No `tenant_id` column. No CourseUpon tables. Not multisite.

---

## 24. Security Review

Observations for **migration planning**, not a pentest:

- Force Login + REST lock: site is private. Good for retail staff LMS; must be recreated.
- 3,943 locked accounts — treat as terminated/inactive staff; do not activate them in a new portal by default.
- Manual Completions + `manage_options` can fabricate completions.
- Visibility Control is CSS-only (not authorization). Unused in content.
- iThemes Security + WP 2FA exist on production; disabled locally only.
- File Manager / Adminer / user switching are high-privilege ops tools — do not copy into the new LMS.
- Assignment download in theme is a custom file-access path; re-review on rebuild.
- Production `debug.log` was multi-GB — operational risk, not a feature.

---

## 25. 228-Store / Multi-Tenant Considerations

| PDF claim | This store |
|---|---|
| 228 independent WP + LearnDash installs | **Cannot be proven here.** This is **one** single-site install. |
| Not a shared multisite | **True for this store.** |
| Every table scoped by `tenant_id` | **Not how this store works.** Hierarchy is `groups` + `supervisor` + `job_titles` + `province`. |
| SSO into each store frontend | **Not present.** Learners log into this WP. |

**[RECOMMENDATION]** Ask the business for a **store inventory** (URLs, hosts, whether they are really 228 WP sites). Until then, design extraction for **one tenant = LearnCabana**, with groups mapping to physical retail stores *inside* this tenant.

---

## 26. Migration Data Inventory

What the PDF says must move (users, enrollments, completion, quiz attempts, certificates) — **this store has all of it**:

| Domain | Extract | Notes |
|---|---|---|
| Users | `wp_users` + roles | 6,290 |
| Locked flag | `baba_user_locked` | 3,943 |
| Org | `job_titles`, `supervisor`, `province`, `expiry_date` | ACF/usermeta |
| Locale | `locale` / WPML | EN/DE |
| Enrollment | `course_*_access_from`, group users, activity `access` | Prefer groups |
| Progress | `wp_learndash_user_activity` + `course_completed_%` | |
| Quizzes | `_sfwd-quizzes`, ProQuiz statistic*, Tin Canny quiz | Flag `m_edit_by` |
| xAPI | `wp_uotincan_reporting` | 5M rows — batch |
| Certificates | Template 3614 + course map + PDFs in uploads | |
| Assignments | 28,503 posts + files | If compliance needs them |
| Notifications config | 9 LD notifications + ACF language | Rebuild, don’t dump HTML blindly |
| Courses/lessons | 161 / 245 | Only if content is **not** rebuilt in CourseUpon |

---

## 27. Migration Mapping (LearnCabana → proposed tenant schema)

| Current | Proposed PDF entity | Transform |
|---|---|---|
| Site `learncabana.com` | `tenant_id` (single tenant) | Assign one UUID |
| `wp_users.ID` | `users.external_id` | Stable key |
| `wp_capabilities` | roles | Map subscriber→learner, group_leader→manager, sub_admin→ops |
| `baba_user_locked` | `users.status=locked` | Do not auto-invite |
| `learndash_group_users_*` / groups CPT | org unit / cohort | 302 groups ≈ stores/teams |
| `supervisor` | manager_user_id | Resolve to user IDs |
| `job_titles` | job_title enum | Keep raw strings first |
| `course_*_access_from` | enrollments | source=direct |
| Group course enroll | enrollments | source=group |
| `learndash_user_activity` | progress_events | Keep timestamps + status |
| `_sfwd-quizzes` / statistic | quiz_attempts | Keep score; tag forced |
| `uotincan_reporting` | xapi_statements | Archive or summarize |
| Certificate post 3614 | certificate_template | One template |
| ACF `course_language` | course.locale | en / de |
| CourseUpon | content source | **No mapping — does not exist here** |
| JWT/SSO | identity | **Must be newly built** |

---

## 28. Migration Risks

1. **Treating this store as if CourseUpon already authors it** — it does not. Content rewrite is a project, not a given.
2. **228-store wave plan with no inventory** — cannot schedule waves from this repo.
3. **Locked users imported as active** — 63% of accounts.
4. **Forced 100% quizzes** mixed with real attempts.
5. **5M xAPI rows** — import time, storage, PII.
6. **Assignments / videos missing from this clone** — files still on production uploads.
7. **EN/DE course pairs** — must not double-enroll or double-notify.
8. **Group vs direct enrollment** — two sources of truth.
9. **Automator assumed live** — it is not.
10. **Laravel lock-in** against client TBD.
11. **Certificate PDFs** not in the clone.
12. **Manual Completions + notifications** — force-complete can email learners.

---

## 29. Proposed Extraction Strategy

PDF Step 3: WP-CLI (or locked REST) **on this site first**.

Suggested commands (design, not implemented here):

```
wp learncabana extract users --format=jsonl
wp learncabana extract enrollments
wp learncabana extract progress
wp learncabana extract quizzes --include-forced
wp learncabana extract xapi --chunk=50000
wp learncabana extract certificates
wp learncabana extract groups
```

Run against production or a refreshed clone. Include schema version + `tenant_slug=learncabana` on every record. Do **not** deploy a public REST extract without auth.

PHP/WP-CLI is the safe extractor regardless of whether the destination is Laravel, Nest, or something else.

---

## 30. Proposed Import Strategy

PDF Step 4: idempotent importer.

- Natural keys: `tenant_slug + wp_user_id`, `tenant_slug + activity_id`, `tenant_slug + quiz_statistic_ref_id`.
- Upsert; never create a second learner for the same WP ID.
- Import locked users as locked.
- Import groups before enrollments.
- xAPI last (largest).
- Dry-run counts vs LearnDash reports (PDF Step 6).

---

## 31. Idempotency Strategy

| Record | Idempotency key |
|---|---|
| User | `tenant + wp_users.ID` |
| Enrollment | `tenant + user_id + course_id + source` |
| Activity | `tenant + wp_learndash_user_activity.activity_id` |
| Quiz attempt | `tenant + statistic_ref_id` or (`user`,`quiz`,`time`) |
| xAPI | Tin Canny row ID / statement ID |
| Certificate issue | `user + course + completed_timestamp` |

Re-runs must not duplicate progress. Forced quiz rows stay forced.

---

## 32. Proposed Future LMS Architecture

**[PROPOSED — not current]** The PDF’s diagram is still a reasonable *target* **if** the business confirms 228 stores + CourseUpon + SSO.

For **this store alone**, a smaller target is enough:

- One tenant (LearnCabana)
- Org units = existing groups + supervisor tree
- Auth = replace Force Login (SSO only if a company IdP exists — **not found here**)
- Progress/certificate/reporting owned by the new app
- Content either (a) rebuild in CourseUpon or (b) import LearnDash structure

Do not add PostgreSQL `tenant_id` complexity until store #2 is identified.

---

## 33. Laravel vs Node / NestJS

PDF recommends Laravel because LearnDash data is PHP. Client said **not in favor; TBD**.

| Path | Fits this store |
|---|---|
| WP-CLI extractor in PHP | **Yes — do this first either way** |
| Laravel app | Fine if team is PHP; not mandated |
| Nest/Next | Fine if company standard; extractor stays PHP |
| Stay on WP + harden | Valid if 228-store premise is dropped |

**[BUSINESS DECISION REQUIRED]** Stack. This audit does **not** adopt Laravel.

---

## 34. CourseUpon Integration Review

| Check | Result |
|---|---|
| Plugin / composer / theme mention | **NOT FOUND** |
| `wp_options` / postmeta / usermeta | **0 rows** |
| Sync tables | **NOT FOUND** |
| Webhook vs poll | **Cannot confirm** — no account in this project |

**[RECOMMENDATION]** Confirm with the business whether CourseUpon is planned, already used on *other* stores, or only a PDF assumption. LearnCabana content today is LearnDash + Presto/Tin Canny packages.

---

## 35. SSO Review

| Check | Result |
|---|---|
| Learner JWT / OAuth / Keycloak | **NOT FOUND** |
| Central IdP | **NOT FOUND** |
| Actual login | WordPress + Theme My Login + Force Login |
| GrassBlade SSO | LRS admin token only |
| TrustedLogin | Vendor support access, not staff SSO |

PDF open question (“is there already centralized SSO?”) — **for this store: no.** Introducing Keycloak is a **new** identity project.

---

## 36. What Must Be Rebuilt

If the new LMS replaces LearnDash for this store:

- Enrollment (direct + group)
- Progress + completion rules (Tin Canny auto-complete; decide whether GrassBlade gate is kept)
- Quiz attempts + admin force-complete policy
- Certificate issuance
- EN/DE notification rules
- Weekly learner/manager reports
- Locked-user auth
- Site-wide login wall
- Assignment upload/review (28k historical)
- Org hierarchy (job title + supervisor + groups + province)
- Admin bulk enroll / bulk complete tools

---

## 37. What Can Be Retired

- WordPress + LearnDash Cloud hosting stack (when cut over)
- Ops plugins (backups, File Manager, Adminer, AI, cache, iThemes) — not LMS logic
- Uncanny Automator (draft only)
- LearnDash Achievements (0 rows)
- Visibility Control (0 content classes)
- Course Grid / Hub / Design Upgrade (presentation)
- GrassBlade CPT (unused) — **keep Tin Canny data**
- This audit’s local-only users (`admin`) — do not export

---

## 38. What Requires Business Decisions

1. Are there really 228 independent WP LMS sites? Provide URLs.
2. Is CourseUpon the future authoring tool for *this* catalog, or only a proposal?
3. Migrate course **content** or only learner **history**?
4. Keep 28k assignments?
5. Keep 5M xAPI statements or summarize?
6. Locked users: archive vs omit vs import locked?
7. Forced 100% quizzes: keep as completed or quarantine?
8. Identity: stay on local accounts vs new SSO?
9. Observation window before decommissioning LearnDash (PDF open question).
10. Application stack (Laravel vs other) — still TBD.
11. Certificate legal retention / PDF originals.
12. German vs English as source of truth for paired courses.

---

## 39. Open Questions / Blockers

PDF open questions, answered for **this store only**:

| Question | Answer |
|---|---|
| All 228 independent vs any multisite? | This store is independent single-site. Others: **unknown**. |
| CourseUpon webhooks vs poll? | CourseUpon **not integrated here**. |
| Centralized SSO already? | **No** on LearnCabana. |
| Observation window before decommission? | **Unset.** |

Additional blockers:

- Uploads not in the clone — cannot visually verify SCORM/video packages.
- No access to other stores.
- No CourseUpon credentials/API docs.

---

## 40. Recommended Next Steps

1. **This document is FINAL for LearnCabana Step 1** (customization catalog + data inventory). Further edits only if another store is audited or uploads are restored.
2. Confirm the 12 business decisions in §38 — especially 228-store inventory and CourseUpon.
3. Build the **WP-CLI extractor** against this clone; verify counts vs §26.
4. Refresh clone with `uploads/` only if SCORM/certificates/assignments must be file-verified.
5. Do **not** start the multi-tenant platform until store #2 and identity are real.
6. If the 228-store premise is withdrawn, scope becomes “replace LearnCabana LMS in place” — much smaller.

---

## 41. Final Architecture Recommendation

**Current state [VERIFIED]:** one private, bilingual (EN/DE) staff LMS on WordPress + LearnDash 4.25.7, **Astra Child** (parent Astra), Tin Canny xAPI on most lessons, group-based retail hierarchy, aggressive account locking, and a small set of real customizations (Tin Canny auto-complete, force-complete, language notifications, weekly reports). Not multi-tenant. Not CourseUpon. Not SSO. GrassBlade is installed but not the live content host.

**Toward the PDF [PROPOSED]:** use LearnCabana as the **reference tenant** and the first extraction target. Keep the PDF’s wave idea only after a store list exists. Keep CourseUpon as optional authoring **after** an API spike. Keep stack TBD; write the extractor in PHP now.

**Do not:** implement Laravel + Next.js + Keycloak + 228 `tenant_id`s from this store alone. That would be building the proposal, not migrating this system.

---

## 42. Appendix — Evidence Index

| Claim | Evidence |
|---|---|
| Production dump date | Updraft `backup_2026-09-11-1751_*`; SQL header WP 6.8.6 |
| LearnDash version | `sfwd-lms/sfwd_lms.php` header 4.25.7; WP-CLI active |
| User / role / lock counts | `wp_users`, `wp_usermeta.wp_capabilities`, `baba_user_locked` |
| CPT counts | `wp_posts` GROUP BY post_type, post_status |
| Activity | `wp_learndash_user_activity` |
| Tin Canny live embeds | 163 published lessons with `tincanny/content`; Tin Canny `Services.php` mark-complete |
| Tin Canny scale | `COUNT(*)` on `wp_uotincan_reporting` / `_quiz` |
| GrassBlade gate (code only) | `grassblade/addons/learndash/functions.php`; CPT/shortcode/meta attach = 0 live |
| Manual 100% quiz | `mark_quiz_complete`; **682** `_sfwd-quizzes` rows contain `m_edit_by` |
| Certificate assignment | 125 published courses contain `3614` in `_sfwd-courses` |
| Course access types | 144 closed + 17 free published |
| Course language (published) | ACF `course_language`: english 156, german 11 |
| Locales | `locale` empty 4576 / en_US 1712 / de_DE 2; `icl_admin_language` en 2771 / de 2 |
| Visibility unused | 0 posts, 0 menus; plugin option enabled |
| Uploads gap | `wp-content/uploads` = 436 KB on clone |
| Re-verification | MySQL queries 14 Sep 2026 on Local socket `ZiCen3nYr` |
| Active theme = Astra Child | Production dump `wp_options.stylesheet=astra-child`, `template=astra`; local `/wp-admin/themes.php` 14 Sep 2026 shows **Active: Astra Child**; Kadence installed, inactive |

---

*FINAL — LearnCabana Step 1 audit for this store. Source of truth: restored local clone (production DB + plugins + themes) + this document. The client PDF remains a proposal for a future platform, not a description of production.*
