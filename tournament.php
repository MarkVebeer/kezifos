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

// Torna ID ellenőrzése
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$tournament_id = $_GET['id'];

// Torna adatainak lekérdezése
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = :id");
$stmt->execute(['id' => $tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tournament) {
    header("Location: index.php");
    exit();
}

// Csapat hozzáadása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_name'])) {
    $team_name = $_POST['team_name'];
    
    $stmt = $pdo->prepare("INSERT INTO teams (name, tournament_id) VALUES (:name, :tournament_id)");
    $stmt->execute(['name' => $team_name, 'tournament_id' => $tournament_id]);

    // Redirect a torna oldalra
    header("Location: tournament.php?id=$tournament_id");
    exit();
}

// Új forduló hozzáadása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_round'])) {
    $round_name = $_POST['new_round'];
    
    $stmt = $pdo->prepare("INSERT INTO rounds (name, tournament_id) VALUES (:name, :tournament_id)");
    $stmt->execute(['name' => $round_name, 'tournament_id' => $tournament_id]);

    // Redirect a torna oldalra
    header("Location: tournament.php?id=$tournament_id");
    exit();
}

// Új mérkőzés hozzáadása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_match'])) {
    $round_id = $_POST['round_id'];
    $home_team_id = $_POST['home_team_id'];
    $away_team_id = $_POST['away_team_id'];
    $match_date = $_POST['match_date'];
    
    $stmt = $pdo->prepare("INSERT INTO matches (round_id, home_team_id, away_team_id, match_date, tournament_id) VALUES (:round_id, :home_team_id, :away_team_id, :match_date, :tournament_id)");
    $stmt->execute([
        'round_id' => $round_id,
        'home_team_id' => $home_team_id,
        'away_team_id' => $away_team_id,
        'match_date' => $match_date,
        'tournament_id' => $tournament_id
    ]);

    // Redirect a torna oldalra
    header("Location: tournament.php?id=$tournament_id");
    exit();
}

// Mérkőzés törlése
if (isset($_GET['delete_match'])) {
    $match_id = $_GET['delete_match'];
    
    // Először töröljük a jegyzőkönyvet
    $stmt = $pdo->prepare("DELETE FROM match_protocols WHERE match_id = :id");
    $stmt->execute(['id' => $match_id]);
    
    // Majd töröljük a mérkőzést
    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = :id AND tournament_id = :tournament_id");
    $stmt->execute(['id' => $match_id, 'tournament_id' => $tournament_id]);
    
    header("Location: tournament.php?id=$tournament_id");
    exit();
}

