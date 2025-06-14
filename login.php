<?php
ob_start(); // Avvia il buffering dell'output per prevenire errori di redirect
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug login: Session = " . print_r($_SESSION, true));

// Se l'utente è già loggato, reindirizza a index
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "index");
    exit;
}

// Genera un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica il token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Errore di validazione CSRF";
        header("Location: " . BASE_PATH . "login");
        exit;
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validazione
    if (empty($username) || empty($password)) {
        $error = "Username e password sono obbligatori.";
        error_log("Debug login: Input mancanti: username=$username");
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login riuscito
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                error_log("Debug login: Login riuscito: user_id={$user['id']}, username={$user['username']}");
                unset($_SESSION['csrf_token']);
                session_write_close();
                $_SESSION['success'] = "Login effettuato con successo!";
                header("Location: " . BASE_PATH . "index");
                exit;
            } else {
                $error = "Username o password non validi.";
                error_log("Debug login: Tentativo di login fallito: username=$username");
            }
        } catch (PDOException $e) {
            error_log("Errore login: " . $e->getMessage());
            $error = "Errore durante il login: " . (ini_get('display_errors') ? $e->getMessage() : "contatta l'amministratore.");
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Login</h1>
        <?php
        if (isset($error)) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Accedi</button>
        </form>
        <p class="mt-3">Non hai un account? <a href="<?php echo BASE_PATH; ?>register">Registrati</a></p>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); // Svuota il buffer e invia l'output ?>