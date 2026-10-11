Order notifications

Apply admin/sql/transactional-notifications.sql and admin/sql/sms-approval.sql on the production database before uploading the updated checkout and admin files. The local database is already migrated.

Upload the private root .env separately. Never commit it. Apache denies .env downloads; server process environment overrides its values. It configures SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION, SMTP_USERNAME, SMTP_FROM_EMAIL, SMTP_PASSWORD, TEXTBEE_API_KEY, TEXTBEE_DEVICE_ID, SMS_ENABLED, ORDER_ADMIN_PHONE and MONCV_BASE_URL. Keep MONCV_BASE_URL pointed at the public site in production.

Run `php tools/send-order-notifications.php` every minute through the hosting scheduler. Failed emails remain pending and retry after five minutes. SMS jobs only prepare approval requests; they never send automatically. The CLI worker also runs immediately after completed checkout or approval. The legacy historical email queue is not drained.

CV alerts are queued only after the final CV details form saves. LinkedIn alerts are queued after the complete validated checkout saves; Chariow orders are awaiting external payment verification at this point, so this is a completed order submission, not a verified payment. Approving payment sends customer SMS and email with the existing tracking link. Admin alerts contain the type, order date, amount and authenticated admin review URL. New completed-order emails to the admin and customer approval emails are automatic. Other email campaigns require an authenticated admin approval; automatic email follow-up scripts are disabled.

TextBee API acceptance means the Android gateway accepted the SMS; final carrier delivery depends on the device, SIM and network. A provider success followed by a worker crash before recording success can cause a repeated delivery, as with standard at-least-once outboxes.

Run `php tests/order-notifications.test.php` for isolated database queue checks. It uses a temporary table and fake delivery transport, and never contacts customers.

SMS approval mode

Every SMS entry point (orders, approvals, reminders, chat and settings tests) prepares a request in sms_approval_requests. Only an authenticated POST with the SMS dashboard CSRF token can approve delivery. The dashboard displays recipient, complete message, estimated segment cost, cancellation and approval controls. Identical normalized message/recipient pairs are unique permanently. Failed or uncertain attempts never retry automatically, and repeated approval cannot resend them.

cron-sms-relances.php is disabled. The delivery helper is private and reachable only through dashboard approval. sms_monthly_budget reserves estimated SMS segments before each provider call, under a database lock, with a fixed cap of 300. Existing website send logs seed the monthly counter. SMS sent outside this website are not included; TextBee remains the authoritative account usage source. Failed/uncertain attempts retain their reservation to avoid exceeding the limit.

Run php tests/sms-approval.test.php to verify approval, CSRF, segment estimation, quota, duplicate protection and crash behavior without provider calls.

Pending SMS messages can be edited and saved without sending. Saving recalculates segment cost and retains pending status. Original and edited fingerprints both protect against duplicate requests. Existing deployments must apply admin/sql/sms-message-editing.sql once; fresh deployments use the updated sms-approval.sql.

Current policy: completed-order SMS to the administrator and customer confirmation SMS after payment approval send automatically through the server-created transactional outbox. Both retain the monthly budget and one-attempt protections. Reminders, chat and test SMS still need dashboard approval. Pending reminders can be edited using “Enregistrer sans envoyer”; their segment estimate is recalculated before approval. Failed or uncertain SMS are never sent again automatically.
