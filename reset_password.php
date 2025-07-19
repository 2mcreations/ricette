<?php
ob_start();
require 'includes/config.php';
session_start();

$token = $_GET['token'] ?? '';
$error = '';

if (empty($token)) {
    $_SESSION['error'] = "Token di reset mancante.";
    header("Location: " . BASE_PATH . "login");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || strtotime($user['reset_token_expires_at']) < time()) {
        $_SESSION['error'] = "Token non valido o scaduto.";
        header("Location: " . BASE_PATH . "login");
        exit;
    }
} catch (PDOException $e) {
    error_log("Errore verifica token reset: " . $e->getMessage());
    $_SESSION['error'] = "Errore del server. Riprova più tardi.";
    header("Location: " . BASE_PATH . "login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Le password non corrispondono.";
    } elseif (strlen($newPassword) < 8) {
        $error = "La password deve essere lunga almeno 8 caratteri.";
    } else {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $stmt->execute([$passwordHash, $user['id']]);

            $_SESSION['success'] = "Password aggiornata con successo. Ora puoi accedere.";
            header("Location: " . BASE_PATH . "login");
            exit;
        } catch (PDOException $e) {
            error_log("Errore aggiornamento password: " . $e->getMessage());
            $error = "Errore durante l'aggiornamento della password. Riprova più tardi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title>Reset Password - Ricettario</title>
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
        <h1>Reset Password</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        if (!empty($error)) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        }
        ?>
        <form method="POST" action="<?php echo BASE_PATH; ?>reset_password?token=<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">Nuova Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Reimposta Password</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>