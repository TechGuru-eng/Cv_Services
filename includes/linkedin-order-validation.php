<?php
/** Match the full order contact, allowing Cameroon's optional country prefix. */
function linkedin_order_contact_matches(string $email, string $phone, string $contact): bool
{
    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) return strcasecmp($email, $contact) === 0;
    if (preg_match('/[a-zA-Z@]/', $contact)) return false;
    $digits = preg_replace('/[^0-9]/', '', $contact);
    $stored = preg_replace('/[^0-9]/', '', $phone);
    return strlen($digits) >= 9 && ($digits === $stored || '237' . $digits === $stored || $digits === '237' . $stored);
}
