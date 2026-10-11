# MonCVPro Audit Report

Scope: static review of the repository (git-tracked files) plus read-only HTTP checks against the local instance (`http://localhost/MonCv`). No data was modified or exfiltrated, and no secret values appear in this report.

**Coverage limits (read first):**
- No browser or screenshot tool was available, so Phases 2, 15 and 17 (visual, responsive-width and Lighthouse-style review) are covered only by code-level signals. They need a rendered pass at 360/390/430/768/1024/1440px.
- Not exercised: admin-authenticated pages, SMS/chat features, the Club, LinkedIn and ebook sub-apps, and the full HTML pages in depth. This is a first pass over roughly 225 tracked source files, not an exhaustive review.
- Production headers, HTTPS and hosting config were not tested. Items marked "(local)" were verified locally and must be re-checked on production.

---

## Executive Summary

- **Design/UX:** Not assessed visually. Code signals: every page loads Tailwind via the CDN runtime, so pages are heavy and may flash unstyled content. Meta and SEO coverage is uneven.
- **Technical quality:** The code uses prepared statements throughout what I read, which is good. Hygiene is the problem: roughly 90 debug, fix, setup, dump and migration scripts sit in the public web root.
- **Security posture:** Poor, mainly through exposure rather than clever bugs. Unauthenticated maintenance scripts, a web-readable `.git`, committed credentials and customer files, and "magic-link" CV retrieval keyed on guessable values.
- **Most important problems:** (1) `.git` reachable, (2) unauthenticated DB/maintenance scripts, (3) completed CVs retrievable by anyone who knows an email or order ref, (4) client-controlled price, (5) committed plaintext SMTP password and customer documents.

---

## Critical Findings

### C1. Repository metadata is web-readable
**Area:** Deployment | **Severity:** Critical
**Observed (local):** `/.git/config` and `/.git/index` return HTTP 200; `/node_modules/...` also returns 200. `.htaccess` has no deny rule, and there is no `.gitignore`.
**Why it matters:** If production matches, anyone can download the full history, including the SMTP password (C5) and customer uploads (C6), and recover source.
**Safe reproduction:** `curl -I https://<prod>/.git/config` should be 403/404.
**Fix:** Block `\.git`, `node_modules`, `*.md`, `*.sql`, `*.txt`, `*.bak`, `*.json` (except manifest) in `.htaccess` or the server config. Better: deploy a build artifact that contains none of them. Add a `.gitignore`.
**Files:** `.htaccess`, repo root.

### C2. Unauthenticated maintenance, debug and schema scripts in web root
**Area:** Access control | **Severity:** Critical
**Observed (local):** No auth guard on, for example, `admin/fix_everything.php`, `admin/get_schema.php`, `admin/debug_db.php`, `debug_db.php`, `check_recent_prospects.php`, `fix_payment_status.php`, `restore_all.php`, `force_fix.php`, `cleanup_bots.php`, `setup_*_db.php`, `migrate_*.php`, `sync_sql.php`, `admin/fix_db*.php`. Requests returned HTTP 200. `check_recent_prospects.php` returns recent prospect rows (names, phones). `fix_payment_status.php` runs `UPDATE orders SET payment_status='verified'` for several statuses on any GET. `create_admin.php` can reset an admin password if `ADMIN_PASSWORD` is set in the environment.
**Note:** My request to `admin/fix_everything` executed it locally; it is `CREATE TABLE IF NOT EXISTS`/`ALTER`-style and idempotent, but it proves any visitor can trigger DDL.
**Why it matters:** Schema disclosure, PII disclosure, arbitrary mass status changes and DB alteration by anonymous visitors. This is effectively a payment-state manipulation tool.
**Fix:** Delete all of these from the web root and run them from CLI or a private ops folder. Where kept, require `admin/auth.php`. Add `php_sapi_name() === 'cli'` guards. Remove `admin/test_security.php` and `admin/debug_*`.
**Files:** the list above (about 90 files, found by a scan for absence of any session/auth reference).

