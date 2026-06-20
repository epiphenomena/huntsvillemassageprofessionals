<?php
/**
 * Tiny dependency-free mailer.
 *
 * If SMTP is configured (SMTP_HOST set in config.php), mail is sent via
 * authenticated SMTP — required for reliable delivery on DreamHost using a
 * login such as smtp-auth@huntsvillemassageprofessionals.com. Otherwise it
 * falls back to PHP mail(). Either way, form submissions are always saved to
 * the CRM regardless of whether the email goes out.
 *
 * Supports implicit TLS (port 465) and STARTTLS (port 587), AUTH LOGIN.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Send a plain-text email. Returns true on success.
 * Never throws — logs and returns false so callers can ignore failures.
 */
function hmp_send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    try {
        if (defined('SMTP_HOST') && SMTP_HOST !== '') {
            return smtp_send($to, $subject, $body, $replyTo);
        }
        // Fallback: PHP mail().
        $headers = 'From: ' . mail_from_header();
        if ($replyTo) {
            $headers .= "\r\nReply-To: " . $replyTo;
        }
        $headers .= "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
        return @mail($to, mime_subject($subject), $body, $headers);
    } catch (Throwable $e) {
        error_log('hmp_send_mail: ' . $e->getMessage());
        return false;
    }
}

function mail_from_header(): string
{
    $name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : BUSINESS['name'];
    $addr = defined('MAIL_FROM') && MAIL_FROM ? MAIL_FROM : BUSINESS['email'];
    return mime_word($name) . ' <' . $addr . '>';
}

/** RFC 2047 encode a header value only if it contains non-ASCII. */
function mime_word(string $s): string
{
    if (preg_match('/[\x80-\xFF]/', $s)) {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }
    return $s;
}

function mime_subject(string $s): string
{
    return mime_word($s);
}

/** Authenticated SMTP send. */
function smtp_send(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $host = SMTP_HOST;
    $port = (int) SMTP_PORT;
    $transport = $port === 465 ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";

    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($transport, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        error_log("SMTP connect failed ($errno): $errstr");
        return false;
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Last line of a (possibly multi-line) reply has a space at pos 3.
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = static function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $ok = static fn (string $resp, int $code): bool => strncmp($resp, (string) $code, 3) === 0;

    $fail = static function (string $why) use ($fp): bool {
        error_log('SMTP: ' . trim($why));
        fclose($fp);
        return false;
    };

    if (!$ok($read(), 220)) {
        return $fail('no 220 greeting');
    }

    $ehloHost = $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost';
    if (!$ok($cmd("EHLO {$ehloHost}"), 250)) {
        return $fail('EHLO rejected');
    }

    // STARTTLS for non-implicit-TLS ports (e.g. 587).
    if ($port !== 465) {
        if (!$ok($cmd('STARTTLS'), 220)) {
            return $fail('STARTTLS rejected');
        }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT
            | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (!stream_socket_enable_crypto($fp, true, $crypto)) {
            return $fail('TLS negotiation failed');
        }
        if (!$ok($cmd("EHLO {$ehloHost}"), 250)) {
            return $fail('EHLO after STARTTLS rejected');
        }
    }

    if (!$ok($cmd('AUTH LOGIN'), 334)) {
        return $fail('AUTH LOGIN unsupported');
    }
    if (!$ok($cmd(base64_encode(SMTP_USER)), 334)) {
        return $fail('username rejected');
    }
    if (!$ok($cmd(base64_encode(SMTP_PASS)), 235)) {
        return $fail('authentication failed — check SMTP_USER / SMTP_PASS');
    }

    $from = defined('MAIL_FROM') && MAIL_FROM ? MAIL_FROM : SMTP_USER;
    if (!$ok($cmd("MAIL FROM:<{$from}>"), 250)) {
        return $fail('MAIL FROM rejected');
    }
    $rcpt = $cmd("RCPT TO:<{$to}>");
    if (!$ok($rcpt, 250) && !$ok($rcpt, 251)) {
        return $fail('RCPT TO rejected: ' . $rcpt);
    }
    if (!$ok($cmd('DATA'), 354)) {
        return $fail('DATA rejected');
    }

    $headers = 'From: ' . mail_from_header() . "\r\n";
    $headers .= "To: <{$to}>\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: <{$replyTo}>\r\n";
    }
    $headers .= 'Subject: ' . mime_subject($subject) . "\r\n";
    $headers .= 'Date: ' . date('r') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

    // Normalise newlines to CRLF and dot-stuff lines beginning with '.'.
    $bodyOut = str_replace(["\r\n", "\r", "\n"], ["\n", "\n", "\r\n"], $body);
    $bodyOut = preg_replace('/^\./m', '..', $bodyOut);

    fwrite($fp, $headers . "\r\n" . $bodyOut . "\r\n.\r\n");
    if (!$ok($read(), 250)) {
        return $fail('message not accepted');
    }

    $cmd('QUIT');
    fclose($fp);
    return true;
}
