# Public website FR/EN translation

French remains the original language. The FR/EN control on public pages loads
`assets/lang/en.json` from this website. No translation API, key, billing account,
or external translation request is required. Admin pages are excluded.

Deploy the updated public pages, `assets/js/site-language.js`,
`assets/css/site-language.css`, `assets/lang/en.json` and `sw.js` together.
The separate Next.js `ebook-landing` application is outside this PHP integration.

## Behavior

The choice is remembered in browser local storage. FR restores original wording.
EN translates catalogued visible text, titles, placeholders, accessibility labels,
image descriptions, button labels and dynamic interface messages. Templates
preserve changing names, references and totals. Form input and submitted option
values are preserved. Translation entries are inserted as text, never HTML.

Newly authored content needs an English catalog entry. Unknown text remains in
its original language. Images, PDFs, embedded third-party pages and native
JavaScript alert/confirm dialogs are not translated by this DOM feature.
Mark content `translate="no"` or `data-no-translate` to exclude it.
A missing/unavailable catalog restores French and offers a retry.

## Maintaining translations

Run `python tools/extract-site-translations.py` to inventory public-page wording.
Edit `tools/translation-overrides.json` for reviewed wording and dynamic templates.
You can also maintain `assets/lang/en.json` directly. Keep source keys normalized
(single spaces, trimmed). Templates use numbered placeholders such as `{0}`.

Optional offline generation uses `tools/build-local-translations.py --model PATH`,
with an extracted Argos French-to-English model and development dependencies
`ctranslate2` and `sentencepiece`. Generation runs locally; these dependencies and
model files are unnecessary on the deployed website. Review generated wording,
particularly legal and payment text, before publishing changed translations.

New root public pages should load:
`<script src="assets/js/site-language.js?v=4" defer></script>`
Use `../assets/js/site-language.js?v=4` for public pages one folder deeper.
Never add this loader to admin pages.

## Verification

In `tests/`, run `npm install --ignore-scripts` then `npm test`.
The DOM suite checks switching, restoration, dynamic messages, form values,
safe text rendering, catalog failure and admin exclusions without external calls.
