<?php
require __DIR__ . '/../includes/linkedin-order-validation.php';
$cases = [
 ['Client@example.com', '+237 670 123 456', 'client@example.com', true],
 ['client@example.com', '+237 670 123 456', '670123456', true],
 ['client@example.com', '670123456', '+237 670 123 456', true],
 ['client@example.com', '670123456', '123456', false],
 ['client@example.com', '670123456', 'wrong@example.com', false],
 ['client@example.com', '670123456', 'nobody', false],
 ['client@example.com', '670123456', '670123457', false]
];
foreach ($cases as [$email, $phone, $contact, $expected]) {
 if (linkedin_order_contact_matches($email, $phone, $contact) !== $expected) throw new RuntimeException('Contact matching failed');
}
echo "7 tracking contact cases passed.\n";
