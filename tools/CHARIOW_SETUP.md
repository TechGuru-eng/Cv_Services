Chariow LinkedIn payments

Apply admin/sql/chariow-webhook.sql to the production database. The local schema is already applied.

Create one Chariow Pulse for successful.sale and the products prd_zvevvoid, prd_w08gjak7 and prd_u06y9fgw. Endpoint: https://moncvpro.hiremeguide.com/api/chariow-webhook.php

Retrieve the Pulse signing secret from Automations → Pulses → select the Pulse → Overview → Signing secret. Save its exact whsec_... value as CHARIOW_PULSE_SECRET in the private root .env. It is not an API key. Do not post it in chat. The endpoint returns 503 until configured. One Pulse/secret handles all three products.

Run php tools/send-order-notifications.php every minute on production to drain notifications without delaying webhook acknowledgements. Provider SMS failures do not resend automatically.

Signatures must be sha256=<64 lowercase hex characters>, computed by HMAC-SHA256 over the original request body using the Pulse secret. Real deliveries require x-pulse-delivery-id. Signed dashboard test events with a note and no delivery ID return test_received without any payment changes. Delivery identifiers and sale identifiers are persisted so retries/replays cannot confirm twice or resend notifications. Only successful.sale with completed status, the configured product, exact XAF price, matching customer email, a matching reference and a pending Chariow payment may confirm an order.

Service links cannot reliably inject order_ref metadata. Do not invent query parameters. Missing references go into admin/linkedin/chariow-sales.php. An admin can enter the matching HM-LI-xxxxxx reference there; all validation still applies. Changed emails, discounts, currency changes or mismatched prices require separate review rather than automatic confirmation. No email-only matching is performed.

The browser return page is navigation only and never proves payment.

Official documentation: https://chariow.dev/en/guides/pulse-security and https://chariow.dev/en/guides/pulses

Admin notification timing: a Chariow order submission no longer alerts the admin before checkout. A matched successful sale queues the admin completed-order alert and customer approval notifications. A return from Chariow can queue that same admin event using the saved reference/access token; it says payment needs verification and never changes payment status. A return and webhook for the same matched order share the admin event key. A verified sale with no reliable order reference alerts the admin to associate the sale on the review page. Without the browser session token, the return page cannot identify an order; the signed sale webhook still alerts the admin.

Also apply admin/sql/chariow-sale-alerts.sql in production. The webhook drains notifications after receipt; the scheduled worker remains a fallback.
