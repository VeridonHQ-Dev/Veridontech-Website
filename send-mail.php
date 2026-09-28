<?php
/**
 * Veridon Technologies — Unified Form Mailer Backend
 * Securely processes submissions from Community, Investor, and Contact forms
 * and delivers branded HTML notifications via PHPMailer to executive inboxes.
 */

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Allow CORS for local and staging environments
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Only POST requests are accepted.']);
    exit;
}

// Require Composer autoload with fallback check
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Backend configuration error: Dependencies not installed. Please run "composer install".'
    ]);
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

// Require Environment & Configuration Loader
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Parse incoming data (handles both FormData / application/x-www-form-urlencoded and JSON)
$data = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: [];
} else {
    $data = $_POST;
}

// Anti-spam honeypot check (if bot filled hidden field)
if (!empty($data['_gotcha']) || !empty($data['website_url'])) {
    echo json_encode(['success' => true, 'message' => 'Your inquiry has been received.']);
    exit;
}

// Determine form type
$formType = strtolower(trim($data['form_type'] ?? 'contact'));

// Helper to sanitize text
function clean_input($val) {
    if (is_array($val)) {
        return array_map('clean_input', $val);
    }
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

// Extract and sanitize standard fields
$fullName     = clean_input($data['fullName'] ?? ($data['name'] ?? ''));
$emailAddress = trim($data['emailAddress'] ?? ($data['email'] ?? ''));
$organization = clean_input($data['organization'] ?? ($data['orgName'] ?? ($data['applicantOrg'] ?? ($data['invOrg'] ?? ''))));
$userRole     = clean_input($data['role'] ?? ($data['userRole'] ?? ($data['invRole'] ?? '')));
$message      = clean_input($data['message'] ?? ($data['messageContent'] ?? ($data['applicantMessage'] ?? ($data['invNotes'] ?? ''))));

// Form-specific fields
$track        = clean_input($data['track'] ?? ($data['applicantTrack'] ?? ($data['involvementTrack'] ?? '')));
$scope        = clean_input($data['scope'] ?? ($data['invScope'] ?? ''));
$timeline     = clean_input($data['timeline'] ?? ($data['projectTimeline'] ?? ''));
$reason       = clean_input($data['reason'] ?? ($data['inquiryReason'] ?? ''));

// Validate required fields
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Please provide your full name.']);
    exit;
}

