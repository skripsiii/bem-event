<?php
// includes/functions.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load autoloader Composer (untuk PHPMailer)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load konfigurasi mail
require_once __DIR__ . '/../config/mail.php';

/**
 * Generate CSRF token dan simpan di session
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Kirim email konfirmasi pendaftaran menggunakan PHPMailer
 *
 * @param string $toEmail   Alamat email peserta
 * @param string $toName    Nama lengkap peserta
 * @param string $eventName Nama event yang didaftari
 * @param array  $eventData Data event lengkap (opsional, untuk info tambahan)
 * @return bool
 */
function sendRegistrationEmail($toEmail, $toName, $eventName, $eventData = []) {
    // Jika PHPMailer tidak tersedia, fallback ke mail() bawaan PHP
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $subject  = "Konfirmasi Pendaftaran Event – {$eventName}";
        $message  = "Halo {$toName},\n\nTerima kasih telah mendaftar pada event \"{$eventName}\".\n\nSalam,\nBEM Fasilkom Unsika";
        $headers  = "From: " . MAIL_FROM_EMAIL . "\r\n";
        return @mail($toEmail, $subject, $message, $headers);
    }

    $mail = new PHPMailer(true);

    try {
        // ── Server SMTP ──────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // ── Pengirim & Penerima ───────────────────────────────────────
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // ── Konten Email HTML ─────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = "✅ Konfirmasi Pendaftaran – {$eventName}";

        // Ambil info tambahan bila tersedia
        $eventDate = !empty($eventData['event_date'])
                     ? date('d M Y', strtotime($eventData['event_date'])) : '-';
        $quota     = $eventData['quota'] ?? '-';
        $category  = $eventData['category'] ?? '-';
        $type      = isset($eventData['event_type']) ? ucfirst($eventData['event_type']) : '-';

        $mail->Body = "
<!DOCTYPE html>
<html lang='id'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Konfirmasi Pendaftaran</title>
</head>
<body style='margin:0;padding:0;background:#f4f6f9;font-family:Poppins,Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f9;padding:30px 0;'>
    <tr>
      <td align='center'>
        <table width='600' cellpadding='0' cellspacing='0'
               style='background:#ffffff;border-radius:12px;overflow:hidden;
                      box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:600px;width:100%;'>

          <!-- Header -->
          <tr>
            <td style='background:linear-gradient(135deg,#7aaace,#355872);
                       padding:30px 40px;text-align:center;'>
              <h1 style='color:#ffffff;margin:0;font-size:24px;font-weight:700;
                          letter-spacing:0.5px;'>
                🎓 BEM Fasilkom Unsika
              </h1>
              <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;'>
                Sistem Pendaftaran Event
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style='padding:40px;'>
              <h2 style='color:#2c3e50;margin:0 0 8px;font-size:20px;'>
                ✅ Pendaftaran Berhasil!
              </h2>
              <p style='color:#555;margin:0 0 24px;font-size:15px;line-height:1.6;'>
                Halo <strong>{$toName}</strong>, pendaftaran Anda telah berhasil
                dikonfirmasi. Berikut detail pendaftaran Anda:
              </p>

              <!-- Info Card -->
              <table width='100%' cellpadding='0' cellspacing='0'
                     style='background:#f0f6fc;border-radius:10px;padding:24px;
                            border-left:4px solid #7aaace;'>
                <tr>
                  <td style='padding-bottom:12px;'>
                    <p style='margin:0;font-size:13px;color:#888;'>Nama Event</p>
                    <p style='margin:4px 0 0;font-size:16px;font-weight:600;color:#2c3e50;'>
                      {$eventName}
                    </p>
                  </td>
                </tr>
                <tr>
                  <td>
                    <table width='100%' cellpadding='0' cellspacing='0'>
                      <tr>
                        <td width='50%' style='padding-top:10px;vertical-align:top;'>
                          <p style='margin:0;font-size:12px;color:#888;'>Kategori</p>
                          <p style='margin:4px 0 0;font-size:14px;color:#2c3e50;'>{$category}</p>
                        </td>
                        <td width='50%' style='padding-top:10px;vertical-align:top;'>
                          <p style='margin:0;font-size:12px;color:#888;'>Tipe</p>
                          <p style='margin:4px 0 0;font-size:14px;color:#2c3e50;'>{$type}</p>
                        </td>
                      </tr>
                      <tr>
                        <td colspan='2' style='padding-top:14px;vertical-align:top;'>
                          <p style='margin:0;font-size:12px;color:#888;'>Tanggal Penyelenggaraan</p>
                          <p style='margin:4px 0 0;font-size:14px;font-weight:600;color:#2c3e50;'>
                            📅 {$eventDate}
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style='color:#555;margin:24px 0 0;font-size:14px;line-height:1.7;'>
                Harap simpan email ini sebagai bukti pendaftaran Anda.
                Jika ada pertanyaan, silakan hubungi panitia BEM Fasilkom Unsika.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style='background:#f8f9fa;padding:20px 40px;text-align:center;
                       border-top:1px solid #e9ecef;'>
              <p style='color:#aaa;margin:0;font-size:12px;'>
                &copy; " . date('Y') . " BEM Fasilkom Unsika &nbsp;|&nbsp; Email ini dikirim otomatis, harap tidak membalas.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>";

        // Versi teks polos (fallback)
        $mail->AltBody =
            "Halo {$toName},\n\n" .
            "Pendaftaran Anda pada event \"{$eventName}\" telah berhasil.\n\n" .
            "Detail:\n" .
            "- Kategori              : {$category}\n" .
            "- Tipe                  : {$type}\n" .
            "- Tanggal Penyelenggaraan: {$eventDate}\n\n" .
            "Salam,\nBEM Fasilkom Unsika";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Catat error ke log server, jangan tampilkan ke user
        error_log("PHPMailer Error [{$toEmail}]: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Rate limiting: maksimal pendaftaran per IP dalam 1 jam
 */
function checkRateLimit($ip, $conn) {
    $check = $conn->query("SHOW COLUMNS FROM registrations LIKE 'ip_address'");
    if (!$check || $check->num_rows === 0) {
        $conn->query("ALTER TABLE registrations ADD COLUMN ip_address VARCHAR(45) NULL");
        $conn->query("CREATE INDEX idx_registrations_ip ON registrations(ip_address, registered_at)");
        return true;
    }

    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $sql = "SELECT COUNT(*) as total FROM registrations
            WHERE ip_address = ? AND registered_at >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ip, $oneHourAgo);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $count < 10;
}

function flash($key) {
    if (isset($_SESSION[$key])) {
        $msg = htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8');
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}