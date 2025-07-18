<?php
ob_start();
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug view_recipe: Session = " . print_r($_SESSION, true));

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID ricetta mancante o non valido";
    header("Location: " . BASE_PATH . "index");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$_GET['id']]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    $_SESSION['error'] = "Ricetta non trovata";
    header("Location: " . BASE_PATH . "index");
    exit;
}

// Genera un token CSRF per l'eliminazione
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica il token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Errore di validazione CSRF";
        header("Location: " . BASE_PATH . "index");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Verifica che l'utente sia il proprietario
        if (!isset($_SESSION['user_id']) || $recipe['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Non sei autorizzato a eliminare questa ricetta";
            header("Location: " . BASE_PATH . "index");
            exit;
        }

        // Elimina la ricetta
        try {
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            error_log("Debug view_recipe: Ricetta eliminata: id={\$_GET['id']}, user_id={\$_SESSION['user_id']}");
            unset($_SESSION['csrf_token']);
            $_SESSION['success'] = "Ricetta eliminata con successo";
            header("Location: " . BASE_PATH . "index");
            exit;
        } catch (PDOException $e) {
            error_log("Errore eliminazione ricetta: " . $e->getMessage());
            $_SESSION['error'] = "Errore durante l'eliminazione della ricetta: " . (ini_get('display_errors') ? $e->getMessage() : "contatta l'amministratore.");
            header("Location: " . BASE_PATH . "view_recipe?id=" . $_GET['id']);
            exit;
        }
    }
}

// Genera la base URL dinamicamente
$base_url = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title><?php echo htmlspecialchars($recipe['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-3">
    <a href="<?php echo BASE_PATH; ?>index" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Torna alle ricette
    </a>
    <div class="container">
        <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>
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
        <p><strong>Tempo di preparazione:</strong> <?php echo htmlspecialchars($recipe['prep_time']); ?> min</p>
        <p><strong>Porzioni:</strong> <span id="servings"><?php echo htmlspecialchars($recipe['servings']); ?></span></p>
        <div class="mb-3">
            <label for="multiplier" class="form-label">Moltiplicatore porzioni:</label>
            <div class="input-group w-25" id="multiplier-container">
                <button class="btn btn-outline-secondary" type="button" id="minus-btn">-</button>
                <input type="number" id="multiplier" class="form-control text-center" value="1" min="1" step="1" oninput="adjustQuantities(<?php echo htmlspecialchars($recipe['servings']); ?>)">
                <button class="btn btn-outline-secondary" type="button" id="plus-btn">+</button>
            </div>
        </div>
        <h3>Ingredienti</h3>
        <ul id="ingredients-list">
            <?php
            $ingredients = explode("\n", $recipe['ingredients']);
            foreach ($ingredients as $ingredient) {
                $ingredient = trim($ingredient);
                if (!empty($ingredient)) {
                    echo '<li data-original="' . htmlspecialchars($ingredient) . '">' . htmlspecialchars($ingredient) . '</li>';
                }
            }
            ?>
        </ul>
        <h3>Istruzioni</h3>
        <p><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>
        <!-- Pulsante di condivisione -->
        <div class="mt-3">
            <button id="share-recipe" class="btn btn-primary">
                <i class="bi bi-share-fill"></i> Condividi Ricetta
            </button>
            <span id="share-fallback" class="d-none ms-2 text-muted"></span>
        </div>
        <?php if (isset($_SESSION['user_id']) && $recipe['user_id'] == $_SESSION['user_id']): ?>
            <div class="mt-3">
                <a href="<?php echo BASE_PATH; ?>edit_recipe?id=<?php echo $recipe['id']; ?>" class="btn btn-warning">Modifica</a>
                <form method="POST" id="delete-form" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger" data-loading-text="Eliminazione..." onclick="confirmDelete(event)">Elimina</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>