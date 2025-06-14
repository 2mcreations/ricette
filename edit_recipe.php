<?php
ob_start();
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug edit_recipe: Session = " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "login");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID ricetta mancante o non valido";
    header("Location: " . BASE_PATH . "index");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$recipe) {
        $_SESSION['error'] = "Ricetta non trovata o non autorizzato";
        header("Location: " . BASE_PATH . "index");
        exit;
    }
} catch (PDOException $e) {
    error_log("Errore recupero ricetta: " . $e->getMessage());
    $_SESSION['error'] = "Errore caricamento ricetta";
    header("Location: " . BASE_PATH . "index");
    exit;
}

// Genera un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: verifica dati POST
    error_log("Debug edit_recipe: POST ricevuto, dati = " . print_r($_POST, true));

    // Verifica il token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Errore di validazione CSRF";
        error_log("Debug edit_recipe: Errore CSRF");
        header("Location: " . BASE_PATH . "index");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Elimina la ricetta
        try {
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            error_log("Debug edit_recipe: Ricetta eliminata: id={$_GET['id']}, user_id={$_SESSION['user_id']}");
            unset($_SESSION['csrf_token']);
            $_SESSION['success'] = "Ricetta eliminata con successo";
            header("Location: " . BASE_PATH . "index");
            exit;
        } catch (PDOException $e) {
            error_log("Errore eliminazione ricetta: " . $e->getMessage());
            $error = "Errore durante l'eliminazione della ricetta.";
        }
    } else {
        // Modifica la ricetta
        $title = trim($_POST['title']);
        $ingredients = trim($_POST['ingredients']);
        $instructions = trim($_POST['instructions']);
        $prep_time = filter_var($_POST['prep_time'], FILTER_SANITIZE_NUMBER_INT);
        $servings = filter_var($_POST['servings'], FILTER_SANITIZE_NUMBER_INT);

        // Validazione
        if (empty($title) || empty($ingredients) || empty($instructions) || $prep_time <= 0 || $servings <= 0) {
            $error = "Tutti i campi sono obbligatori e devono essere validi";
            error_log("Debug edit_recipe: Validazione fallita");
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE recipes SET title = ?, ingredients = ?, instructions = ?, prep_time = ?, servings = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $ingredients, $instructions, $prep_time, $servings, $_GET['id'], $_SESSION['user_id']]);
                error_log("Debug edit_recipe: Ricetta modificata: id={$_GET['id']}, title=$title, user_id={$_SESSION['user_id']}");
                unset($_SESSION['csrf_token']);
                $_SESSION['success'] = "Ricetta modificata con successo";
                header("Location: " . BASE_PATH . "view_recipe?id=" . $_GET['id']);
                exit;
            } catch (PDOException $e) {
                error_log("Errore modifica ricetta: " . $e->getMessage());
                $error = "Errore nel salvataggio delle modifiche.";
            }
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
    <title>Modifica Ricetta - Ricettario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h1>Modifica Ricetta</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <form method="POST" id="edit-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Titolo</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($recipe['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="ingredients" class="form-label">Ingredienti (uno per riga)</label>
                <textarea class="form-control" id="ingredients" name="ingredients" rows="5" required><?php echo isset($_POST['ingredients']) ? htmlspecialchars($_POST['ingredients']) : htmlspecialchars($recipe['ingredients']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="instructions" class="form-label">Istruzioni</label>
                <textarea class="form-control" id="instructions" name="instructions" rows="5" required><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : htmlspecialchars($recipe['instructions']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="prep_time" class="form-label">Tempo di preparazione (min)</label>
                <input type="number" class="form-control" id="prep_time" name="prep_time" min="1" value="<?php echo isset($_POST['prep_time']) ? htmlspecialchars($_POST['prep_time']) : htmlspecialchars($recipe['prep_time']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="servings" class="form-label">Porzioni</label>
                <input type="number" class="form-control" id="servings" name="servings" min="1" value="<?php echo isset($_POST['servings']) ? htmlspecialchars($_POST['servings']) : htmlspecialchars($recipe['servings']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" data-original-text="Salva Modifiche">Salva Modifiche</button>
        </form>
        <form method="POST" id="delete-form" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger" data-original-text="Elimina Ricetta" onclick="confirmDelete(event)">Elimina Ricetta</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
        window.basePath = '<?php echo BASE_PATH; ?>';
    </script>
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>