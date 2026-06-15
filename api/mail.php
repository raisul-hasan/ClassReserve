<?php
// Simple SMTP mail helper using direct socket connections.
require_once __DIR__ . '/../config.php';

function send_email($to, $subject, $message) {
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        return false;
    }
    
    $host = defined('SMTP_HOST') ? SMTP_HOST : '';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $username = defined('SMTP_USER') ? SMTP_USER : '';
    $password = defined('SMTP_PASS') ? SMTP_PASS : '';
    $from = defined('SMTP_FROM') ? SMTP_FROM : 'noreply@classreserve.local';
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'ClassReserve';

    if (empty($host)) {
        error_log("ClassReserve Mail: SMTP host is not configured.");
        return false;
    }

    try {
        $timeout = 5;
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            error_log("ClassReserve Mail SMTP Socket Error: $errstr ($errno)");
            return false;
        }

        $res = fgets($socket, 515); // Server banner
        
        fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
        $res = fgets($socket, 515);
        while (substr($res, 3, 1) === '-') {
            $res = fgets($socket, 515);
        }

        // Negotiate TLS if on port 587 or 465 (using STARTTLS)
        if ($port == 587) {
            fwrite($socket, "STARTTLS\r\n");
            $res = fgets($socket, 515);
            if (substr($res, 0, 3) == '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log("ClassReserve Mail: STARTTLS crypto handshake failed.");
                    fclose($socket);
                    return false;
                }
                // Resend EHLO after TLS handshake
                fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
                $res = fgets($socket, 515);
                while (substr($res, 3, 1) === '-') {
                    $res = fgets($socket, 515);
                }
            }
        }

        // SMTP Authentication
        if (!empty($username) && !empty($password)) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $res = fgets($socket, 515);
            if (substr($res, 0, 3) != '334') {
                error_log("ClassReserve Mail AUTH LOGIN failed: " . $res);
                fclose($socket);
                return false;
            }

            fwrite($socket, base64_encode($username) . "\r\n");
            $res = fgets($socket, 515);
            if (substr($res, 0, 3) != '334') {
                error_log("ClassReserve Mail Base64 username challenge failed: " . $res);
                fclose($socket);
                return false;
            }

            fwrite($socket, base64_encode($password) . "\r\n");
            $res = fgets($socket, 515);
            if (substr($res, 0, 3) != '235') {
                error_log("ClassReserve Mail AUTH password response failed: " . $res);
                fclose($socket);
                return false;
            }
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$from>\r\n");
        $res = fgets($socket, 515);
        if (substr($res, 0, 3) != '250') {
            error_log("ClassReserve Mail MAIL FROM failed: " . $res);
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        $res = fgets($socket, 515);
        if (substr($res, 0, 3) != '250' && substr($res, 0, 3) != '251') {
            error_log("ClassReserve Mail RCPT TO failed: " . $res);
            fclose($socket);
            return false;
        }

        // DATA
        fwrite($socket, "DATA\r\n");
        $res = fgets($socket, 515);
        if (substr($res, 0, 3) != '354') {
            error_log("ClassReserve Mail DATA command failed: " . $res);
            fclose($socket);
            return false;
        }
        
        // Headers & Body
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        
        $body = $headers . "\r\n" . $message . "\r\n.\r\n";
        fwrite($socket, $body);
        $res = fgets($socket, 515);
        if (substr($res, 0, 3) != '250') {
            error_log("ClassReserve Mail sending message data failed: " . $res);
            fclose($socket);
            return false;
        }
        
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("ClassReserve Mail Exception: " . $e->getMessage());
        return false;
    }
}
