<?php
ob_start();
require 'includes/config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Inserisci un'email valida.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);

                // Invia l'email con il link di reset usando PHPMailer
                $resetLink = HOSTNAME . BASE_PATH . "reset_password?token=" . $token;
                $subject = "Reset Password Ricettario";
                $message = "Ciao,<br>hai richiesto un reset della password per il tuo account Ricettario.<br>Clicca sul seguente link per reimpostare la tua password:<br><a href=\"" . $resetLink . "\">" . $resetLink . "</a><br><br>Il link vale per 1 ora.<br><br>Se non hai richiesto tu questo reset, ignora questa email.<br><br>Grazie,<br>Il Team Ricettario.";

                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;

                    //Recipients
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Subject = $subject;
                    $mail->Body    = $message;
                    $mail->AltBody = strip_tags($message); // Plain text for non-HTML mail clients

                    $mail->send();
                    $_SESSION['success'] = "Se l'email è presente nel nostro sistema, riceverai una mail con il link per il reset della password.";
                } catch (Exception $e) {
                    error_log("Errore invio email reset password a: " . $email . ". Errore Mailer: {" . $mail->ErrorInfo . "}");
                    $_SESSION['error'] = "Errore durante l'invio dell'email di reset. Riprova più tardi.";
                }
            } else {
                // Non rivelare se l'email esiste o meno per motivi di sicurezza
                $_SESSION['success'] = "Se l'email è presente nel nostro sistema, riceverai una mail con il link per il reset della password.";
            }
        } catch (PDOException $e) {
            error_log("Errore richiesta reset password: " . $e->getMessage());
            $_SESSION['error'] = "Errore del server. Riprova più tardi.";
        }
    }
    header("Location: " . BASE_PATH . "forgot_password");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title>Password Dimenticata - Ricettario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-3">
    <a href="<?php echo BASE_PATH; ?>login" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Torna al Login
    </a>
    <div class="container">
        <h1>Password Dimenticata</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        ?>
        <p>Inserisci la tua email per ricevere un link di reset della password.</p>
        <form method="POST" action="<?php echo BASE_PATH; ?>forgot_password">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Invia Link Reset</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>