### C3. Completed CV (and order data) retrievable with only an email or order ref
**Area:** Authorization / IDOR | **Severity:** Critical
**Observed:** `retrieve-cv.php?ref=<email>` returns, without any proof of ownership, the latest order's `completed_cv_path`, name, amount and status, or the pack/sponsorship `pack_link`. `?ref=<order_ref>` does the same; the ref format is `CMD-yymmdd-XXXX`, where the suffix is only 4 hex characters from `uniqid()`. The POST form in the same file checks only email plus a typed name. `track-cv.php?ref=` also returns order data from the ref alone.
**Why it matters:** CVs contain phone, address and employment history. Emails are easy to guess or harvest, and the 4-char ref suffix on a known date is brute-forceable (no rate limit).
**Safe reproduction:** Use a test order you created; request `retrieve-cv?ref=<its email>` from a fresh session with no cookie.
**Fix:** Issue a random 128-bit unguessable token per order (`bin2hex(random_bytes(16))`), send it only to the customer, and look up by token. Never look up by email alone. Rate-limit. Serve final files through a PHP script that checks the token and `payment_status='verified'` rather than a direct path.
**Files:** `retrieve-cv.php`, `track-cv.php`, `save-order.php:~96`.

### C4. Price and discount are taken from the browser
**Area:** Payment integrity | **Severity:** High (Critical if admins verify by stated amount)
**Observed:** `paiement.html` reads `amount`, `original_amount`, `discount_amount`, `promo_code`, `package` from the URL query string and posts them; `save-order.php` stores `floatval($_POST['amount'])` and writes it to `orders` and `revenue` with no server-side price lookup. `is_sponsorship`, `sponsorship_qty` and `package_name` are also client-supplied; the pool credits derive from the client amount. Promo usage is incremented but the code is never re-validated server-side, and `usage_limit` is not enforced at save time (race-prone as well).
**Why it matters:** A customer can edit `?amount=100` and place an order for a CV priced at 15000. Revenue stats are inflated or deflated by client input. Sponsorship pool credits can be created from a fake order.
**Mitigating:** Payment is manual (proof upload plus admin verification), so this depends on admin diligence, but the admin UI will show the client-stated amount.
**Fix:** Server-side catalog of packages and prices; recompute total, promo and affiliate discount on the server; compare to what the admin verifies; enforce promo limits in one atomic `UPDATE ... WHERE usage_count < usage_limit`. Create the sponsorship pool only upon verification, never at order creation.
**Files:** `save-order.php`, `paiement.html:443-621`, `api/check_promo.php`, `admin/order-details.php`.

### C5. Plaintext SMTP password committed in source
**Area:** Secrets | **Severity:** High
**Observed:** `save-order.php` (twice) and `save-cv-details.php` (twice) contain a hard-coded mailbox password (inside comments or live code), with the same hosting account name. Also present: `admin/config/db.php` uses DB user `root` with an empty password and falls back to a hosting DB name.
**Fix:** Rotate the mailbox password now; load secrets from environment or a non-web-readable config outside the web root; remove from history (`git filter-repo`) since it is already in two commits. Use a dedicated least-privilege DB user in production.
**Files:** `save-order.php:184,244`, `save-cv-details.php:94,149`, `admin/config/db.php`.

### C6. Customer documents and PII committed to git
**Area:** Privacy | **Severity:** High
**Observed:** 62 files under `uploads/` are tracked (31 jpg, 17 png, 8 pdf, 5 docx, including `CV-*.docx/pdf` and payment proofs). Also tracked: `debug_output*.txt` (order rows), `promos_*.json`, schema dumps, and a `.zip`. Several of these are web-reachable (`debug_output.txt` returned 200).
**Fix:** Remove from the repo and history, add to `.gitignore`, move uploads outside the web root, and notify affected customers if the repo was ever shared. Check your obligations under applicable data-protection rules.

---

## Security Findings

### S1. `view-draft.php` serves draft CVs by sequential ID
**Severity:** High. `?id=N` loads any order's draft with no token, and its link goes on to `draft-payment.php?id=N`. Watermarking is CSS only (`oncontextmenu`/keyboard blocks are not security); the `<img src>` is the raw file path. **Fix:** random token per draft; serve a server-side watermarked, downscaled preview only; `unlink` access to the raw file.

### S2. File upload validation is extension-only
**Severity:** Medium-High. `save-order.php` and `save-cv-details.php` check only the client filename extension. No MIME or magic-byte check, no size cap, `mkdir(..., 0777)`. The `uploads/.htaccess` blocks `.php/.php5/.phtml` but not `.phar`, `.php7`, `.php8`, `.pht`, `.shtml`, nor `.html`/`.svg` (stored XSS if ever allowed). Extensions are currently whitelisted so RCE is unlikely, but the files are world-readable at a guessable-ish path (`md5(time().name)`, which is predictable). **Fix:** `finfo` MIME plus magic-byte check, max size, `random_bytes` names, store outside the web root and stream through an authorized script, set `php_flag engine off` and `Options -ExecCGI`.

