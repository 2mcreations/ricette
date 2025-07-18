<?php
ob_start();
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug logout: Session = " . print_r($_SESSION, true));

// Genera un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica il token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Errore di validazione CSRF";
        error_log("Debug logout: Errore CSRF");
        header("Location: " . BASE_PATH . "index");
        exit;
    }

    // Termina la sessione
    session_unset();
    session_destroy();
    error_log("Debug logout: Logout effettuato");
    unset($_SESSION['csrf_token']);
    $_SESSION['success'] = "Logout effettuato con successo!";
    session_write_close();
    header("Location: " . BASE_PATH . "index");
    exit;
}

// Se l'utente non è loggato, reindirizza a index
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "index");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title>Logout - Ricettario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h1>Logout</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <p>Sei sicuro di voler uscire?</p>
        <form method="POST" action="<?php echo BASE_PATH; ?>logout">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" class="btn btn-primary" data-loading-text="Elimino Sessione..." data-original-text="Esci">Esci</button>
            <a href="<?php echo BASE_PATH; ?>index" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>