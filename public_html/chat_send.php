<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function reply(bool $ok, string $msg, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if (!is_post() || !csrf_valid()) {
    reply(false, 'Session expired. Please refresh the page and try again.', 400);
}
// Honeypot: real visitors never fill this hidden field.
if (post('website') !== '') {
    reply(true, 'Thank you! We will contact you soon.');
}

$name    = mb_substr(post('name'), 0, 100);
$phone   = post('phone');
$message = mb_substr(post('message'), 0, 1000);

if ($name === '' || $message === '' || !valid_phone($phone)) {
    reply(false, 'Please enter your name, a valid phone number and a message.', 422);
}

// Simple flood control: max 5 messages per hour per visitor session.
$_SESSION['chat_times'] = array_values(array_filter($_SESSION['chat_times'] ?? [], fn($t) => $t > time() - 3600));
if (count($_SESSION['chat_times']) >= 5) {
    reply(false, 'You have sent several messages already. Please call us for urgent help.', 429);
}
$_SESSION['chat_times'][] = time();

db_insert('messages', [
    'name' => $name, 'phone' => $phone, 'message' => $message,
    'is_read' => 0, 'created_at' => date('Y-m-d H:i:s'),
]);
reply(true, 'Thank you! Your message has been sent. We will contact you soon.');
