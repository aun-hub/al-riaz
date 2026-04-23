<?php
/**
 * Al-Riaz Associates — Mailer
 * Thin wrapper around PHPMailer (vendored at /vendor/phpmailer) that reads
 * SMTP config from the `settings` DB table via getSmtpConfig().
 */

require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send an email via SMTP using the saved admin config.
 *
 * @param string $to       Recipient email.
 * @param string $subject  Subject line.
 * @param string $body     Body (HTML or plain).
 * @param array  $opts     html=>bool, alt_body=>string, to_name=>string, encryption=>'tls'|'ssl'|''
 * @return array ['ok'=>bool, 'error'=>string?]
 */
if (!function_exists('sendMail')) {
    function sendMail(string $to, string $subject, string $body, array $opts = []): array {
        $cfg = getSmtpConfig();

        if ($cfg['host'] === '') {
            return ['ok' => false, 'error' => 'SMTP host is not configured in Settings → SMTP Config.'];
        }

        $m = new PHPMailer(true); // throw exceptions
        try {
            $m->isSMTP();
            $m->Host       = $cfg['host'];
            $m->Port       = (int)$cfg['port'];
            $m->SMTPAuth   = ($cfg['user'] !== '');
            $m->Username   = $cfg['user'];
            $m->Password   = $cfg['pass'];
            $m->CharSet    = 'UTF-8';
            $m->Timeout    = 15;

            // Auto-pick encryption from port unless the caller overrides.
            $enc = $opts['encryption'] ?? null;
            if ($enc === null) {
                $enc = match ((int)$cfg['port']) {
                    465                 => PHPMailer::ENCRYPTION_SMTPS,
                    25, 1025, 2525      => '', // typical plaintext / local dev ports
                    default             => PHPMailer::ENCRYPTION_STARTTLS,
                };
            }
            if ($enc !== '') $m->SMTPSecure = $enc;

            $fromEmail = $cfg['from_email'] !== '' ? $cfg['from_email'] : ($cfg['user'] ?: 'no-reply@localhost');
            $m->setFrom($fromEmail, $cfg['from_name'] ?: 'Al-Riaz Associates');
            $m->addAddress($to, (string)($opts['to_name'] ?? ''));
            if ($cfg['reply_to'] !== '') $m->addReplyTo($cfg['reply_to']);

            $m->isHTML((bool)($opts['html'] ?? false));
            $m->Subject = $subject;
            $m->Body    = $body;
            if (!empty($opts['alt_body'])) $m->AltBody = $opts['alt_body'];

            $m->send();
            return ['ok' => true];
        } catch (Throwable $e) {
            $err = $m->ErrorInfo !== '' ? $m->ErrorInfo : $e->getMessage();
            error_log('[sendMail] ' . $err);
            return ['ok' => false, 'error' => $err];
        }
    }
}
