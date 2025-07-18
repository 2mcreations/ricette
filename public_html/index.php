<?php
ob_start();
require 'includes/config.php';
session_start();

// Debug: verifica stato sessione
error_log("Debug index: Session = " . print_r($_SESSION, true));

try {
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (!empty($searchTerm)) {
        $stmt = $pdo->prepare("SELECT r.id, r.title, r.prep_time, r.user_id FROM recipes r WHERE r.title LIKE ? ORDER BY r.created_at DESC");
        $stmt->execute(["%" . $searchTerm . "%"]);
    } else {
        $stmt = $pdo->prepare("SELECT r.id, r.title, r.prep_time, r.user_id FROM recipes r ORDER BY r.created_at DESC");
        $stmt->execute();
    }
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Debug index: Recuperate " . count($recipes) . " ricette: " . json_encode(array_column($recipes, 'id')));
} catch (PDOException $e) {
    error_log("Errore recupero ricette: " . $e->getMessage());
    $_SESSION['error'] = "Errore nel caricamento delle ricette: " . (ini_get('display_errors') ? $e->getMessage() : "contatta l'amministratore.");
    $recipes = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#007bff">
    <title>Ricettario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Le Mie Ricette</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cerca ricette per titolo..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Cerca</button>
                    </div>
                </form>
                <a href="<?php echo BASE_PATH; ?>add_recipe" class="btn btn-primary mb-3">Aggiungi Ricetta</a>
                <?php else: ?>
                    <p>Effettua il <a href="<?php echo BASE_PATH; ?>login">login</a> per aggiungere ricette.</p>
                    <?php endif; ?>
                    <div class="row">
                        <?php if (empty($recipes)): ?>
                            <p>Nessuna ricetta disponibile.</p>
                            <?php else: ?>
                                <?php foreach ($recipes as $recipe): ?>
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                        <p class="card-text">Tempo: <?php echo htmlspecialchars($recipe['prep_time']); ?> min</p>
                        <a href="<?php echo BASE_PATH; ?>view_recipe?id=<?php echo htmlspecialchars($recipe['id']); ?>" class="btn btn-info">Visualizza</a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id']): ?>
                        <a href="<?php echo BASE_PATH; ?>edit_recipe?id=<?php echo htmlspecialchars($recipe['id']); ?>" class="btn btn-warning">Modifica</a>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>