// Forduló és hozzá tartozó meccsek törlése
if (isset($_GET['delete_round'])) {
    $round_id = $_GET['delete_round'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM matches WHERE round_id = :round_id AND tournament_id = :tournament_id");
        $stmt->execute(['round_id' => $round_id, 'tournament_id' => $tournament_id]);
        
        $stmt = $pdo->prepare("DELETE FROM rounds WHERE id = :id AND tournament_id = :tournament_id");
        $stmt->execute(['id' => $round_id, 'tournament_id' => $tournament_id]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Hiba történt: " . $e->getMessage();
    }
    header("Location: tournament.php?id=$tournament_id");
    exit();
}

// Mérkőzés frissítése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_match'])) {
    try {
        $match_id = $_POST['match_id'];
        $home_team_id = $_POST['home_team_id'];
        $away_team_id = $_POST['away_team_id'];
        $match_date = $_POST['match_date'];
        $score_home = $_POST['score_home'] !== '' ? $_POST['score_home'] : null;
        $score_away = $_POST['score_away'] !== '' ? $_POST['score_away'] : null;
        $referees = $_POST['referees'];
        $tournament_id = $_POST['tournament_id'];

        // Ellenőrizzük, hogy a csapatok ugyanahhoz a tornához tartoznak-e
        $stmt = $pdo->prepare("SELECT tournament_id FROM teams WHERE id IN (:home_team_id, :away_team_id)");
        $stmt->execute(['home_team_id' => $home_team_id, 'away_team_id' => $away_team_id]);
        $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count(array_unique($teams)) > 1) {
            throw new Exception("A csapatok különböző tornákhoz tartoznak!");
        }

        // Frissítjük a mérkőzést
        $stmt = $pdo->prepare("UPDATE matches SET 
            home_team_id = :home_team_id,
            away_team_id = :away_team_id,
            match_date = :match_date,
            score_home = :score_home,
            score_away = :score_away,
            referees = :referees
            WHERE id = :id AND tournament_id = :tournament_id");
            
        $stmt->execute([
            'home_team_id' => $home_team_id,
            'away_team_id' => $away_team_id,
            'match_date' => $match_date,
            'score_home' => $score_home,
            'score_away' => $score_away,
            'referees' => $referees,
            'id' => $match_id,
            'tournament_id' => $tournament_id
        ]);

        // Frissítjük a jegyzőkönyvet is, ha létezik
        $stmt = $pdo->prepare("UPDATE match_protocols SET 
            home_team_score = :score_home,
            away_team_score = :score_away
            WHERE match_id = :match_id");
            
        $stmt->execute([
            'score_home' => $score_home,
            'score_away' => $score_away,
            'match_id' => $match_id
        ]);

        header("Location: tournament.php?id=$tournament_id");
        exit();
    } catch (Exception $e) {
        // Hiba esetén visszaadjuk a hibaüzenetet
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Csapatok lekérdezése
$stmt_teams = $pdo->prepare("SELECT * FROM teams WHERE tournament_id = :tournament_id");
$stmt_teams->execute(['tournament_id' => $tournament_id]);
$teams = $stmt_teams->fetchAll(PDO::FETCH_ASSOC);

// Fordulók lekérdezése
$stmt_rounds = $pdo->prepare("SELECT * FROM rounds WHERE tournament_id = :tournament_id");
$stmt_rounds->execute(['tournament_id' => $tournament_id]);
$rounds = $stmt_rounds->fetchAll(PDO::FETCH_ASSOC);

// Mérkőzések lekérdezése
$stmt_matches = $pdo->prepare("SELECT m.id AS match_id, m.round_id, m.home_team_id, m.away_team_id, m.match_date, m.score_home, m.score_away, m.referees, 
                                      t1.name AS home_team, t2.name AS away_team, r.name AS round_name
                               FROM matches m
                               LEFT JOIN teams t1 ON m.home_team_id = t1.id
                               LEFT JOIN teams t2 ON m.away_team_id = t2.id
                               LEFT JOIN rounds r ON m.round_id = r.id
                               WHERE m.tournament_id = :tournament_id
                               ORDER BY m.match_date");
$stmt_matches->execute(['tournament_id' => $tournament_id]);
$matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tournament['name']; ?> Kezelése</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .editable {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container mt-5">

        <!-- Vissza a tornákhoz gomb -->
        <a href="index.php" class="btn btn-secondary mb-4">Vissza a tornákhoz</a>
        
        <h1><?php echo $tournament['name']; ?> Kezelése</h1>

        <!-- Csapat hozzáadása -->
        <form action="tournament.php?id=<?php echo $tournament_id; ?>" method="post" class="mb-4">
            <div class="mb-3">
                <label for="team_name" class="form-label">Új csapat neve</label>
                <input type="text" class="form-control" id="team_name" name="team_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Csapat hozzáadása</button>
        </form>

        <!-- Új forduló hozzáadása -->
        <form action="tournament.php?id=<?php echo $tournament_id; ?>" method="post" class="mb-4">
            <div class="mb-3">
                <label for="new_round" class="form-label">Új forduló neve</label>
                <input type="text" class="form-control" id="new_round" name="new_round" required>
            </div>
            <button type="submit" class="btn btn-primary" name="create_round">Új forduló hozzáadása</button>
        </form>

        <!-- Új mérkőzés hozzáadása -->
        <form action="tournament.php?id=<?php echo $tournament_id; ?>" method="post" class="mb-4">
            <div class="mb-3">
                <label for="round_id" class="form-label">Válassz fordulót</label>
                <select class="form-select" id="round_id" name="round_id" required>
                    <?php foreach ($rounds as $round): ?>
                        <option value="<?php echo $round['id']; ?>"><?php echo $round['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="home_team_id" class="form-label">Hazai csapat</label>
                <select class="form-select" id="home_team_id" name="home_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="away_team_id" class="form-label">Vendég csapat</label>
                <select class="form-select" id="away_team_id" name="away_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="match_date" class="form-label">Mérkőzés időpontja</label>
                <input type="datetime-local" class="form-control" id="match_date" name="match_date" required>
            </div>
            <button type="submit" class="btn btn-primary" name="new_match">Új mérkőzés hozzáadása</button>
        </form>
        
        <!-- Meccsek és Fordulók táblázata -->
        <h2>Mérkőzések Listája</h2>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Forduló</th>
                    <th>#</th>
                    <th>Hazai csapat</th>
                    <th>Vendég csapat</th>
                    <th>Időpont</th>
                    <th>Eredmény</th>
                    <th>Játékvezetők</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_round = null;
                $match_number = 1;

                foreach ($matches as $match):
                    if ($current_round !== $match['round_id']):
                        $current_round = $match['round_id'];
                        ?>
                        <tr class="table-primary fw-bold">
                            <td colspan="8">
                                <?php echo $match['round_name']; ?>
                                <a href="?delete_round=<?php echo $match['round_id']; ?>&id=<?php echo $tournament_id; ?>" class="btn btn-danger btn-sm ms-3">Forduló törlése</a>
                            </td>
                        </tr>
                        <?php
                        $match_number = 1;
                    endif;
                    ?>
                    <tr data-match-id="<?php echo $match['match_id']; ?>">
                        <td></td>
                        <td><?php echo $match_number++; ?></td>
                        <td class="editable" data-field="home_team_id">
                            <select class="form-select form-select-sm" name="home_team_id">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>" <?php echo $team['id'] == $match['home_team_id'] ? 'selected' : ''; ?>><?php echo $team['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="editable" data-field="away_team_id">
                            <select class="form-select form-select-sm" name="away_team_id">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>" <?php echo $team['id'] == $match['away_team_id'] ? 'selected' : ''; ?>><?php echo $team['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="editable" data-field="match_date">
                            <input type="datetime-local" class="form-control form-control-sm" name="match_date" value="<?php echo date('Y-m-d\TH:i', strtotime($match['match_date'])); ?>">
                        </td>
                        <td class="editable" data-field="score">
                            <input type="number" class="form-control form-control-sm" name="score_home" value="<?php echo $match['score_home']; ?>" style="width: 60px;">
                            <input type="number" class="form-control form-control-sm" name="score_away" value="<?php echo $match['score_away']; ?>" style="width: 60px;">
                        </td>
                        <td class="editable" data-field="referees">
                            <input type="text" class="form-control form-control-sm" name="referees" value="<?php echo $match['referees']; ?>">
                        </td>
                        <td>
                            <a href="?delete_match=<?php echo $match['match_id']; ?>&id=<?php echo $tournament_id; ?>" class="btn btn-danger btn-sm">Törlés</a>
                            <button type="button" class="btn btn-success btn-sm save-btn">Mentés</button>
                            <a href="match_protocol.php?match_id=<?php echo $match['match_id']; ?>" class="btn btn-primary btn-sm">Jegyzőkönyv</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.save-btn').forEach(button => {
            button.addEventListener('click', () => {
                const row = button.closest('tr');
                const matchId = row.getAttribute('data-match-id');
                const data = {
                    match_id: matchId,
                    home_team_id: row.querySelector('select[name="home_team_id"]').value,
                    away_team_id: row.querySelector('select[name="away_team_id"]').value,
                    match_date: row.querySelector('input[name="match_date"]').value,
                    score_home: row.querySelector('input[name="score_home"]').value,
                    score_away: row.querySelector('input[name="score_away"]').value,
                    referees: row.querySelector('input[name="referees"]').value,
                    tournament_id: <?php echo $tournament_id; ?>,
                    update_match: true
                };

                fetch('tournament.php?id=<?php echo $tournament_id; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response;
                })
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Hiba:', error);
                    alert(error.error || 'Hiba történt a mentés során.');
                });
            });
        });
    </script>
</body>
</html>