### S3. Admin session and login hardening
**Severity:** Medium. No `session_regenerate_id()` after login (session fixation), no cookie flags (`HttpOnly`, `Secure`, `SameSite`), no lockout (only `sleep(1)`, which also lets an attacker tie up PHP workers), login CSRF is OK. `Security::getIP()` trusts `X-Forwarded-For`/`Client-IP`, so IP bans and logs are spoofable. Admin JSON endpoints (`admin/api/*`) check `$_SESSION['admin']` (good) but have no CSRF token; set `SameSite=Lax/Strict` to mitigate. **Fix:** regenerate on login, `session_set_cookie_params([...])`, per-account and per-IP attempt limiter, optional 2FA.

### S4. Debug output and error disclosure
**Severity:** Medium. `display_errors=1` in `admin/order-details.php` (an admin page that also runs `ALTER TABLE` on page load), `debug_status.php`, `dump_orders_fixed.php`, `fix_*.php`, `admin/exit_popups.php`, `admin/diag_analytics.php`. Many scripts echo `$e->getMessage()`. **Fix:** `display_errors=0` globally, log only; remove schema auto-migration from request paths.

### S5. Security headers and CORS
**Severity:** Medium. Present: `X-Frame-Options`, `X-Content-Type-Options`. Missing (local): CSP, HSTS, Referrer-Policy, Permissions-Policy. `X-XSS-Protection` is obsolete. `Access-Control-Allow-Origin: *` on `api/check_promo.php`, `get_rotating_portfolio.php`, `save_lead.php`, `save_school_lead.php`. Write endpoints with wildcard CORS and no rate limit allow spam from any origin. Tailwind and Lucide are loaded from CDNs (`unpkg.com/lucide@latest` is unpinned, a supply-chain risk) with no SRI, which blocks a strict CSP. **Fix:** restrict CORS to your domain, pin versions plus SRI, add CSP in report-only first.

### S6. Public data and endpoint exposure
**Severity:** Low-Medium. `api/check_promo.php?action=get_active` and `?code=` allow unlimited code guessing with no rate limit (valid vs invalid is distinguishable). `robots.txt` has no `Disallow` for `/admin`, `/uploads`, `/api`, and a malformed `bbox-agent` line. Public API endpoints such as `api/get_recent_sales.php` (not reviewed in depth) should be checked for PII in names/cities.

### S7. Input handling (positive plus gaps)
Prepared statements are used in everything I read (no SQL injection found in reviewed files). Output via `htmlspecialchars` is common. Gaps: `save-order.php` stores `htmlspecialchars`-encoded values in the DB (double-encoding risk and inconsistent escaping on output); `view-digital-cv.php` embeds `cv_data` JSON into a page (needs a check that it is encoded with `JSON_HEX_TAG`); `track-cv`/`retrieve-cv` build `wa.me` and QR URLs from stored data. Not tested: blog `content` rendering from `save_blog_post.php` (raw HTML from admin; fine if only admins write it).

---

## Business Logic Loopholes

1. **Fake or manipulated price** (C4): edit URL parameters.
2. **Promo abuse:** no per-customer limit, no server validation at order time, count incremented even if the order is later rejected; race condition at `usage_limit`.
3. **Duplicate transaction IDs:** `transaction_number` is only checked for non-empty. The same MoMo reference can back multiple orders.
4. **Rate limit is session-only** (`last_order_attempt` in `$_SESSION`): deleting the cookie bypasses it, enabling order spam into the admin queue and the SMS/relance pipeline.
5. **Sponsorship pool created at order time, not payment time** (C4): a donor "pays" nothing, yet `sponsorship_pool` credits exist. Check whether the pool is spendable before verification.
6. **Status changes unconstrained:** `admin/order-details.php` accepts any `status` string from POST (no allowed-list or state machine); `fix_payment_status.php` bulk-verifies.
7. **Revenue integrity:** `save-order.php` inserts into `revenue` at order creation (even though `pending`) and `order-details.php` inserts again on verification only if absent. Revenue may count unpaid orders.
8. **Affiliate code** is accepted from POST or session with no validation, so commissions can be claimed with any code.
9. **CV "audit" (api/analyze_cv.php) is simulated:** the score is seeded from job title plus file name/size via `srand(crc32(...))`; it is not analysing the document. Presenting it as an analysis is a trust/consumer-protection risk, and the scores are trivially gameable. Either implement real parsing or label it clearly as an estimate.

