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

    // Best-effort auto-reply to the sender. Failure here must not fail the
    // request — the notification above already reached the recipient.
    try {
        $thanksBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body style="margin:0; padding:0; background-color:#f2f2f5; font-family: Arial, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f2f5; padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(20,20,30,0.08);">

          <!-- Header -->
          <tr>
            <td style="background-color:#0d0d14; padding:28px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:20px; font-weight:800; color:#f5b942; letter-spacing:0.2px;">
                    Kirtan<span style="color:#ffffff; font-weight:400;">.dev</span>
                  </td>
                  <td align="right" style="font-size:12px; color:#a6a6b3;">Frontend Developer</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Accent bar -->
          <tr>
            <td style="height:4px; line-height:4px; font-size:0; background-color:#f5b942; background-image:linear-gradient(90deg,#f5b942,#ff9d3d);">&nbsp;</td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:36px 32px 8px 32px;">
              <div style="display:inline-block; width:44px; height:44px; line-height:44px; text-align:center; border-radius:50%; background-color:#fdf2df; color:#c8890f; font-size:22px; font-weight:700; margin-bottom:16px;">&#10003;</div>
              <h1 style="margin:0 0 12px 0; font-size:22px; color:#16161f;">Thanks for reaching out, {$safeName}!</h1>
              <p style="margin:0 0 20px 0; font-size:15px; line-height:1.6; color:#4a4a55;">
                I've received your message and will get back to you as soon as possible &mdash; usually within
                <strong style="color:#16161f;">1&ndash;2 business days</strong>.
              </p>
            </td>
          </tr>

          <!-- Message recap card -->
          <tr>
            <td style="padding:0 32px 28px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f8fa; border:1px solid #ececf1; border-radius:10px;">
                <tr>
                  <td style="padding:18px 20px;">
                    <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#a6a6b3;">Subject</p>
                    <p style="margin:0 0 16px 0; font-size:14px; color:#16161f; font-weight:600;">{$safeSubject}</p>
                    <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#a6a6b3;">Your message</p>
                    <p style="margin:0; font-size:14px; line-height:1.6; color:#4a4a55;">{$safeMessage}</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Sign-off -->
          <tr>
            <td style="padding:0 32px 32px 32px; border-top:1px solid #ececf1;">
              <p style="margin:24px 0 0 0; font-size:14px; line-height:1.6; color:#4a4a55;">
                Talk soon,<br>
                <strong style="color:#16161f;">Kirtan Koshti</strong>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#f8f8fa; padding:18px 32px; text-align:center;">
              <p style="margin:0; font-size:12px; color:#9d9da8;">This is an automated confirmation &mdash; no need to reply to this email.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $thanksMail = new PHPMailer(true);
        $thanksMail->isSMTP();
        $thanksMail->Host       = SMTP_HOST;
        $thanksMail->Port       = SMTP_PORT;
        $thanksMail->SMTPAuth   = true;
        $thanksMail->Username   = SMTP_USERNAME;
        $thanksMail->Password   = str_replace(' ', '', SMTP_PASSWORD);
        $thanksMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $thanksMail->CharSet    = 'UTF-8';

        $thanksMail->setFrom(FROM_EMAIL, FROM_NAME);
        $thanksMail->addAddress($email, $name);

        $thanksMail->isHTML(true);
        $thanksMail->Subject = 'Thanks for contacting ' . SITE_NAME;
        $thanksMail->Body    = $thanksBody;
        $thanksMail->AltBody = "Thanks for reaching out, {$name}!\n\nI've received your message and will get back to you as soon as possible, usually within 1-2 business days.\n\nYour message:\nSubject: {$subject}\n\n{$message}";

        $thanksMail->send();
    } catch (PHPMailerException $e) {
        error_log('Contact form auto-reply error: ' . $thanksMail->ErrorInfo);
    }

    respond(true, 'Message sent successfully! I will get back to you soon.');
} catch (PHPMailerException $e) {
    error_log('Contact form mail error: ' . $mail->ErrorInfo);
    respond(false, 'Sorry, the message could not be sent right now. Please try again later or email me directly.', 500);
}
