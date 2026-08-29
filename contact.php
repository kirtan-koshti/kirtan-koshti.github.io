<?php
/**
 * Contact form handler for the portfolio site.
 *
 * Receives a POST from the form in index.html (via fetch/AJAX), validates
 * and sanitizes the input, then sends an email via Gmail SMTP using
 * PHPMailer. SMTP is used instead of PHP's mail() because most local dev
 * boxes (and some hosts) have no sendmail/MTA installed — SMTP works the
 * same everywhere as long as SMTP_PASSWORD in config.php is a valid Gmail
 * App Password.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}

// Honeypot: real users never fill this hidden field in.
$honeypot = trim((string) ($_POST['website'] ?? ''));
if ($honeypot !== '') {
    // Pretend success so bots don't learn the honeypot was tripped.
    respond(true, 'Message sent successfully!');
}

$name    = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Please provide a valid name.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}

if (mb_strlen($subject) < 2 || mb_strlen($subject) > 150) {
    $errors[] = 'Please provide a subject.';
}

if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
    $errors[] = 'Message should be between 10 and 5000 characters.';
}

// Block header-injection attempts (newlines in single-line fields).
foreach (['name' => $name, 'email' => $email, 'subject' => $subject] as $field => $value) {
    if (preg_match('/[\r\n]/', $value)) {
        $errors[] = 'Invalid characters detected in ' . $field . '.';
    }
}

if (!empty($errors)) {
    respond(false, implode(' ', $errors), 422);
}

$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

$mailSubject = '[' . SITE_NAME . '] New message: ' . $subject;

$body = <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto;">
  <h2 style="color:#c8890f;">New contact form submission</h2>
  <p><strong>Name:</strong> {$safeName}</p>
  <p><strong>Email:</strong> {$safeEmail}</p>
  <p><strong>Subject:</strong> {$safeSubject}</p>
  <hr>
  <p><strong>Message:</strong></p>
  <p>{$safeMessage}</p>
  <hr>
  <p style="font-size:12px;color:#888;">Sent from the contact form on {$_SERVER['HTTP_HOST']}</p>
</div>
HTML;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = str_replace(' ', '', SMTP_PASSWORD);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(RECIPIENT_EMAIL);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = $mailSubject;
    $mail->Body    = $body;
    $mail->AltBody = "New message from {$name} ({$email})\nSubject: {$subject}\n\n{$message}";

    $mail->send();

    respond(true, 'Message sent successfully! I will get back to you soon.');
} catch (PHPMailerException $e) {
    error_log('Contact form mail error: ' . $mail->ErrorInfo);
    respond(false, 'Sorry, the message could not be sent right now. Please try again later or email me directly.', 500);
}
