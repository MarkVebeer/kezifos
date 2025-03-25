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

// Törlés logika
if (isset($_GET['match_id'])) {
    $match_id = $_GET['match_id'];

    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = :id");
    $stmt->execute(['id' => $match_id]);

    // Redirect a főoldalra
    header("Location: index.php");
    exit();
}

?>
