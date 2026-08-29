# Kirtan Koshti — Portfolio

Dark/gold themed personal portfolio. Static HTML/CSS/JS frontend with a PHP + PHPMailer (Gmail SMTP) contact form backend.

## Structure

- `index.html` — all page content/sections
- `assets/css/style.css` — design system + layout
- `assets/js/script.js` — nav, scroll reveals, animated skill bars, contact form AJAX
- `contact.php` — validates and emails contact form submissions via Gmail SMTP
- `config.example.php` — template for `config.php` (see setup below)

## Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Copy the config template and fill in your real values:
   ```bash
   cp config.example.php config.php
   ```
   In `config.php`, set `RECIPIENT_EMAIL`, `SMTP_USERNAME`, and `SMTP_PASSWORD`
   (a Gmail [App Password](https://myaccount.google.com/apppasswords) — requires
   2-Step Verification to be enabled on the account).
3. Serve it with PHP:
   ```bash
   php -S localhost:8000
   ```
   Then open http://localhost:8000/

## Notes

- `config.php` is gitignored — it holds a live email credential and must never be committed.
- **GitHub Pages will not run `contact.php`** (Pages only serves static files). Deploy to a host that
  runs PHP (e.g. shared hosting, a VPS, Render, etc.) for the contact form to work; the rest of the
  site works fine as static HTML anywhere.
