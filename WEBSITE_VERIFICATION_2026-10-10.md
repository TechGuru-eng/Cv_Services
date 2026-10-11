# Website verification — 10 October 2026

Changes were made in the local MonCv workspace. Existing unrelated changes were preserved. No deployment, live payment, customer email or customer SMS was initiated.

## Corrections

- CV checkout now validates prices against a server catalog, includes paid delivery and ebook options, validates active promotions and product restrictions, and reserves promo usage within the order transaction. Rejected orders roll back and remove their uploaded proof. Sponsorship quantities determine the price and credit count. Expired dated campaigns and closed or sold-out flash products cannot be ordered directly.
- Manual promo validation now returns the code needed by checkout. Delivery choices survive navigation to payment. Edited WhatsApp fields are no longer overwritten by stale URL parameters. Successful order responses include their reference. Proof-only campaign payments receive a unique internal reference rather than all colliding on `N/A`.
- CV audit no longer fabricates scores from a filename and file size. It submits the actual PDF/image to Gemini, requires explicit consent, checks real file type and a 5 MB limit, validates the returned scoring structure, and renders findings as text. Missing configuration and provider failures return visible errors. Results are clearly indicative.
- Audit report capture requires a recent successful audit, avoids schema migrations on public requests, and reports an email as queued rather than already delivered. Queue failures are surfaced.
- Digital CV data is validated and escaped before insertion into scripts and HTML templates. Missing lists receive safe defaults. Builder updates target only a lead owned by the current session; a phone number alone no longer permits overwriting another CV. New public slugs use cryptographic randomness.
- Member login and registration regenerate sessions, set protected cookies and enforce rate limits. Deleted member sessions are handled safely.
- Member request uploads and payment proofs use the shared MIME, extension and size checks. CV quota consumption is atomic and checks membership expiry. Expired active members can reach the renewal payment page.
- Membership payment approval checks payment ownership and pending status under a transaction, rejects repeated approval, and resets the allowance on renewal.
- Customer search requires an admin session. Public migration execution is blocked. Apache also blocks test/tool directories and the PDF vendor directory.
- The SMS cron rejection response no longer reveals its access token. Public email auto-pilot entry points require a configured cron secret. MailBatch endpoints use an environment secret instead of an embedded shared token.
- TextBee webhooks verify the raw request body with HMAC-SHA256 before database access, following the [TextBee webhook specification](https://textbee.dev/docs/webhooks).
- Embedded SMTP passwords were removed from five email handlers. Rate-limit reads and writes now share one exclusive lock. Database connection failures return HTTP 503 and JSON for JSON endpoints.
- `npm test` now runs all JavaScript regression suites sequentially, rather than the root placeholder or only the translation suite.

## Verified locally

| Check | Evidence |
|---|---|
| JavaScript regression suites | 19 tests passed: audit consent/error handling/HTML injection, LinkedIn checkout and receipt authorization, live polling, translation |
| Public navigation | 239 internal links and anchors across 64 public pages resolve; route aliases checked |
| PHP syntax | All 253 application PHP files at the full-lint checkpoint passed; later changed/new webhook and integration files were linted separately |
| Server page rendering | 23 selected public/admin pages rendered without PHP warnings |
| Pricing | 14 checks passed, including altered amounts, unpaid add-ons, restricted/exhausted promos, sponsorship inflation and expired offers |
| Membership | Payment ownership, allowance reset and duplicate approval checks passed |
| CV data and AI response validation | Injection protection, missing-field normalization and valid/invalid response cases passed |
| Webhook authentication | 5 signature cases passed, including modified body and missing/wrong secrets |
| Existing PHP flow checks | LinkedIn contact/follow-up checks and prospect/cart/duplicate/SMS audience checks passed |

Rendering covered reservation, CV care/models/builders, blog, refunds, ambassadors, ebook, LinkedIn landing/checkout/diagnostic/tracking, Club login/register, and selected admin dashboard/live/prospects/finance/SMS/Club/LinkedIn pages. Rendering confirms PHP and database queries complete; it is not proof that every button or form works.

Database integration fixtures used transactions and were rolled back. JavaScript tests used simulated DOMs; the existing receipt test exercised the local server and verified that invalid or absent tokens cannot download the receipt.

## Required configuration

Set these in the PHP server environment; do not commit secret values:

- `GEMINI_API_KEY` (or `GOOGLE_API_KEY`) for CV analysis and the existing AI generators. `GEMINI_AUDIT_MODEL` optionally overrides the audit model; default is `gemini-3.8-flash`. The integration follows Google's [structured output REST format](https://ai.google.dev/gemini-api/docs/generate-content/structured-output).
- `SMTP_PASSWORD` for the configured SMTP mailboxes. Rotate the previously embedded password with the mail provider; removing it from current files does not remove it from Git history.
- `MONCV_CRON_TOKEN` for HTTP auto-pilot invocations, supplied as `Authorization: Bearer ...`. CLI auto-pilot jobs remain available.
- `MONCV_MAILBATCH_TOKEN` for the eight MailBatch endpoints; update the external integration to use the same newly generated secret.
- `TEXTBEE_WEBHOOK_SECRET` matching the TextBee subscription signing secret.

The Gemini and SMTP environment variables were absent locally. Successful AI generation and email delivery therefore remain unverified. No real documents were sent to an AI provider during testing.

## Remaining verification limits

- The browser initially opened the homepage, but subsequent browser access was blocked because the browser's security policy check was unavailable. It was not bypassed. Full rendered interaction, responsive layouts and accessibility checks remain incomplete.
- Production HTTPS, rewrite rules, deployment headers, payment-provider callbacks, actual payment verification, delivered-document access, email delivery and SMS delivery were not exercised on the deployed host.
- Legacy CV retrieval still supports knowledge of an order reference or email plus name, and delivered files still use upload paths. A complete private-download migration needs per-order ownership tokens and compatible changes to delivery links; this audit does not certify those legacy flows as secure.
- The initial parallel test run exhausted Windows memory. Sequential JavaScript tests and PHP linting with CLI OPcache disabled passed. Existing unrelated trailing whitespace was reported by the repository-wide diff check and left intact.

This report confirms the listed corrections and checks. It does not certify that every website feature works perfectly or that every possible security issue has been eliminated.