if (empty($emailAddress) || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

// Prevent email header injection
if (preg_match("/[\r\n]/", $emailAddress) || preg_match("/[\r\n]/", $fullName)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format detected.']);
    exit;
}

// Establish form metadata based on form type
$currentDate = date('D, d M Y — H:i:s \U\T\C');
$clientIp    = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

switch ($formType) {
    case 'community':
        $badgeText    = 'COMMUNITY EXPRESSION OF INTEREST';
        $formHeading  = 'Connect with Veridon Community';
        $trackTitle   = !empty($track) ? $track : 'Developer & Ecosystem Track';
        $subject      = "[Veridon Community] New Expression of Interest: {$fullName} ({$trackTitle})";
        $dataRows     = [
            'Applicant Name'       => $fullName,
            'Email Address'        => $emailAddress,
            'Organization/School'  => !empty($organization) ? $organization : 'Individual / Independent',
            'Interest Track'       => $trackTitle,
        ];
        break;

    case 'investor':
        $isPartner    = (strtolower($track) === 'partner');
        $badgeText    = $isPartner ? 'STRATEGIC ALLIANCE & PARTNERSHIP' : 'CAPITAL INVESTMENT INQUIRY';
        $formHeading  = $isPartner ? 'Strategic Partnership Engagement' : 'Investor Relations & Brief Request';
        $trackLabel   = $isPartner ? 'Strategic Partnership' : 'Capital Investment';
        $scopeLabel   = $isPartner ? 'Strategic Scope / Alliance Focus' : 'Target Investment Size';
        $subject      = "[Veridon Investors] {$trackLabel}: {$fullName}" . (!empty($organization) ? " — {$organization}" : "");
        $dataRows     = [
            'Full Name'            => $fullName,
            'Work Email'           => $emailAddress,
            'Organization / Fund'  => !empty($organization) ? $organization : 'Private Investor',
            'Title / Role'         => !empty($userRole) ? $userRole : 'Not specified',
            'Collaboration Track'  => $trackLabel,
            $scopeLabel            => !empty($scope) ? $scope : 'Not specified',
        ];
        break;

    case 'contact':
    default:
        $inquiryFocus = !empty($reason) ? $reason : 'General Enterprise Inquiry';
        $badgeText    = 'DIRECT ENTERPRISE INQUIRY';
        $formHeading  = 'Direct Executive & Engineering Inquiry';
        $subject      = "[Veridon Inquiries] {$inquiryFocus}: {$fullName}" . (!empty($organization) ? " — {$organization}" : "");
        $dataRows     = [
            'Full Name'            => $fullName,
            'Work Email'           => $emailAddress,
            'Organization / Firm'  => !empty($organization) ? $organization : 'Independent',
            'Title / Role'         => !empty($userRole) ? $userRole : 'Not specified',
            'Inquiry Focus'        => $inquiryFocus,
            'Estimated Timeline'   => !empty($timeline) ? $timeline : 'Not specified',
        ];
        break;
}

// Build table rows HTML
$tableRowsHtml = '';
$plainTextFields = '';
foreach ($dataRows as $label => $val) {
    $valSafe = nl2br(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
    $tableRowsHtml .= "
        <tr>
            <td style=\"padding:10px 14px; font-size:13px; font-weight:600; color:#C89B3C; text-transform:uppercase; letter-spacing:0.04em; background:#0e1713; border-bottom:1px solid #1c2b24; width:34%;\">{$label}</td>
            <td style=\"padding:10px 14px; font-size:14px; color:#F6F4EE; background:#121d18; border-bottom:1px solid #1c2b24;\">{$valSafe}</td>
        </tr>";
    $plainTextFields .= "• {$label}: {$val}\n";
}

$messageSafe = !empty($message) ? nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) : '<em>(No narrative provided)</em>';
$messagePlain = !empty($message) ? $message : '(No narrative provided)';

// Retrieve executive recipients from configuration
$recipients = get_mail_recipients();
$recipientEmails = array_map(function($r) { return $r['email']; }, $recipients);
$recipientListStr = htmlspecialchars(implode(', ', $recipientEmails), ENT_QUOTES, 'UTF-8');
$recipientListPlain = implode(', ', $recipientEmails);

// Compose Branded HTML Email Template
$bodyHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$subject}</title>
</head>
<body style="margin:0; padding:24px 0; background-color:#050807; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#F6F4EE;">
  <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#050807; padding:20px 10px;">
    <tr>
      <td align="center">
        <!-- Container -->
        <table role="presentation" width="620" border="0" cellspacing="0" cellpadding="0" style="max-width:620px; width:100%; background:#0B1210; border:1px solid #22362c; border-top:4px solid #C89B3C; border-radius:8px; overflow:hidden; box-shadow:0 12px 36px rgba(0,0,0,0.6);">
          
          <!-- Header -->
          <tr>
            <td style="padding:28px 32px 20px; background:linear-gradient(180deg, #111b16 0%, #0B1210 100%); border-bottom:1px solid #1c2b24;">
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="left">
                    <div style="display:inline-block; font-size:11px; font-weight:700; letter-spacing:0.12em; color:#C89B3C; text-transform:uppercase; margin-bottom:8px;">
                      ● {$badgeText}
                    </div>
                    <h1 style="margin:0; font-size:22px; font-weight:600; color:#F6F4EE; font-family:Georgia, serif; letter-spacing:-0.01em;">
                      {$formHeading}
                    </h1>
                  </td>
                  <td align="right" valign="top">
                    <span style="font-size:11px; color:#889990; font-family:monospace; background:#070C0A; padding:4px 8px; border-radius:4px; border:1px solid #1c2b24;">
                      {$clientIp}
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Key Submitter Card -->
          <tr>
            <td style="padding:24px 32px 16px;">
              <div style="background:#121d18; border-left:3px solid #C89B3C; border-radius:4px; padding:16px 20px;">
                <p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; color:#C89B3C; font-weight:600;">Lead / Submitter</p>
                <h2 style="margin:0; font-size:20px; color:#F6F4EE; font-weight:600;">
                  {$fullName}
                </h2>
                <p style="margin:6px 0 0; font-size:14px; color:#A4B3AC;">
                  <a href="mailto:{$emailAddress}" style="color:#C89B3C; text-decoration:none; font-weight:500;">{$emailAddress}</a>
                </p>
              </div>
            </td>
          </tr>

          <!-- Data Table -->
          <tr>
            <td style="padding:0 32px 20px;">
              <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; border-radius:6px; overflow:hidden; border:1px solid #1c2b24;">
                {$tableRowsHtml}
              </table>
            </td>
          </tr>

          <!-- Message Box -->
          <tr>
            <td style="padding:0 32px 24px;">
              <div style="background:#0e1713; border:1px solid #1c2b24; border-radius:6px; padding:18px 20px;">
                <div style="font-size:12px; font-weight:700; color:#C89B3C; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;">
                  Submitted Narrative / Inquiry Scope
                </div>
                <div style="font-size:14px; color:#F6F4EE; line-height:1.65; white-space:normal;">
                  {$messageSafe}
                </div>
              </div>
            </td>
          </tr>

          <!-- Reply-To Direct CTA -->
          <tr>
            <td style="padding:0 32px 28px;" align="center">
              <a href="mailto:{$emailAddress}?subject=Re:%20{$subject}" style="display:inline-block; background:#C89B3C; color:#070C0A; padding:12px 28px; border-radius:4px; font-size:13px; font-weight:700; text-decoration:none; letter-spacing:0.03em;">
                Reply Directly to {$fullName} &rarr;
              </a>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 32px; background:#070C0A; border-top:1px solid #1c2b24; font-size:11px; color:#6C7A73; text-align:center; line-height:1.5;">
              <p style="margin:0 0 6px; color:#889990;">
                Submitted on <strong>{$currentDate}</strong> from IP <strong>{$clientIp}</strong>
              </p>
              <p style="margin:0;">
                Delivered automatically to: <strong>{$recipientListStr}</strong>
              </p>
              <p style="margin:6px 0 0; color:#526159;">
                &copy; Veridon Technologies Limited &bull; Digital Trust &amp; AI Infrastructure
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

