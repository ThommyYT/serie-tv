<?php
header('Content-Type: application/json');
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/functions.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (isset($_SESSION['user']) && !isset($_GET['token'])) {
    $token = generateToken();
    $code = generateCode();
    $_SESSION['code'] = $code;
    /** @var \classes\Database $db */
    $db = $_SESSION['DB'];

    if ($db->setTokenUser($_SESSION['user'], $token)) {
        $user = $db->getUser($_SESSION['user']);

        $mail = new PHPMailer(true);

        try {
            // ⚙️ CONFIG SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // oppure altro SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'serietv.app1@gmail.com';
            $mail->Password   = 'sken lsii huyp haoh'; // NON password normale
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // 👤 MITTENTE / DESTINATARIO
            $mail->setFrom('serietv.app1@gmail.com', 'SerieTV');
            $mail->addAddress($user['email']);

            // ✉️ CONTENUTO
            $mail->isHTML(true);
            $mail->Subject = 'Verifica email';
            $mail->Body = "
                <h3>Verifica la tua email</h3>
                <h4>Metti questo codice: $code</h4>
            ";

            $mail->send();

            echo json_encode(['status' => 'email_sent', 'message' => $token]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $mail->ErrorInfo
            ]);
        }
    }
} else if (isset($_GET['token'])) {
    /** @var \classes\Database $db */
    $db = $_SESSION['DB'];

    if (isset($_POST['code'])) {
        if ($_POST['code'] == $_SESSION['code']) {
            unset($_SESSION['code']);
            if ($db->verifyUser($_GET['token'])) {
                echo json_encode(['status' => 'success', 'message' => 'Utente verificato']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Utente non verificato']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Codice non valido']);
        }
    } else {
        if ($db->verifyUser($_GET['token'])) {
            echo json_encode(['status' => 'success', 'message' => 'Utente verificato']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Utente non verificato']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nessun utente loggato']);
}
