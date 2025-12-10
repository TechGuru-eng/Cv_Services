# Configuration Anti-Spam (DNS Records)

To ensure your emails land in the Inbox (and not Spam), you MUST configure the following DNS records on your domain registrar (Hostinger, Namecheap, GoDaddy, etc.).

## 1. SPF Record (Sender Policy Framework)
This authorizes Brevo (Sendinblue) and Google (if you use Gmail) to send emails on your behalf.

*   **Type:** `TXT`
*   **Host/Name:** `@` (or blank)
*   **Value:** `v=spf1 include:_spf.google.com include:spf.brevo.com ~all`

## 2. DKIM Record (DomainKeys Identified Mail)
Brevo will provide you with a specific selector (e.g., `mail._domainkey.yourdomain.com`). You can find this in your **Brevo Dashboard > Senders & IPs > Domains**.

*   **Type:** `TXT`
*   **Host/Name:** `mail._domainkey` (This varies! Check Brevo dashboard)
*   **Value:** `k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQ...` (Long key from Brevo)

## 3. DMARC Record (Domain-based Message Authentication)
This tells receiving servers what to do if an email fails SPF/DKIM checks.

*   **Type:** `TXT`
*   **Host/Name:** `_dmarc`
*   **Value:** `v=DMARC1; p=none; rua=mailto:moncvpro@hiremeguide.com`

> **Note:** Start with `p=none` (monitoring mode). Once stable, you can switch to `p=quarantine` or `p=reject` for stricter security.
