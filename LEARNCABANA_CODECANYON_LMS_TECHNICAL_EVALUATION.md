# LearnCabana LMS Replacement — CodeCanyon LMS Technical Evaluation

**Subtitle:** PHP-Based LMS Platform Assessment & Migration Feasibility Review

| Field | Value |
|---|---|
| Author role | Senior PHP / LMS migration architect |
| Subject system | LearnCabana (Canna Cabana E-Learning) — WordPress + LearnDash |
| Baseline reference | `LMS_CURRENT_STATE_AND_MIGRATION_ARCHITECTURE_REVIEW.md` (Step 1 audit, re-verified 14 Sep 2026) |
| Catalogue reviewed | [CodeCanyon LMS search](https://codecanyon.net/search/lms) |
| Evaluation date | 18 September 2026 |
| Source packages in workspace | **None** — code-level verification is pending source-package access |
| Evidence classes used | **VERIFIED** · **VENDOR CLAIM** · **ENGINEERING INFERENCE** |

---

## 1. Executive Summary

I reviewed the LearnCabana Step 1 audit as the authoritative current-state baseline, then evaluated PHP-based CodeCanyon LMS products as potential migration foundations—not as feature shopping lists.

**My assessment:** no CodeCanyon product is a low-risk drop-in replacement for LearnCabana. The current system is a private staff LMS whose live completion path is Tin Canny SCORM/xAPI, with group enrollment, supervisor/job-title hierarchy, EN/DE notification gating, admin-forced quiz completion, large assignment volume, and ~5M xAPI history rows. Marketplace LMS scripts are built primarily for selling courses, not for reproducing that operational model.

Among PHP candidates I investigated, **Infix LMS** is the strongest foundation for a controlled proof-of-concept. It is Laravel-based, modular, claims both SCORM and xAPI as installable modules, documents group enrollment (via Skill & Pathway), institutes/organizations, manual enrollment, assignments, certificates, and CSV student import. Those claims still require source/runtime proof.

**Rocket LMS** is the second candidate worth validating because of Private Mode, organizational education, SCORM plugin, assignments, manual enrollment, and multi-language content. I would treat Rocket as higher vendor/framework lock-in risk because critical LMS capabilities sit in a paid plugin bundle and the vendor requires ionCube Loader for license-related files.

**Edulab LMS + Tenanta** is the only candidate where I could verify a serious multi-tenant architecture description (Stancl Tenancy). It does **not** currently look SCORM/xAPI-ready for LearnCabana without an external engine or major custom work.

**Lernen LMS** and marketplace-first scripts (including WordPress Academy LMS variants and tutor-marketplace products) should not proceed as LearnCabana replacement foundations.

I would not commit to any CodeCanyon product without a POC that proves SCORM launch/completion against real LearnCabana packages, progress/quiz migration including forced completions, group enrollment semantics, and account locking.

---

## 2. Management Request / Objective

Management asked whether a prebuilt PHP CodeCanyon LMS/framework could make replacing LearnDash + WordPress easier.

The objective is **not** to pick the product with the most screenshots or sales. The objective is to identify which candidate:

1. Provides the strongest starting foundation for LearnCabana behavior  
2. Requires the least custom rebuilding of verified business rules  
3. Has the most compatible data model for users, groups, enrollment, progress, quizzes, certificates, assignments  
4. Provides usable extension points / APIs  
5. Can realistically reproduce LearnCabana operational behavior  
6. Creates major migration blockers  
7. Creates vendor/framework lock-in  
8. Can support the proposed future centralized / multi-tenant architecture  

I evaluated candidates against that migration objective.

---

## 3. Existing LearnCabana Baseline

I treated the existing Step 1 audit as authoritative. I did **not** invent additional current-state functionality.

### 3.1 Verified platform shape

| Item | Verified state |
|---|---|
| Stack | WordPress + LearnDash **4.25.7** |
| Nature | Private staff-only LMS (Force Login) |
| Languages | English + German (WPML) |
| Users | **6,290** |
| Locked accounts | **3,943** (`baba_user_locked=yes`) |
| Courses published | **161** |
| Lessons published | **245** |
| Groups | **302** |
| Assignments published | **28,503** |
| Course activity complete | **93,271** |
| Lesson activity complete | **238,788** |
| Quiz activity complete | **4,822** |
| Tin Canny reporting / xAPI rows | **~4.99 million** (`wp_uotincan_reporting`) |
| Certificate template | **1** template used by **125** published courses |
| Enrollment pattern | Primarily **group-based**, plus direct access |
| Org model | Supervisor + job-title hierarchy (not `tenant_id`) |
| Notifications | Language-aware EN/DE gating |
| Reporting | Weekly learner + manager reports |
| Admin force-complete | Course/lesson/topic/quiz; **682** users with admin-forced 100% quiz evidence (`m_edit_by`) |
| Live SCORM/xAPI path | **Tin Canny** (163/245 published lessons embed `tincanny/content`) |
| GrassBlade | Completion filter code present; **no active attached GrassBlade content** found |
| CourseUpon | **Not found** in this store |
| SSO / JWT learner auth | **Not found** |
| Multi-tenant application model | **Not found** |
| Application layer outside WP/plugins/theme | **Not found** |

### 3.2 Behaviors that any replacement must account for

These are migration-critical, not nice-to-haves:

1. Group enrollment at scale (302 groups)  
2. Locked-user semantics (do not activate/email locked accounts by default)  
3. Tin Canny-driven lesson completion + large xAPI history  
4. Admin-forced completion and fabricated 100% quiz attempts  
5. EN/DE course language + notification language gate  
6. Supervisor rollup weekly reports  
7. Assignment submission volume and group-leader download path  
8. Certificate mapping for 125 courses  
9. Private staff access (no open marketplace registration)  

If a candidate cannot support these either natively or through clean customization, it is not a low-risk foundation.

---

## 4. Evaluation Methodology

### 4.1 What I reviewed

1. LearnCabana Step 1 audit (code + DB evidence already recorded there)  
2. CodeCanyon LMS catalogue at `https://codecanyon.net/search/lms`  
3. Candidate listing pages, changelogs, and vendor documentation / helpdesk articles where reachable  
4. Workspace filesystem for purchased source packages  

### 4.2 What I could not review in this pass

- No Infix / Rocket / Academy / Lernen / Edulab source trees are present in the LearnCabana workspace.  
- Therefore: **Code-level verification is pending source-package access** for every candidate.  
- Production LearnCabana `uploads/` packages are missing on the local clone (already noted in the Step 1 audit), so live SCORM package byte-level testing against Tin Canny content IDs remains blocked until packages are available.

### 4.3 Evidence rules used in this document

| Label | Meaning |
|---|---|
| **VERIFIED** | Confirmed from LearnCabana source/DB audit, or from unambiguous vendor documentation I could open |
| **VENDOR CLAIM** | Stated on CodeCanyon/vendor marketing or feature lists; implementation not independently proven |
| **ENGINEERING INFERENCE** | Reasonable architectural conclusion requiring POC/source proof |

I do not treat marketplace sales, ratings, or UI attractiveness as architecture evidence.

---

## 5. CodeCanyon Candidate Landscape

From the CodeCanyon LMS catalogue I examined, the technically relevant PHP/Laravel products for this brief were:

| Candidate | Framework (vendor) | Why considered | Initial disposition |
|---|---|---|---|
| **Infix LMS** | Laravel 12 (VENDOR CLAIM) | SCORM + xAPI modules claimed; groups/institutes/org modules; assignments; certificates | Serious candidate |
| **Rocket LMS** | Laravel (VENDOR CLAIM) | Private Mode; organizations; SCORM plugin; assignments; manual enrollment | Serious candidate |
| **Academy LMS (Laravel)** by Creativeitem | Laravel | SCORM lesson/course type claimed; clean marketplace LMS | Secondary / high risk |
| **Edulab LMS** (+ **Tenanta** addon) | Laravel 11 (VENDOR CLAIM) | Organization portal + documented multi-tenancy addon | Secondary for tenancy only |
| **Lernen LMS** (+ Learnty addon) | Laravel 11 | Listed as LMS; actually tutor marketplace | Not suitable |
| **eClass** | Laravel 10 | Institutes / private courses | Weak SCORM evidence |
| **EduEx**, SkillGro, LMS ZAI, etc. | Laravel variants | Marketplace clones | Not prioritized |

**Out of scope for selection (reference only):**

- **LMS Hub** (Node.js + Express + Vue + PostgreSQL) — claims SCORM, 193 REST endpoints, full source without encoding. Relevant as an architecture contrast, but outside the PHP-framework requirement.  
- WordPress LMS plugins (DT LMS, WordPress Academy LMS, etc.) — they do not exit the WordPress dependency management wants to leave.

---

## 6. Candidate-by-Candidate Technical Review

### 6.1 Infix LMS (CodeThemes)

#### A. Technology

| Area | Finding | Evidence |
|---|---|---|
| PHP | 7.x / 8.x claimed | VENDOR CLAIM (listing) |
| Framework | Laravel; listing states **v9.0.0 / Laravel 12** | VENDOR CLAIM |
| Database | MySQL / SQL dumps included | VENDOR CLAIM |
| Frontend | Bootstrap / Blade-style PHP LMS UI | VENDOR CLAIM / ENGINEERING INFERENCE |
| Source | Full PHP source package on purchase | VENDOR CLAIM |
| Modules | Module Manager for add-ons | VERIFIED from vendor docs existence |
| API | “API Settings” in docs = Google Maps + Fixer currency keys | VERIFIED docs — **not** an LMS domain REST API |
| Auth | Local login/registration; social/login device controls claimed | VENDOR CLAIM |
| Queues | Laravel-capable in principle | ENGINEERING INFERENCE — not verified as used for LMS jobs |
| Storage | Local uploads; S3/Bunny as modules | VENDOR CLAIM |
| Licensing | CodeCanyon regular/extended; module upsells | VENDOR CLAIM |
| Updates | Auto-update claimed | VENDOR CLAIM |

#### B. LMS domain model (vendor-documented / claimed)

| Domain | Support | Notes |
|---|---|---|
| Users / roles | Present | Student, instructor, admin, custom roles claimed |
| Institutes / organizations | Present (module/core areas) | Institutes list; organisational education module |
| Groups | Present via Skill & Pathway module | Parent/sub-group; group enroll cascades | 
| Courses / chapters / lessons | Present | Course builder with topics/lessons |
| Quizzes / questions | Present | Course quizzes + question bank claims |
| Progress | Present | Course progress features listed |
| Certificates | Present | Certificate designer claimed |
| Assignments | Present | Course Assignment module documented |
| Enrollment | Present | Manual enroll + purchase enroll |
| Prerequisites / sequence | Partial | “Complete Course Sequence” = sequential lock, not LD-style prereq graph |
| Reporting | Present at marketplace depth | Not LearnCabana manager/supervisor weekly reports |

#### C. SCORM / xAPI — HIGH PRIORITY

| Capability | Status |
|---|---|
| SCORM support | **VENDOR CLAIM** — separate SCORM module |
| SCORM 1.2 / 2004 | **VENDOR CLAIM** (module listing) |
| Package upload | **VENDOR CLAIM** |
| Completion / pass-fail / duration | **VENDOR CLAIM** |
| xAPI support | **VENDOR CLAIM** — separate xAPI module; install via Module Manager |
| LRS | **VENDOR CLAIM** / unclear whether embedded or external |
| Resume / attempt model | **Not verified** |
| Mapping to lesson completion | **Not verified** |
| Import of Tin Canny history | **Missing** — custom development / archive required |
| External SCORM engine integration | Possible in Laravel terms — **ENGINEERING INFERENCE**, spike required |

> Claimed by vendor — implementation not yet verified.

#### D. LearnCabana fit (summary)

**Strengths**

- Closest documented match to group-based enrollment among reviewed PHP products (Skill & Pathway groups)  
- SCORM **and** xAPI both appear as first-class product modules (rare on CodeCanyon)  
- Manual enrollment, assignments, certificates, institutes, registration fields including job title  
- Modular addon architecture reduces (but does not eliminate) core-coupling risk  

**Weaknesses / gaps**

- Product is still marketplace-oriented (payments, instructor commissions, coupons)  
- No verified LearnCabana-style supervisor hierarchy or weekly manager rollups  
- No verified account-lock model equivalent to `baba_user_locked`  
- No verified admin-forced quiz 100% completion path  
- “API Settings” are third-party keys, not a migration-grade LMS API  
- SaaS module appears to be **feature packages / limits**, not true tenant isolation  

**Classification:** **Potential Foundation** — subject to source + SCORM/xAPI POC.

---

### 6.2 Rocket LMS (rocketsoft)

#### A. Technology

| Area | Finding | Evidence |
|---|---|---|
| PHP | 8.1+ claimed | VENDOR CLAIM |
| Framework | Laravel | VENDOR CLAIM |
| DB | MySQL 5.7+ / MariaDB 10.2+ | VENDOR CLAIM |
| Frontend | Bootstrap / SCSS / Webpack | VENDOR CLAIM |
| Source | “Full source” claimed; **license-related files encrypted**; **ionCube Loader required** | VENDOR CLAIM (FAQ) |
| Critical features | Many behind **Universal Plugin Bundle** | VENDOR CLAIM |
| API | Mobile/Flutter app API plugin claimed; general LMS REST API **not verified** | VENDOR CLAIM / Not verified |
| Storage | S3 / Wasabi claimed | VENDOR CLAIM |
| Auth | Local + Google/Facebook/SMS social login claimed | VENDOR CLAIM |

#### B. Domain model highlights

- Organizations plugin (students/instructors under organizations)  
- User groups (marketing/discount oriented — not necessarily LD groups)  
- Private Mode (internal LMS access restriction)  
- Manual enrollment plugin  
- Assignments & homework plugin  
- Quiz & certification plugin  
- Multi-language content plugin  
- SaaS Packages plugin (limits for instructors/orgs — monetization SaaS, not proven tenant isolation)

#### C. SCORM / xAPI

| Capability | Status |
|---|---|
| SCORM | **VENDOR CLAIM** — plugin since v1.5; Captivate/iSpring packages |
| SCORM versions | **Not verified** beyond “SCORM file types” |
| xAPI / LRS | **Not found** as a first-class Rocket feature in materials I reviewed |
| Tin Canny history migration | Missing — custom / external LRS |

> Claimed by vendor — SCORM implementation not yet verified. xAPI appears absent unless custom-built or externally integrated.

#### D. LearnCabana fit

**Strengths**

- Private Mode aligns with staff-only LMS better than open marketplace defaults  
- Organizational education + manual enrollment + assignments + certificates are directionally relevant  
- Multi-language content plugin is useful for EN/DE course variants  

**Major concerns**

- **Vendor lock-in:** ionCube + encrypted license files + feature gating via plugin bundle  
- SCORM only; xAPI not evidenced  
- Organization model is commercial/institute oriented; supervisor/job-title reporting still custom  
- SaaS Packages ≠ multi-tenant store isolation  

**Classification:** **Possible with Significant Customization** / **Requires Further Technical Validation** (especially encoding + SCORM runtime quality).

---

### 6.3 Academy LMS Laravel (Creativeitem)

#### A. Technology

Laravel LMS, MySQL 8.x claimed, addon installer, SCORM added as lesson/course type in recent updates (**VENDOR CLAIM**). Separate WordPress “Academy LMS” product exists and must not be confused with the Laravel item.

#### B. LearnCabana fit

- Good as a commercial course marketplace starter  
- SCORM addon exists (**VENDOR CLAIM**)  
- xAPI: **Not verified**  
- Groups equivalent to LearnDash groups: **Not verified**  
- Supervisor hierarchy / locked users / forced quiz completion: **Missing** → custom development required  
- Multi-tenancy: **Not verified**  

**Classification:** **High Migration Risk** for LearnCabana.

---

### 6.4 Edulab LMS + Tenanta (CodexShaper)

#### A. Technology / tenancy

| Area | Finding | Evidence |
|---|---|---|
| Base | Laravel 11 LMS with Admin / Instructor / Student / Organization portals | VENDOR CLAIM / docs |
| Multi-tenancy | **Tenanta** addon uses **Stancl Tenancy v3**; separate DB or shared DB + `tenant_id` | VERIFIED from vendor docs |
| Queues/scheduler | Required for provisioning | VERIFIED docs |
| SCORM / xAPI | **Not verified** in materials I reviewed | Missing / unknown |

#### B. LearnCabana fit

- Best documented path toward centralized multi-store tenancy among PHP CodeCanyon options  
- Organization portal may help group training, but is not LearnDash groups + supervisor graph  
- Without proven SCORM/xAPI, Edulab cannot carry LearnCabana’s primary lesson completion path  

**Classification:** **Requires Further Technical Validation** for tenancy architecture; **High Migration Risk** as sole LMS replacement unless an external SCORM/xAPI engine is accepted.

---

### 6.5 Lernen LMS (AmentoTech)

Lernen is a **tutor marketplace** (1:1 / group tutoring, bookings, calendars). Course LMS behavior requires the separate **Learnty** addon and additional quiz/assignment addons.

- SCORM/xAPI: **Not found** in docs I reviewed  
- Corporate staff LMS semantics: poor fit  
- Extension model is addon/modules over a marketplace core  

**Classification:** **Not Suitable for Current Requirements**.

---

### 6.6 Other PHP candidates (brief)

| Product | Note | Suitability |
|---|---|---|
| eClass | Institutes, private courses; SCORM **not verified** | High Migration Risk / Not Suitable without SCORM proof |
| EduEx | Laravel + Flutter marketplace clone; SCORM **not verified** | Not prioritized |
| SkillGro / LMS ZAI | Marketplace scripts | Not prioritized |

### 6.7 LMS Hub (Node.js) — reference only

LMS Hub claims SCORM lesson types, PostgreSQL, 193 REST endpoints, and unencoded source. That combination is architecturally interesting for a greenfield API-first LMS. It is **out of scope** for this PHP prebuilt-framework selection, but I would keep it as a comparison point if management later relaxes the PHP-only constraint.

---

## 7. LearnCabana-to-Candidate Capability Mapping

Primary mapping focuses on **Infix LMS** and **Rocket LMS** as the only serious PHP shortlist. Other products are summarized where relevant.

| Current LearnCabana Requirement | Candidate Capability | Native Support? | Customization Required? | Migration Complexity | Risk | Recommended Approach |
|---|---|---|---|---|---|---|
| Users (6,290) | Both have user tables / import paths (Infix CSV import documented) | Partial | Yes (field mapping) | Low–Medium | Low | ETL into users + profile fields |
| Locked users (3,943) | No LearnCabana-equivalent lock flag verified | No | Yes | Medium | Medium | Add `status=locked`; block auth + reports |
| Roles (subscriber, group_leader, admin, sub_admin) | Generic student/instructor/admin/org roles | Partial | Yes | Medium | Medium | Role mapping table; recreate sub_admin caps |
| Groups (302) | Infix: Skill & Pathway groups; Rocket: org + user groups | Partial (Infix closer) | Yes | Medium–High | High | Spike group→course enrollment semantics |
| Group enrollment | Infix group enroll cascade documented; Rocket org pricing/enroll | Partial | Yes | High | High | Do not assume LD group courses auto-map |
| Direct enrollment | Both support manual enroll | Yes (claimed) | Low | Low | Low | Map `course_*_access_from` |
| Supervisor hierarchy | Not a native LMS graph in either | No | Yes | High | High | Custom `manager_user_id` + report queries |
| Job titles | Infix registration field includes job title; Rocket custom fields claimed | Partial | Yes | Medium | Medium | Enum/normalize after import |
| Bilingual content EN/DE | Both claim multi-language; Rocket has multi-language content plugin | Partial | Yes | Medium | Medium | Preserve course language pairs + locale |
| Bilingual notifications | Language gate matching user/course/notification language | No | Yes | Medium–High | High | Rebuild notification rules |
| Courses / lessons / topics | Standard course→section→lesson models | Partial | Yes | Medium | Medium | Topics thin in LearnCabana; collapse if needed |
| Quizzes / attempts | Native quizzes claimed | Partial | Yes | High | High | Map ProQuiz + `_sfwd-quizzes`; keep timestamps |
| Forced quiz completion (682 users) | Not verified as native admin fabricate-100% | No | Yes | High | High | Import with `forced=true` audit flag; admin tool |
| Progress tracking | Native progress claimed | Partial | Yes | High | High | Map LD activity rows; reconcile with SCORM |
| Prerequisites | Infix sequence lock ≠ LD prereq lists | Partial | Yes | Medium | Medium | Custom prereq service if required |
| SCORM | Infix module / Rocket plugin | Claimed | Likely tuning | Very High | Very High | POC with real packages |
| xAPI | Infix module claimed; Rocket not evidenced | Infix claimed / Rocket no | Likely high | Very High | Very High | Prove LRS + completion callback |
| Tin Canny history (~5M rows) | No native Tin Canny import | No | Yes / archive | Very High | Very High | Archive LRS; migrate summaries only if needed |
| Certificates (1 template / 125 courses) | Certificate builders claimed | Partial | Yes | Medium | Medium | Recreate template; remap course links |
| Assignment records (28,503) | Assignment modules claimed | Partial | Yes | High | High | Decide compliance retention before full migrate |
| Assignment file downloads | Custom LD theme path today | No | Yes | High | Medium | Rebuild authorized download endpoints |
| Weekly learner/manager reports | Not native LearnCabana logic | No | Yes | High | High | Custom jobs/mailers |
| Admin enrollment tools | Manual enroll UIs claimed | Partial | Yes | Medium | Medium | Recreate bulk enroll UX |
| Account locking | Not verified | No | Yes | Medium | Medium | Auth middleware + status |
| SSO | Not present today; candidates: social OAuth only | No (enterprise SSO) | Yes | High | Medium | New IdP integration (OIDC/SAML) |
| API | Infix docs show Maps/Fixer; Rocket mobile API plugin | Weak / unclear | Yes | High | High | Assume custom API layer unless proven |
| Multi-tenancy | Infix/Rocket SaaS = packages; Edulab Tenanta = real tenancy docs | Mostly no / Edulab addon | Yes | Very High | Very High | Do not assume Laravel LMS = multi-tenant |
| Storage | Local + optional S3 modules | Partial | Yes | Medium | Medium | Plan object storage for packages/PDFs |
| Background processing | Laravel queues available in principle | Unclear usage | Yes | Medium | Medium | Required for reports + imports |
| Audit / forced-completion history | LearnCabana has `m_edit_by` evidence | No | Yes | High | High | Preserve provenance columns |

---

## 8. Migration Complexity Assessment

Estimates are engineering categories, not hour quotes.

### 8.1 Infix LMS

| Area | Complexity | Why |
|---|---|---|
| Users + lock flag | Low–Medium | Import is feasible; lock behavior is custom |
| Roles | Medium | Capability model differs from WP roles |
| Groups + group enrollment | High | Closest native concept, but semantics ≠ LearnDash groups |
| Courses/lessons | Medium | Structural mapping possible if content migrates |
| Quizzes + attempts | High | Different attempt schema; forced attempts need flags |
| Forced completion tooling | High | Appears custom |
| Progress | High | Must reconcile LD activity + SCORM completion |
| SCORM runtime | Very High / external if module fails | Core risk for live learning |
| xAPI + Tin Canny history | Very High / archive | Volume + schema mismatch |
| Certificates | Medium | Template rebuild + course map |
| Assignments | High | Volume + files + permissions |
| EN/DE notifications | Medium–High | Custom rule engine |
| Weekly supervisor reports | High | Custom |
| SSO | New implementation | Not in current or candidate baseline |
| Multi-tenancy | New architecture / unsuitable as-is | SaaS module ≠ tenant isolation |
| Overall | **High**, with SCORM/xAPI as the make-or-break | |

### 8.2 Rocket LMS

| Area | Complexity | Why |
|---|---|---|
| Private staff mode | Medium | Private Mode helps, still needs lock semantics |
| Organizations | Medium–High | Useful, not supervisor graph |
| SCORM | Very High | Plugin claimed; quality unknown |
| xAPI | Blocked / Requires external component | Not evidenced |
| Plugin-bundle dependency | High (process risk) | Many needed features are paid addons |
| Encoded license files | High (maintainability) | ionCube requirement |
| Overall | **Very High** operationally despite feature breadth | |

### 8.3 Edulab + Tenanta

| Area | Complexity | Why |
|---|---|---|
| Multi-tenant skeleton | Medium (relatively) | Best documented tenancy among PHP options |
| LearnCabana LMS parity | Very High | SCORM/xAPI gap dominates |
| Overall | **Blocked / Requires external SCORM-xAPI component** for LearnCabana lesson path | |

### 8.4 Lernen / Academy Laravel / eClass

Overall: **Very High** or **Not Suitable** because domain model mismatch plus weak/absent SCORM+xAPI and group/supervisor semantics.

---

## 9. SCORM/xAPI Assessment

This is the highest technical risk in the replacement.

### 9.1 Current LearnCabana truth

- Live path is **Tin Canny** embeds on lessons calling LearnDash mark-complete.  
- ~5M xAPI reporting rows exist.  
- GrassBlade is installed but not the live content host.  
- Package binaries are not on the local clone.

### 9.2 Candidate reality

| Candidate | SCORM | xAPI | Notes |
|---|---|---|---|
| Infix | Module claimed (1.2/2004) | Module claimed | Only PHP shortlist item claiming both |
| Rocket | Plugin claimed | Not evidenced | Private LMS fit better; standards gap on xAPI |
| Academy Laravel | Addon/lesson type claimed | Not evidenced | Marketplace focus |
| Edulab | Not verified | Not verified | Tenancy-strong, standards-weak |
| Lernen | Not found | Not found | Unsuitable |

### 9.3 Engineering position

I would treat vendor SCORM/xAPI pages as **VENDOR CLAIM** until a POC launches a real LearnCabana package and proves:

1. Package upload/extract  
2. Launch in learner session  
3. Completion callback writes progress  
4. Pass/fail and score handling  
5. Attempt/resume behavior  
6. Admin report visibility  
7. Whether Tin Canny history must be archived externally rather than imported row-for-row  

If Infix’s modules fail that POC, the fallback is **external component required** (e.g., dedicated SCORM/xAPI engine / LRS) plus custom completion bridging—regardless of which LMS UI is chosen.

---

## 10. Data Model & Migration Assessment

### 10.1 LearnCabana source model (verified)

Relational-ish WordPress model:

- CPT graph: courses, lessons, topics, quizzes, groups, certificates, assignments  
- Access meta: `course_{id}_access_from`, group user meta  
- Activity: `wp_learndash_user_activity`  
- Quizzes: `_sfwd-quizzes` + ProQuiz tables + Tin Canny quiz rows  
- xAPI: `wp_uotincan_*`  
- Org fields: ACF usermeta (`job_titles`, `supervisor`, `expiry_date`, `province`)  
- No `tenant_id`

### 10.2 Expected candidate models (engineering inference pending source)

Typical CodeCanyon Laravel LMS schemas are normalized tables roughly like:

`users`, `roles`, `courses`, `course_sections`, `lessons`, `quizzes`, `questions`, `quiz_results`, `enrollments`, `certificates`, `assignments`, `assignment_submissions`, optional `organizations`.

That is **cleaner** than WordPress postmeta, but **not isomorphic** to LearnDash.

### 10.3 Mapping difficulties (all candidates)

| LearnCabana data | Mapping issue |
|---|---|
| Group course enrollment | Need explicit enrollment expansion or retained group membership model |
| Forced quiz attempts | Need provenance columns (`forced_by`, `forced_at`) |
| Tin Canny 5M rows | Likely **cannot** map cleanly into app progress tables; archive |
| Assignments as WP posts + files | File URI rewrite + permission model rewrite |
| Certificate PDFs missing locally | Regenerate after import or restore uploads |
| Empty locale = English notification rule | Business rule, not a locale field default in candidates |
| Topics sparsely used | May flatten into lessons |

### 10.4 Data that cannot map cleanly without custom tables

- `baba_user_locked` semantics  
- Supervisor graph used by weekly manager reports  
- Notification language triad (user / course / notification)  
- `m_edit_by` forced quiz audit trail  
- Tin Canny statement lake at full fidelity  
- LearnDash activity meta nuances  

**Conclusion:** even the best candidate still needs a LearnCabana compatibility schema (custom tables/services) beside vendor tables.

---

## 11. Customization / Extension Architecture

### 11.1 Source status

**Code-level extension assessment remains pending source-package access** for all candidates.

### 11.2 What vendor materials suggest

| Candidate | Apparent extension style | Preliminary judgment |
|---|---|---|
| Infix | Module Manager addons; Laravel app structure | **GOOD (conditional):** custom behavior may be isolatable as modules/services if core stays untouched — **unverified in source** |
| Rocket | Plugin bundle + some encrypted license files | **RISK:** features and updates coupled to vendor plugins; encoded files limit auditability |
| Academy Laravel | Addon zip installer | **RISK / unknown** without source |
| Edulab | nWidart-style modules + Tenanta | **GOOD (conditional)** for tenancy; LMS-domain gaps remain |
| Lernen | Addon modules over marketplace core | **RISK** for corporate LMS rewrite |

### 11.3 LearnCabana-required behaviors vs extension quality

| Behavior | Likely extension outcome |
|---|---|
| Locked users | Custom — usually clean (middleware/status) |
| Supervisor weekly reports | Custom jobs — clean if queues exist |
| EN/DE notification gate | Custom listeners — clean if mail events exist |
| Forced completion | Often **RISK** — may require deep progress/quiz writes |
| SCORM completion → progress | Depends on module hooks; may become **BLOCKER** if module is closed/poor |
| Group enrollment parity | Custom domain services likely required |
| Enterprise SSO | Custom / package integration |
| True multi-tenancy on Infix/Rocket | Likely **BLOCKER** without rewriting tenancy |

I would not claim “custom behavior can be implemented without modifying vendor core” until the purchased source is inspected for events, service container usage, and override points.

---

## 12. SSO & Authentication

| Concern | LearnCabana today | Infix | Rocket | Academy Laravel | Edulab |
|---|---|---|---|---|---|
| Local auth | VERIFIED | Native (claimed) | Native (claimed) | Native (claimed) | Native (claimed) |
| OAuth social | Not learner SSO | Possible / claimed | Google/Facebook/SMS claimed | Social addons common | Not verified here |
| OIDC / SAML enterprise SSO | Not present | Custom implementation | Custom implementation | Custom implementation | Custom implementation |
| JWT API auth | Not present | Not verified | Mobile API plugin claimed | Not verified | Not verified |
| Centralized IdP | New work | New work | New work | New work | New work |

**Clear separation:**

- **Native capability:** local username/password (all serious candidates, vendor claim)  
- **Available addon:** social OAuth (Rocket/Infix/Academy style products)  
- **Custom implementation:** OIDC/SAML/JWT for corporate SSO  
- **Not verified:** production-grade API token auth for a future Next.js/portal layer  

---

## 13. Multi-Tenancy

The future proposal discusses centralizing multiple WordPress stores. LearnCabana itself has **no** tenant model today; hierarchy is groups + supervisor + job titles.

| Candidate | Tenant/store support | Verdict |
|---|---|---|
| Infix SaaS module | Feature limits for instructors/orgs | **Architecturally unsuitable** as true multi-tenant isolation without custom tenancy |
| Rocket SaaS Packages | Same pattern | **Architecturally unsuitable** as-is for 228-store isolation |
| Rocket/Infix Organizations | Org-scoped users/courses (commercial) | **Configurable starting point**, not full tenancy |
| Edulab + Tenanta | Stancl tenancy; separate DB or `tenant_id`; subdomain provisioning | **Native (addon)** for SaaS tenancy — best PHP CodeCanyon tenancy evidence |
| Lernen | Marketplace, not store tenancy | Unsuitable |

**Do not assume Laravel LMS = multi-tenant.**

For LearnCabana Store 0 migration, I would first migrate **one tenant** correctly. Multi-store centralization is a separate architecture decision and may favor Edulab Tenanta patterns—or a custom tenancy layer on Infix—only after SCORM/xAPI is solved.

---

## 14. CourseUpon Integration Considerations

The LearnCabana audit found **no CourseUpon integration**.

Therefore I do **not** assume any CodeCanyon LMS has CourseUpon support.

### Integration approach (all candidates)

| Integration need | Approach | Status |
|---|---|---|
| REST pull/push of courses | Custom sync service | Architecture spike required |
| Webhooks on publish | Depends on CourseUpon contracts | Not verified |
| Background sync | Laravel queues | Engineering inference |
| Content import | Map external IDs to local courses/lessons | Custom |
| Authoring workflow ownership | Business decision (PDF proposed CourseUpon owns content) | Business decision required |

Until CourseUpon API contracts are provided, CourseUpon should be treated as **external dependency + spike**, not a vendor checkbox.

---

## 15. Risks & Blockers

### 15.1 Hard blockers (until proven otherwise)

1. Unproven SCORM runtime parity with Tin Canny lesson completion  
2. Unproven xAPI/LRS path (critical for Infix validation; likely missing on Rocket)  
3. No clean native home for LearnCabana supervisor reporting model  
4. No native forced-quiz provenance model  
5. No enterprise SSO in current or candidate baselines  
6. True multi-tenancy absent on the two strongest LMS-feature candidates  

### 15.2 Soft blockers

1. Assignment file migration volume (28,503)  
2. EN/DE notification rule rebuild  
3. Certificate PDF regeneration if uploads unrestored  
4. Marketplace cruft (carts, commissions) conflicting with private staff UX  
5. Vendor module/plugin commercial coupling  

### 15.3 Major unknowns

1. Actual Infix SCORM/xAPI code quality and completion callbacks  
2. Whether Rocket SCORM supports resume/attempts adequately  
3. Whether group enrollment cascade matches LearnDash group-course semantics  
4. API surface for a future detached learner portal  
5. Encoded-file impact on Rocket maintainability after updates  
6. Whether other “228 stores” even exist as independent WP installs (not verified in LearnCabana audit)

### 15.4 External dependencies

- Real SCORM/xAPI packages from production uploads  
- Possible external LRS / SCORM engine if modules fail  
- CourseUpon API (if content strategy requires it)  
- Corporate IdP (if SSO is mandated)

### 15.5 Vendor lock-in concerns

| Candidate | Lock-in profile |
|---|---|
| Infix | Module marketplace + update cadence; generally source-available claim |
| Rocket | **Higher:** ionCube loader, encrypted license files, paid plugin bundle for core LMS capabilities |
| Edulab/Tenanta | Addon licensing + Stancl coupling; better tenancy, weaker LMS-standards fit |
| Lernen | Addon stack around marketplace core |

---

## 16. Candidate Comparison Matrix

| Candidate | Framework | DB | SCORM | xAPI | Quiz migration | Progress migration | Groups/orgs | Assignments | Certificates | API | SSO | Multi-tenancy | Extension model | Migration complexity | Major blocker | Source-code confidence | Overall suitability |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Infix LMS** | Laravel 12 claimed | MySQL claimed | Module claimed | Module claimed | High effort | High effort | Groups module documented | Module documented | Claimed | Weak (Maps/Fixer docs) | Custom | Unsuitable as-is (SaaS≠tenancy) | Modules (unverified source) | High | SCORM/xAPI proof pending | Low (no package yet) | **Potential Foundation** |
| **Rocket LMS** | Laravel claimed | MySQL claimed | Plugin claimed | Not evidenced | High | High | Org plugin | Plugin | Plugin | Mobile API claimed; REST unclear | Custom | Unsuitable as-is | Plugins + encoded license files | Very High | xAPI gap + lock-in | Low | **Possible with Significant Customization** |
| **Edulab + Tenanta** | Laravel 11 claimed | MySQL | Not verified | Not verified | High | High | Organization portal | Unknown/partial | Claimed | Not verified | Custom | **Best documented** | Modules + Stancl | Very High / blocked on SCORM | SCORM/xAPI absence | Low | **Requires Further Technical Validation** |
| **Academy LMS Laravel** | Laravel | MySQL 8 claimed | Addon claimed | Not verified | High | High | Weak vs LD groups | Addon ecosystem | Claimed | Not verified | Custom | Not verified | Addons | Very High | Domain mismatch | Low | **High Migration Risk** |
| **Lernen (+Learnty)** | Laravel 11 | MySQL 8 | Not found | Not found | N/A poor fit | N/A | Tutor subjects ≠ LD groups | Addon | Addon | Integration APIs for Zoom/etc. | Social OAuth | No | Addon modules | Very High | Wrong product domain | Low | **Not Suitable** |
| **eClass** | Laravel 10 | MySQL/MariaDB | Not verified | Not verified | High | High | Institutes | Unknown | Claimed | Not verified | Custom | No | Unknown | Very High | SCORM unknown | Low | **High Migration Risk** |
| **LMS Hub** (ref) | Node.js | PostgreSQL | Claimed | Unknown | n/a | n/a | Unknown | Unknown | Claimed | Strong REST claimed | Custom | Unknown | Full source claimed | n/a (stack out of scope) | PHP requirement | Low | Out of scope alternative |

---

## 17. Recommended POC

Do **not** rebuild the LMS in the POC. Prove the highest-risk seams with representative LearnCabana data.

### 17.1 Primary POC target

**Infix LMS** + SCORM module + xAPI module (licensed), installed in an isolated environment.

### 17.2 Secondary POC (only if primary falters on packaging/lock-in)

**Rocket LMS** + Universal Plugin Bundle (for Private Mode, Organizations, SCORM, Assignments, Manual Enrollment).

### 17.3 POC checklist

1. Import one real LearnCabana user (including locale + job title + supervisor if present)  
2. Import one real group  
3. Import one course  
4. Import lessons/topics for that course  
5. Import enrollment (prefer a group-derived enrollment)  
6. Import completion/progress for that user/course  
7. Import one quiz and one attempt  
8. Demonstrate forced-completion handling (admin mark complete + 100% quiz with audit flag)  
9. Launch one real SCORM package from LearnCabana uploads  
10. Verify completion callback updates lesson/course progress  
11. Verify certificate generation for a mapped course  
12. Test bilingual behavior (EN/DE course + notification gate stub)  
13. Test assignment submit + authorized download path  
14. Test role/group permissions (learner vs group leader vs admin)  
15. Test API access (or document absence and prototype a custom endpoint)  
16. Test organization/tenant isolation **only if** using Edulab Tenanta or a custom tenancy spike  

### 17.4 POC exit criteria

Proceed only if:

- SCORM completion reliably marks the correct learning object complete  
- Forced completions are distinguishable in data  
- Group enrollment can be reproduced without hand-editing vendor core  
- Locked users can be refused login  
- Custom report job can email a supervisor rollup without core hacks  

Fail the candidate if SCORM completion is unreliable or requires core patches for basic progress writes.

---

## 18. Engineering Recommendation

### A. Best technical candidate for further proof-of-concept

**Infix LMS**

Justification:

- Only shortlisted PHP product that claims **both** SCORM and xAPI modules  
- Documents **group enrollment** behavior closest to LearnCabana’s scale path  
- Has institutes/org concepts, manual enrollment, assignments, certificates, student import  
- Modular addon approach is a better customization bet than a heavily encoded plugin bundle  

This is **not** an approval to buy-and-migrate. It is the best candidate to **disprove or confirm** under POC.

### B. Second candidate worth validating

**Rocket LMS** (with plugin bundle), primarily because Private Mode + Organizations + SCORM + assignments match the private training shape better than pure marketplace scripts.

I would validate Rocket only with eyes open on **ionCube**, encoded license files, xAPI absence, and plugin commercial coupling.

### C. Candidates that should not proceed (for LearnCabana replacement)

- **Lernen LMS** — tutor marketplace, not staff LMS; SCORM/xAPI not evidenced  
- **Academy LMS Laravel** as primary foundation — marketplace-first, weak group/supervisor fit, xAPI not evidenced  
- **eClass / EduEx / SkillGro-type clones** — insufficient evidence for LearnCabana’s SCORM-first private ops model  
- **WordPress LMS plugins** — do not solve the WordPress exit objective  

**Edulab + Tenanta** should not proceed as the *LMS replacement* foundation until SCORM/xAPI is solved, but it remains relevant as a **tenancy reference architecture**.

### D. What must be proven before committing

1. Infix (or Rocket) SCORM runtime against real LearnCabana packages  
2. xAPI completion path (Infix module) or accepted external LRS architecture  
3. Group enrollment semantics vs LearnDash groups  
4. Forced completion data model  
5. Extension points without core edits (requires source review)  
6. API strategy for any future portal  
7. Multi-tenant strategy decision (custom vs Edulab Tenanta vs postpone)  
8. CourseUpon API contracts if content will not migrate from LearnDash  

### E. Recommended decision stance

My assessment is that a CodeCanyon PHP LMS can shorten UI/admin scaffolding, but it will **not** remove the hard LearnCabana work: SCORM/xAPI completion bridging, group/supervisor domain, forced-completion auditability, bilingual notifications, assignment permissions, and historical data ETL.

I would treat CodeCanyon as a **possible accelerator for a single-tenant staff LMS rebuild**, not as a low-risk full replacement of LearnDash + Tin Canny + custom plugins.

---

## 19. Decisions / Information Required

1. Confirm whether course **content** must migrate or will be rebuilt (CourseUpon or otherwise).  
2. Provide CourseUpon API documentation if that authoring path is mandatory.  
3. Restore or supply production SCORM/xAPI packages for POC.  
4. Purchase/access source packages for Infix (and optionally Rocket) for code-level review.  
5. Decide retention policy for 28,503 assignments and ~5M xAPI rows (full migrate vs archive).  
6. Confirm whether enterprise SSO is required in phase 1.  
7. Confirm whether multi-store centralization is in scope for phase 1 or later.  
8. Inventory whether the proposed “228 stores” exist as independent systems.  

---

## 20. Next Steps

1. Obtain Infix LMS source + SCORM + xAPI modules.  
2. Perform code-level review: models, migrations, SCORM/xAPI services, events, auth, queues.  
3. Stand up POC environment and execute §17 checklist with sanitized LearnCabana samples.  
4. Parallel spike: external SCORM/xAPI engine option (fallback if modules fail).  
5. If POC passes, produce an ETL mapping spec (users, locks, groups, enrollments, progress, quizzes, certificates).  
6. Only then estimate build phases for custom LearnCabana compatibility services.  
7. Keep Edulab Tenanta docs as reference if/when multi-tenant centralization is scheduled.  

---

## 21. Appendix — Evidence & Verification Status

### 21.1 LearnCabana baseline

| Item | Status |
|---|---|
| WP + LearnDash 4.25.7 private staff LMS | VERIFIED (Step 1 audit) |
| Counts (users, locks, courses, activity, Tin Canny, assignments) | VERIFIED |
| Tin Canny primary completion path | VERIFIED |
| GrassBlade unused as content host | VERIFIED |
| No CourseUpon / no learner SSO / no tenant_id | VERIFIED |
| Uploads/SCORM binaries on local clone | PARTIALLY VERIFIED / missing files |

### 21.2 Catalogue / vendor evidence

| Item | Status |
|---|---|
| CodeCanyon LMS catalogue contents | VERIFIED (catalogue browse 18 Sep 2026) |
| Infix Laravel 12, SCORM module, xAPI module, groups via Skill & Pathway | VENDOR CLAIM / docs |
| Infix API settings = Maps/Fixer | VERIFIED docs |
| Rocket Private Mode, Organizations, SCORM plugin, ionCube requirement | VENDOR CLAIM (listing FAQ) |
| Edulab Tenanta Stancl multi-tenancy | VERIFIED vendor docs |
| Lernen tutor-marketplace nature | VERIFIED docs |
| Academy Laravel SCORM addon | VENDOR CLAIM |
| LMS Hub Node.js + REST + SCORM | VENDOR CLAIM (out of scope) |

### 21.3 Source-package review

| Candidate package in workspace | Status |
|---|---|
| Infix / Rocket / Academy / Lernen / Edulab | **Not present** |
| Code-level models/migrations/SCORM implementation | **Pending source-package access** |

### 21.4 Consistency check (pre-finalization)

- No unsupported features presented as facts.  
- Major LearnCabana custom behaviors (Tin Canny, locks, forced quizzes, supervisor reports, EN/DE gates, group enrollment) are explicitly mapped.  
- No candidate recommended without migration implications.  
- No “easy migration” claim made.  
- No framework selected merely because it is Laravel/PHP.  
- No SCORM/xAPI assumption without evidence labels.  
- No multi-tenancy assumption without evidence.  

---

*End of assessment.*
