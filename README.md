# Veridon Technologies — Corporate Website & Mailer Backend

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-darkgreen.svg)](LICENSE)
[![PHPMailer](https://img.shields.io/badge/PHPMailer-v7.1-blue.svg)](https://github.com/PHPMailer/PHPMailer)

Official web portal and unified enterprise mailer backend for **Veridon Technologies Limited** — a digital trust, sovereign AI infrastructure, and identity intelligence engineering firm.

---

## Architecture & Technology Stack

- **Frontend**: Semantic HTML5, modern Vanilla CSS (design tokens, glassmorphism, responsive grid layouts), and Vanilla JavaScript (hero canvas particle animations, responsive drawer navigation).
- **Backend**: PHP 8.0+ lightweight RESTful mail handler (`send-mail.php`) delivering multipart branded HTML and plaintext notifications to executive leadership.
- **Mailer Engine**: [PHPMailer](https://github.com/PHPMailer/PHPMailer) v7 with dual-port failover (SMTPS / Port 465 with automatic STARTTLS / Port 587 fallback).
- **Configuration & Security**: Decoupled environment loader (`config.php` + `.env`), honeypot bot defense, header-injection guards, input sanitization, and Apache security rules (`.htaccess`).

---

## Directory Structure

```text
Veridontech-Website/
├── images/                   # Executive leadership portraits & site media
│   ├── Bus Dev.jpeg          # Head of Business Development
│   ├── CEO.jpeg              # Chief Executive Officer
│   ├── CTO.png               # Chief Technical Officer
│   └── favicon.ico           # Website favicon
├── .env.example              # Environment variable template for credentials
├── .gitignore                # Git exclusions (credentials, vendor, OS files)
├── .htaccess                 # Apache server hardening (deny dotfiles/env)
├── about.html                # Corporate profile, mission, & executive team
├── career.html               # Talent acquisition & engineering roles
├── community.html            # Open corpora, AI research, & developer tracks
├── composer.json             # PHP Composer dependency specifications
├── composer.lock             # Exact locked Composer dependency tree
├── config.php                # Environment & configuration loader
├── contact.html              # Direct enterprise & inquiry interface
├── cookie-policy.html        # Cookie usage policy & compliance
├── footer.css                # Global footer stylesheets
├── hero-animation.js         # Interactive canvas/particle animation engine
├── hero-style.css            # Hero visual stage & canvas layout styling
├── index.html                # Corporate homepage & capability showcase
├── investors.html            # Investor relations & strategic alliance inquiries
├── LICENSE                   # Proprietary software license
├── logo-full.png             # Full Veridon Technologies branding
├── logo-mark.png             # Veridon emblem mark
├── mobile-nav.css            # Responsive navigation drawer styling
├── mobile-nav.js             # Mobile drawer toggle & focus management
├── privacy-policy.html       # Corporate data privacy governance
├── README.md                 # Project documentation
├── send-mail.php             # Unified AJAX form processing endpoint
└── services.html             # Enterprise solutions & AI infrastructure
```

---

## Getting Started

### 1. Requirements

- **PHP**: 8.0 or higher
- **Web Server**: Apache (with `mod_rewrite` & `mod_headers` recommended) or Nginx
- **Composer**: [getcomposer.org](https://getcomposer.org/)

### 2. Local Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/<your-username>/Veridontech-Website.git
   cd Veridontech-Website
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure Environment Variables**:
   Copy the example environment template and configure your credentials:
   ```bash
   cp .env.example .env
   ```

   Edit `.env` to define your SMTP parameters:
   ```ini
   SMTP_HOST=mail.yourdomain.com
   SMTP_PORT=465
   SMTP_USER=info@yourdomain.com
   SMTP_PASS=your_smtp_password
   SMTP_SECURE=smtps
   SMTP_TIMEOUT=15

   SMTP_FALLBACK_PORT=587
   SMTP_FALLBACK_SECURE=starttls

   MAIL_FROM_ADDRESS=info@yourdomain.com
   MAIL_FROM_NAME="Veridon Notifications"

   MAIL_RECIPIENTS="info@yourdomain.com:Veridon Info,ceo@yourdomain.com:Veridon CEO,cto@yourdomain.com:Veridon CTO"
   ```

4. **Serve Locally**:
   - **Using XAMPP**: Place folder in `htdocs` and visit `http://localhost/Veridontech-Website/`
   - **Using PHP Built-in Server**:
     ```bash
     php -S localhost:8000
     ```

---

## Forms & Email Delivery Pipeline

All interactive submissions on `contact.html`, `investors.html`, and `community.html` dispatch asynchronous JSON/FormData POST requests to `send-mail.php`:

1. **Anti-Spam Filter**: Checks hidden honeypot fields (`_gotcha`, `website_url`) before invocation.
2. **Input Sanitization**: Filters and escapes user input, validating email RFC compliance and preventing email header injection attacks.
3. **Branded Dispatch**: PHPMailer attempts primary delivery over SSL (Port 465). If network timeouts or SSL handshakes fail, it automatically fails over to TLS (Port 587).
4. **Recipients**: Submissions are routed to executive stakeholders specified in `MAIL_RECIPIENTS`.

---

## Security Practices

- **Zero Tracked Secrets**: Passwords and private credentials are excluded via `.gitignore` and loaded via `.env`.
- **Server Guard**: `.htaccess` denies direct web access to `.env`, `.git`, `composer.json`, and `.lock` files.
- **Client Sanitization**: All incoming form payloads are sanitized using `htmlspecialchars` with `ENT_QUOTES`.

---

## License

Copyright &copy; 2026 Veridon Technologies Limited. All rights reserved.
Unauthorized copying, modification, or distribution of this software is strictly prohibited. See [LICENSE](LICENSE) for details.
