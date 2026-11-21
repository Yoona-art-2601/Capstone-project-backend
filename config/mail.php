<?php
define('APP_URL', 'http://localhost:3000');
define('MAIL_OUTBOX', __DIR__ . '/../mail_outbox');

function send_mail($to, $subject, $html) {
    if (!is_dir(MAIL_OUTBOX)) mkdir(MAIL_OUTBOX, 0775, true);
    $safe_to = preg_replace('/[^a-z0-9@._-]/i', '_', $to);
    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $file = MAIL_OUTBOX . '/' . $now->format('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '-' . $safe_to . '.html';
    $page = "<!doctype html><html><head><meta charset=\"utf-8\"><title>" . htmlspecialchars($subject) . "</title></head>"
          . "<body style=\"font-family:Arial,sans-serif;background:#faf7f0;padding:24px\">"
          . "<p style=\"color:#666;font-size:12px\">To: " . htmlspecialchars($to) . "<br>Subject: " . htmlspecialchars($subject) . "<br>Sent: " . $now->format('Y-m-d H:i') . "</p>"
          . "<div style=\"background:#fff;border:1px solid #e6e1d5;border-radius:12px;padding:24px;max-width:520px\">$html</div>"
          . "</body></html>";
    return file_put_contents($file, $page) !== false;
}
?>
