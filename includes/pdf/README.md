# Receipt PDF dependency

HireMe uses the pinned `setasign/fpdf` 1.8.6 PHP library to produce downloadable, branded PDF receipts directly in memory. It requires no external PDF service, browser binary, Python runtime, or public receipt storage. The Composer lockfile records the dependency; ship this directory including `vendor`, or run `composer install --no-dev` here when deploying. Upstream: https://github.com/Setasign/FPDF. License is included in the vendor package.

Order data is loaded server-side after token authorization. PDF download auditing uses the existing order history. Independent resource purchases use a curated shared catalogue; they do not modify order snapshots or payment entitlements. Listed prices are supplied by HireMe; the shop determines checkout prices.