// Compose Plain Text Version
$bodyPlain = <<<TEXT
================================================================================
VERIDON TECHNOLOGIES — {$badgeText}
================================================================================

Form: {$formHeading}
Date: {$currentDate}
Submitter IP: {$clientIp}

SUBMITTER DETAILS:
{$plainTextFields}

SUBMITTED NARRATIVE / SCOPE:
{$messagePlain}

--------------------------------------------------------------------------------
To reply directly to this inquiry, email: {$emailAddress}
Delivered to: {$recipientListPlain}
================================================================================
TEXT;

// Read SMTP configuration from environment
$smtpHost       = env('SMTP_HOST', 'mail.veridontechnologies.com');
$smtpPort       = (int) env('SMTP_PORT', 465);
$smtpUser       = env('SMTP_USER', 'info@veridontechnologies.com');
$smtpPass       = env('SMTP_PASS', '');
$smtpSecure     = strtolower(env('SMTP_SECURE', 'smtps'));
$smtpTimeout    = (int) env('SMTP_TIMEOUT', 15);

$fallbackPort   = (int) env('SMTP_FALLBACK_PORT', 587);
$fallbackSecure = strtolower(env('SMTP_FALLBACK_SECURE', 'starttls'));

$fromAddress    = env('MAIL_FROM_ADDRESS', 'info@veridontechnologies.com');
$fromName       = env('MAIL_FROM_NAME', 'Veridon Notifications');

// Validate SMTP credentials presence
if (empty($smtpPass)) {
    error_log("PHPMailer error: SMTP_PASS is empty. Check your .env configuration.");
    echo json_encode([
        'success' => false,
        'message' => 'Mailer configuration error: SMTP credentials are not configured.'
    ]);
    exit;
}

// Helper closure to apply encryption mode to PHPMailer instances
$applyEncryption = function($mailer, $secureMode) {
    if ($secureMode === 'smtps' || $secureMode === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secureMode === 'starttls' || $secureMode === 'tls') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mailer->SMTPSecure = false;
        $mailer->SMTPAutoTLS = false;
    }
};

// Initialize and configure PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $applyEncryption($mail, $smtpSecure);
    $mail->Port       = $smtpPort;
    $mail->Timeout    = $smtpTimeout;
    $mail->CharSet    = 'UTF-8';

    // From and Reply-To
    $mail->setFrom($fromAddress, $fromName);
    $mail->addReplyTo($emailAddress, $fullName);

    // Add all executive recipients
    $first = true;
    foreach ($recipients as $recipient) {
        if ($first) {
            $mail->addAddress($recipient['email'], $recipient['name']);
            $first = false;
        } else {
            $mail->addCC($recipient['email'], $recipient['name']);
        }
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyPlain;

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your submission has been received by our executive leadership team.'
    ]);
    exit;

} catch (Exception $e) {
    // Failover to fallback port / TLS if primary fails
    try {
        $mailFallback = new PHPMailer(true);
        $mailFallback->isSMTP();
        $mailFallback->Host       = $smtpHost;
        $mailFallback->SMTPAuth   = true;
        $mailFallback->Username   = $smtpUser;
        $mailFallback->Password   = $smtpPass;
        $applyEncryption($mailFallback, $fallbackSecure);
        $mailFallback->Port       = $fallbackPort;
        $mailFallback->Timeout    = $smtpTimeout;
        $mailFallback->CharSet    = 'UTF-8';

        $mailFallback->setFrom($fromAddress, $fromName);
        $mailFallback->addReplyTo($emailAddress, $fullName);

        $first = true;
        foreach ($recipients as $recipient) {
            if ($first) {
                $mailFallback->addAddress($recipient['email'], $recipient['name']);
                $first = false;
            } else {
                $mailFallback->addCC($recipient['email'], $recipient['name']);
            }
        }

        $mailFallback->isHTML(true);
        $mailFallback->Subject = $subject;
        $mailFallback->Body    = $bodyHtml;
        $mailFallback->AltBody = $bodyPlain;

        $mailFallback->send();

        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your submission has been received by our executive leadership team.'
        ]);
        exit;

    } catch (Exception $e2) {
        error_log("PHPMailer error: " . $mail->ErrorInfo . " | Fallback error: " . $mailFallback->ErrorInfo);
        
        echo json_encode([
            'success' => false,
            'message' => 'Unable to send message at this moment. Please email ' . htmlspecialchars($fromAddress, ENT_QUOTES, 'UTF-8') . ' directly.'
        ]);
        exit;
    }
}
