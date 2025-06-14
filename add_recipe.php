<?php
ob_start(); // Avvia il buffering dell'output per prevenire errori di redirect
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug add_recipe: Session = " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "login");
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
        header("Location: " . BASE_PATH . "add_recipe");
        exit;
    }

    $title = trim($_POST['title']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $prep_time = filter_var($_POST['prep_time'], FILTER_SANITIZE_NUMBER_INT);
    $servings = filter_var($_POST['servings'], FILTER_SANITIZE_NUMBER_INT);

    // Validazione
    if (empty($title) || empty($ingredients) || empty($instructions) || $prep_time <= 0 || $servings <= 0) {
        $error = "Tutti i campi sono obbligatori e devono essere validi. Controlla che il tempo di preparazione e le porzioni siano numeri positivi.";
    } else {
        $public_link = uniqid('recipe_', true);

        // Debug: log dei valori prima dell'inserimento
        error_log("Debug add_recipe: Tentativo inserimento: title=$title, prep_time=$prep_time, servings=$servings, public_link=$public_link, user_id={$_SESSION['user_id']}");

        $stmt = $pdo->prepare("INSERT INTO recipes (title, ingredients, instructions, prep_time, servings, public_link, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$title, $ingredients, $instructions, $prep_time, $servings, $public_link, $_SESSION['user_id']]);
            error_log("Debug add_recipe: Ricetta inserita: title=$title, user_id={$_SESSION['user_id']}");
            // Resetta il token CSRF
            unset($_SESSION['csrf_token']);
            $_SESSION['success'] = "Ricetta salvata con successo!";
            header("Location: " . BASE_PATH . "index");
            exit;
        } catch (PDOException $e) {
            error_log("Errore inserimento ricetta: " . $e->getMessage());
            $error = "Errore nel salvataggio della ricetta: " . (ini_get('display_errors') ? $e->getMessage() : "contatta l'amministratore.");
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
    <title>Aggiungi Ricetta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Aggiungi Nuova Ricetta</h1>
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
                <label for="title" class="form-label">Titolo</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ingredients" class="form-label">Ingredienti (uno per riga)</label>
                <textarea class="form-control" id="ingredients" name="ingredients" rows="5" required><?php echo isset($_POST['ingredients']) ? htmlspecialchars($_POST['ingredients']) : ''; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="instructions" class="form-label">Istruzioni</label>
                <textarea class="form-control" id="instructions" name="instructions" rows="5" required><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="prep_time" class="form-label">Tempo di preparazione (min)</label>
                <input type="number" class="form-control" id="prep_time" name="prep_time" min="1" value="<?php echo isset($_POST['prep_time']) ? htmlspecialchars($_POST['prep_time']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="servings" class="form-label">Porzioni</label>
                <input type="number" class="form-control" id="servings" name="servings" min="1" value="<?php echo isset($_POST['servings']) ? htmlspecialchars($_POST['servings']) : ''; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Salva Ricetta</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); // Svuota il buffer e invia l'output ?>