<?php
/**
 * Contact form configuration — TEMPLATE.
 *
 * Copy this file to config.php (which is gitignored) and fill in your real
 * values. Never commit config.php — it holds a live Gmail App Password.
 *
 * Setup:
 *  1. Turn on 2-Step Verification on the Google account you send mail from.
 *  2. Generate an App Password: https://myaccount.google.com/apppasswords
 *  3. Paste the 16-character app password below as SMTP_PASSWORD
 *     (keep the spaces or remove them — both work).
 */

define('RECIPIENT_EMAIL', 'you@example.com');
define('SITE_NAME', 'Your Name — Portfolio');

// Gmail account used to send the mail (usually the same as RECIPIENT_EMAIL).
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'you@example.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');

// From: header must match SMTP_USERNAME for Gmail to accept the send.
define('FROM_EMAIL', SMTP_USERNAME);
define('FROM_NAME', SITE_NAME);
