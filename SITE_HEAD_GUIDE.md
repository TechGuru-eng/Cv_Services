# Favicons and page metadata

Use the existing HireMe icons in `assets/favicons` on every new page.
The root `favicon.ico` provides the browser default for new pages, and the
Next.js ebook app inherits its icons from `src/app/favicon.ico` and `apple-icon.png`.

For new PHP pages, include `includes/site-favicons.php` inside the HTML head,
after the charset and any base element. Set `$faviconBase` to the site's URL
prefix when it is hosted in a subdirectory. For HTML pages, copy the four icon
links from `index.html`, adjusting relative paths for the page's base URL.

Give each public page a unique title, description, absolute production canonical
URL, Open Graph metadata, and Twitter card metadata. Use the production origin
`https://moncvpro.hiremeguide.com`. Keep query strings, customer references, and
access tokens out of metadata. Add indexable pages to `sitemap.xml`.

Checkout, payment return, and private order pages should use `noindex` and should
not appear in the sitemap. Keep such pages crawlable so crawlers can see the
noindex directive. Metadata does not replace authentication.