---

## UI/UX Findings (code-signal based; visual confirmation pending)

| Problem | Impact | Recommendation | Priority |
|---|---|---|---|
| Tailwind CDN runtime on every page (`output.css` exists but is unused by `index.html`) | Slow first paint, FOUC, the CDN console warning, blocked by strict CSP | Build `output.css` with the Tailwind CLI and link it | P1 |
| ~150 KB single `index.html`, 5 `<img>` without proven alt text, 2 lazy | Heavy landing page; accessibility gap | Split sections, add `alt`/`loading="lazy"`/dimensions | P2 |
| Many separate campaign pages (valentin, ramadan, camtel, flash, neptune, femme, 20mai) | Inconsistent brand and design systems, dead-end seasonal pages, maintenance burden | Template them from one component set; archive expired campaigns | P2 |
| Price is passed via URL to `paiement.html` | Visitors can see and edit the amount, which erodes trust | Pass a package ID; render price from the server | P1 |
| Manual payment flow (upload proof plus transaction ID) with no stated delivery time on the form | Drop-off and anxiety after payment | State "verification within X hours" and what happens next, with a status link | P1 |
| Draft preview uses scare watermark and blocked right-click | Feels hostile; trivial to bypass anyway | Server-watermarked preview, clear next step | P3 |
| Naming mix: HireMe / MonCV / MonCVPro / HireMeGuide across pages and emails | Brand inconsistency | Pick one brand and domain | P2 |

## Mobile Findings
Not measured (no browser). To check on first render: the sticky bottom action bar in `view-draft.php` (`sticky bottom-4`) covering content; the admin dashboards (`admin/*.php`, large tables) at 360 px; `paiement.html` file-upload and transaction fields; and the wide `index.html` hero. Viewport meta tags are present on all pages sampled.

## Functional Bugs
- `create_admin.php` returns HTTP 500 when env vars are unset (by design) but is publicly reachable.
- `.htaccess` canonicalization redirects `*.php` GETs to extensionless URLs; check forms or links that hard-code `.php` with query strings, since direct `track-cv.php?ref=` links were redirected (301) in my tests.
- `sw.js` caches under a fixed name `hireme-v1` while `.htaccess` sets `no-store` on all assets. The two strategies conflict, and customers may get stale files after deploys (no cache-version bump).
- Email sending in `save-order.php` and `save-cv-details.php` is commented out. Customers get no confirmation email, so the "what happens next" relies on the thank-you page only.
- `admin/order-details.php` runs `SHOW COLUMNS`/`ALTER TABLE` on every page load.
- `robots.txt` malformed line (`bbox-agent`).

## Performance Findings
1. Replace Tailwind CDN with the built `output.css` (193 KB; purge to a few tens of KB).
2. `.htaccess` disables caching for all images, JS and CSS (`no-store`), so every visit re-downloads everything. Use long-lived caching with versioned filenames, and keep `no-store` only for HTML/PHP.
3. Google Fonts with 5 families on `view-digital-cv.php`; reduce and use `display=swap`/self-host.
4. Compress and resize the large PNG/JPG assets in `assets/` and the root-level stock images (hundreds of KB each); serve WebP/AVIF.
5. Unpinned `lucide@latest` from unpkg adds a render-blocking third-party request.

## Quick Wins (each under an hour)
1. Add deny rules for `.git`, `node_modules`, `*.sql|*.md|*.txt|*.bak|*.zip` in `.htaccess`.
2. Delete or move all `debug_*`, `dump_*`, `fix_*`, `check_*`, `setup_*`, `migrate_*`, `test_*`, `restore_all`, `force_fix`, `create_admin` out of the web root.
3. Rotate the SMTP mailbox password; move to an env file.
4. `session_regenerate_id(true)` on admin login and set cookie flags.
5. Whitelist order statuses in `order-details.php`.
6. Turn off `display_errors`.
7. Fix `robots.txt` and add `Disallow: /admin/ /api/ /uploads/`.
8. Restrict CORS to your own origin.
9. Enable caching for static assets.
10. Pin Lucide and add SRI to CDN scripts.

---

## Priority Matrix

