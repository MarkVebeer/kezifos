<?php
// Kapcsolódás az adatbázishoz
$host = 'localhost';
$dbname = 'csapatok_db';
$username = 'root';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Kapcsolódási hiba: ' . $e->getMessage();
    exit;
}

// Új torna hozzáadása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tournament_name'])) {
    $tournament_name = $_POST['tournament_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $stmt = $pdo->prepare("INSERT INTO tournaments (name, start_date, end_date) VALUES (:name, :start_date, :end_date)");
    $stmt->execute([
        'name' => $tournament_name,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);

    // Redirect a főoldalra
    header("Location: index.php");
    exit();
}

// Tornák lekérdezése
$stmt = $pdo->query("SELECT * FROM tournaments");
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tornák Kezelése</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Tornák Kezelése</h1>

        <!-- Új torna hozzáadása -->
        <form action="index.php" method="post" class="mb-4">
            <div class="mb-3">
                <label for="tournament_name" class="form-label">Új torna neve</label>
                <input type="text" class="form-control" id="tournament_name" name="tournament_name" required>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Kezdés dátuma</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">Befejezés dátuma</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <button type="submit" class="btn btn-primary">Torna hozzáadása</button>
        </form>

        <!-- Tornák listája -->
        <h2>Tornák Listája</h2>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Név</th>
                    <th>Kezdés dátuma</th>
                    <th>Befejezés dátuma</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tournaments as $tournament): ?>
                    <tr>
                        <td><?php echo $tournament['name']; ?></td>
                        <td><?php echo $tournament['start_date']; ?></td>
                        <td><?php echo $tournament['end_date']; ?></td>
                        <td>
                            <a href="tournament.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary btn-sm">Megnyitás</a>
                            <a href="?delete_tournament=<?php echo $tournament['id']; ?>" class="btn btn-danger btn-sm">Törlés</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>