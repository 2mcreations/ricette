<?php
ob_start();
require 'includes/config';
session_start();

// Debug: verifica stato sessione
error_log("Debug index: Session = " . print_r($_SESSION, true));

// Recupera le ricette
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, title, prep_time FROM recipes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->query("SELECT id, title, prep_time FROM recipes ORDER BY created_at DESC");
    }
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Debug index: Recuperate " . count($recipes) . " ricette");
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
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
    <link rel="manifest" href="<?php echo BASE_PATH; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>images/icon-192x192.png">
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</head>
<body>
    <?php include 'includes/header'; ?>
    <div class="container">
        <h1>Le Mie Ricette</h1>
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
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
                                <a href="<?php echo BASE_PATH; ?>view_recipe?id=<?php echo $recipe['id']; ?>" class="btn btn-info">Visualizza</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer'; ?>
</body>
</html>
<?php ob_end_flush(); ?>