# Local preview with working public links

PHP's built-in web server does not read Apache `.htaccess` rewrites. Start it
from the project root with the local router:

```powershell
php -S 127.0.0.1:8787 -t . tools/dev-router.php
```

Then open `http://127.0.0.1:8787/index.html`. The router supports extensionless
pages, LinkedIn service and tracking aliases, blog articles and short links.
Apache deployments continue to use the existing `.htaccess` rules.

Run `python tools/check-public-links.py` to verify static navigation paths and
section anchors. The checker covers active public root, LinkedIn and Club pages.
Database-generated links, authenticated downloads, third-party sites and
post-payment destinations still require their corresponding data/access.