**P0 — Fix immediately:** C1 `.git`/`node_modules` exposure; C2 unauthenticated maintenance scripts; C5 rotate the SMTP password; C3 token-based CV retrieval; C6 purge customer files from repo and web root.
**P1 — Fix next:** C4 server-side pricing and promo logic; S1 draft IDOR; S2 upload hardening; S3 admin session and login hardening; duplicate transaction IDs; status state machine; pass package ID not price in URL; delivery-time messaging.
**P2 — Improve soon:** S4 error display; S5 headers, CORS and CSP; real or honestly labelled CV audit; Tailwind build and caching; brand and design-system consolidation; SEO gaps (missing canonical on `paiement`/`ecoles`, no meta description on `sponsors`, `robots`).
**P3 — Polish:** draft watermark UX, `sw.js` versioning, alt text and lazy-loading, retire seasonal pages.

## Top 10 Actions
1. Block `.git`, `node_modules` and sensitive file types at the web server, and verify on production.
2. Remove or protect every debug/fix/setup/dump/migrate script and `create_admin.php`.
3. Rotate the SMTP password (and any DB credentials); stop hard-coding secrets; purge them from git history.
4. Remove customer uploads and data dumps from the repo and history; move uploads outside the web root and serve them through an authorized script.
5. Replace email/order-ref lookup with unguessable per-order tokens for CV and draft retrieval; add rate limits.
6. Compute price, promo and affiliate discount on the server; make the sponsorship pool depend on verified payment.
7. Enforce unique transaction IDs and a status state machine; stop writing revenue for pending orders.
8. Harden admin auth: session regeneration, cookie flags, attempt lockout, trusted-proxy IP handling, optional 2FA.
9. Harden uploads: MIME and magic-byte checks, size limits, random names, no execution.
10. Replace the Tailwind CDN with a built stylesheet, enable asset caching, and run the rendered mobile and desktop visual pass that this audit could not do.

---

## Remediation status (applied in the working tree, not committed)

**Fixed in code:**
- C1/C2: `.htaccess` now returns 403 for `.git`, `node_modules`, `_ops`, `admin/config|sql|includes`, sensitive extensions and dump files. About 85 debug/fix/setup/dump/migrate scripts and data dumps were moved to `_ops/` (deny-all, not deleted). `.gitignore` added.
- C3: `retrieve-cv.php` no longer returns data for an email-only link; refs are now 8 random hex characters (was 4); rate limits on `retrieve-cv` and `track-cv`.
- C4 (partial): `save-order.php` validates name, email and amount, rejects duplicate transaction IDs, enforces promo validity and usage limit atomically (invalid codes are flagged `INVALID:` on the order), no longer writes revenue at order creation, and sponsorship credits stay locked (0) until `order-details.php` verifies the payment.
- C5 (code only): the SMTP password is removed from source (`getenv('SMTP_PASSWORD')`).
- S1: `view-draft.php` and `draft-payment.php` require the matching `ref`; the admin-generated draft link includes it.
- S2: new `includes/upload_helper.php` (extension plus real MIME check, size cap, random names, rejects PHP in images) used by `save-order`, `save-cv-details`, `save-reservation`, `draft-payment`. `draft-payment.php` previously accepted any extension. `uploads/.htaccess` hardened.
- S3: admin login has session regeneration, cookie flags, per-IP lockout; `Security::getIP()` trusts only `REMOTE_ADDR`.
- S4/S5: `display_errors` off; HSTS, Referrer-Policy and Permissions-Policy added; CORS wildcard replaced by a hiremeguide.com allowlist; rate limits on public `api/save_*` and `check_promo`.
- Order status whitelisted in `order-details.php`; static asset caching enabled; `robots.txt` fixed; CV audit labelled as an estimate with upload checks.
- Correction to this report's own first draft: the admin API endpoints are session-guarded, and only four admin scripts were unguarded (moved to `_ops/`).

**Still requires you (cannot be done from code):**
1. **Rotate the SMTP mailbox password now** and set `SMTP_PASSWORD` in the server environment. The old value remains in git history.
2. **Purge git history** of the password, 62 customer files in `uploads/`, and the dump files (`git filter-repo`), then force-push; this rewrites history, so I did not do it.
3. **Create a dedicated MySQL user** (not `root` with an empty password) and put credentials in an environment file.
4. **Server-side price catalog:** amounts are still client-supplied (validated for range only). It needs a package/price table or config that I could not infer from the code.
5. Old draft links (`view-draft?id=N`) sent to customers stop working until re-sent from the admin page.
6. Not done: CSP, SRI/pinning for CDN scripts, Tailwind build, visual/mobile pass, admin-side upload validation (`admin/order-details.php`, `admin/cv-models.php`), 2FA.
