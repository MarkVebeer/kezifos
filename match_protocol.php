<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// TCPDF könyvtár ellenőrzése
if (!file_exists('tcpdf/tcpdf.php')) {
    die('A TCPDF könyvtár nincs telepítve. Kérlek, telepítsd a TCPDF-et a projekt gyökérkönyvtárába.');
}

require_once('tcpdf/tcpdf.php');
require_once('config.php');

// TCPDF konstansok definiálása
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}
if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}
if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 15);
}
if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}
if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 15);
}
if (!defined('PDF_MARGIN_HEADER')) {
    define('PDF_MARGIN_HEADER', 5);
}
if (!defined('PDF_MARGIN_FOOTER')) {
    define('PDF_MARGIN_FOOTER', 10);
}
if (!defined('PDF_CREATOR')) {
    define('PDF_CREATOR', 'TCPDF');
}

// PDF osztály létrehozása
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'JEGYZŐKÖNYV', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Oldal '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// PDF generálása
function generateProtocolPDF($match_id, $pdo) {
    try {
        // Mérkőzés adatainak lekérdezése
        $stmt = $pdo->prepare("
            SELECT m.*, t1.name as home_team_name, t2.name as away_team_name,
                   r.name as round_name, p.*
            FROM matches m
            LEFT JOIN teams t1 ON m.home_team_id = t1.id
            LEFT JOIN teams t2 ON m.away_team_id = t2.id
            LEFT JOIN rounds r ON m.round_id = r.id
            LEFT JOIN match_protocols p ON m.id = p.match_id
            WHERE m.id = :match_id
        ");
        $stmt->execute(['match_id' => $match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new Exception('A mérkőzés nem található.');
        }

        // PDF dokumentum létrehozása
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Dokumentum tulajdonságok beállítása
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Rendszer');
        $pdf->SetTitle('Jegyzőkönyv - ' . $match['home_team_name'] . ' vs ' . $match['away_team_name']);

        // Fejléc és lábléc beállítása
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

        // Margók beállítása
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Automatikus oldaltörés
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Font beállítása
        $pdf->SetFont('helvetica', '', 12);

        // Új oldal hozzáadása
        $pdf->AddPage();

        // Tartalom hozzáadása
        $html = '
        <table cellpadding="5">
            <tr>
                <td><strong>Forduló:</strong></td>
                <td>' . $match['round_name'] . '</td>
            </tr>
            <tr>
                <td><strong>Hazai csapat:</strong></td>
                <td>' . $match['home_team_name'] . '</td>
            </tr>
            <tr>
                <td><strong>Vendég csapat:</strong></td>
                <td>' . $match['away_team_name'] . '</td>
            </tr>
            <tr>
                <td><strong>Időpont:</strong></td>
                <td>' . date('Y.m.d H:i', strtotime($match['match_date'])) . '</td>
            </tr>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Eredmény:</strong></td>
                <td>' . ($match['home_team_score'] ?? '-') . ' - ' . ($match['away_team_score'] ?? '-') . '</td>
            </tr>
            <tr>
                <td><strong>Hazai sárga lapok:</strong></td>
                <td>' . ($match['home_team_yellow_cards'] ?? '0') . '</td>
            </tr>
            <tr>
                <td><strong>Vendég sárga lapok:</strong></td>
                <td>' . ($match['away_team_yellow_cards'] ?? '0') . '</td>
            </tr>
            <tr>
                <td><strong>Hazai piros lapok:</strong></td>
                <td>' . ($match['home_team_red_cards'] ?? '0') . '</td>
            </tr>
            <tr>
                <td><strong>Vendég piros lapok:</strong></td>
                <td>' . ($match['away_team_red_cards'] ?? '0') . '</td>
            </tr>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Hazai gólszerzők:</strong></td>
                <td>' . nl2br($match['home_team_goalscorers'] ?? '') . '</td>
            </tr>
            <tr>
                <td><strong>Vendég gólszerzők:</strong></td>
                <td>' . nl2br($match['away_team_goalscorers'] ?? '') . '</td>
            </tr>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Hazai csere:</strong></td>
                <td>' . nl2br($match['home_team_substitutions'] ?? '') . '</td>
            </tr>
            <tr>
                <td><strong>Vendég csere:</strong></td>
                <td>' . nl2br($match['away_team_substitutions'] ?? '') . '</td>
            </tr>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Hazai játékosok:</strong></td>
                <td>' . nl2br($match['home_team_players'] ?? '') . '</td>
            </tr>
            <tr>
                <td><strong>Vendég játékosok:</strong></td>
                <td>' . nl2br($match['away_team_players'] ?? '') . '</td>
            </tr>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Játékvezetői jegyzetek:</strong></td>
                <td>' . nl2br($match['referee_notes'] ?? '') . '</td>
            </tr>
        </table>';

        // HTML tartalom hozzáadása
        $pdf->writeHTML($html, true, false, true, false, '');

        // PDF mentése
        $pdf->Output('jegyzokonyv_' . $match_id . '.pdf', 'D');
    } catch (Exception $e) {
        die('Hiba történt a PDF generálása során: ' . $e->getMessage());
    }
}

// Adatbázis kapcsolat létrehozása
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Kapcsolódási hiba: ' . $e->getMessage());
}

// Jegyzőkönyv mentése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_protocol'])) {
    try {
        $match_id = $_POST['match_id'];
        $tournament_id = $_POST['tournament_id'];
        $home_team_score = $_POST['home_team_score'] !== '' ? $_POST['home_team_score'] : null;
        $away_team_score = $_POST['away_team_score'] !== '' ? $_POST['away_team_score'] : null;
        $home_team_yellow_cards = $_POST['home_team_yellow_cards'] !== '' ? $_POST['home_team_yellow_cards'] : 0;
        $away_team_yellow_cards = $_POST['away_team_yellow_cards'] !== '' ? $_POST['away_team_yellow_cards'] : 0;
        $home_team_red_cards = $_POST['home_team_red_cards'] !== '' ? $_POST['home_team_red_cards'] : 0;
        $away_team_red_cards = $_POST['away_team_red_cards'] !== '' ? $_POST['away_team_red_cards'] : 0;
        $home_team_goalscorers = $_POST['home_team_goalscorers'];
        $away_team_goalscorers = $_POST['away_team_goalscorers'];
        $home_team_substitutions = $_POST['home_team_substitutions'];
        $away_team_substitutions = $_POST['away_team_substitutions'];
        $home_team_players = $_POST['home_team_players'];
        $away_team_players = $_POST['away_team_players'];
        $referee_notes = $_POST['referee_notes'];

        // Ellenőrizzük, hogy létezik-e már jegyzőkönyv
        $stmt = $pdo->prepare("SELECT id FROM match_protocols WHERE match_id = :match_id");
        $stmt->execute(['match_id' => $match_id]);
        $existing_protocol = $stmt->fetch();

        if ($existing_protocol) {
            // Frissítjük a meglévő jegyzőkönyvet
            $stmt = $pdo->prepare("UPDATE match_protocols SET 
                home_team_score = :home_team_score,
                away_team_score = :away_team_score,
                home_team_yellow_cards = :home_team_yellow_cards,
                away_team_yellow_cards = :away_team_yellow_cards,
                home_team_red_cards = :home_team_red_cards,
                away_team_red_cards = :away_team_red_cards,
                home_team_goalscorers = :home_team_goalscorers,
                away_team_goalscorers = :away_team_goalscorers,
                home_team_substitutions = :home_team_substitutions,
                away_team_substitutions = :away_team_substitutions,
                home_team_players = :home_team_players,
                away_team_players = :away_team_players,
                referee_notes = :referee_notes
                WHERE match_id = :match_id");
        } else {
            // Új jegyzőkönyvet hozunk létre
            $stmt = $pdo->prepare("INSERT INTO match_protocols 
                (match_id, tournament_id, home_team_score, away_team_score, 
                home_team_yellow_cards, away_team_yellow_cards, 
                home_team_red_cards, away_team_red_cards,
                home_team_goalscorers, away_team_goalscorers,
                home_team_substitutions, away_team_substitutions,
                home_team_players, away_team_players,
                referee_notes) 
                VALUES 
                (:match_id, :tournament_id, :home_team_score, :away_team_score,
                :home_team_yellow_cards, :away_team_yellow_cards,
                :home_team_red_cards, :away_team_red_cards,
                :home_team_goalscorers, :away_team_goalscorers,
                :home_team_substitutions, :away_team_substitutions,
                :home_team_players, :away_team_players,
                :referee_notes)");
        }

        $stmt->execute([
            'match_id' => $match_id,
            'tournament_id' => $tournament_id,
            'home_team_score' => $home_team_score,
            'away_team_score' => $away_team_score,
            'home_team_yellow_cards' => $home_team_yellow_cards,
            'away_team_yellow_cards' => $away_team_yellow_cards,
            'home_team_red_cards' => $home_team_red_cards,
            'away_team_red_cards' => $away_team_red_cards,
            'home_team_goalscorers' => $home_team_goalscorers,
            'away_team_goalscorers' => $away_team_goalscorers,
            'home_team_substitutions' => $home_team_substitutions,
            'away_team_substitutions' => $away_team_substitutions,
            'home_team_players' => $home_team_players,
            'away_team_players' => $away_team_players,
            'referee_notes' => $referee_notes
        ]);

        // PDF generálása
        generateProtocolPDF($match_id, $pdo);
    } catch (Exception $e) {
        die('Hiba történt: ' . $e->getMessage());
    }
}

// Ha GET kérés érkezik, akkor a jegyzőkönyv űrlapot jelenítjük meg
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['match_id'])) {
    $match_id = $_GET['match_id'];
    
    // Mérkőzés adatainak lekérdezése
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.name as home_team_name, 
               t2.name as away_team_name,
               r.name as round_name,
               tour.name as tournament_name,
               tour.organization,
               tour.sport_type,
               tour.gender,
               tour.age_group,
               tour.competition_type,
               tour.season,
               p.*
        FROM matches m
        LEFT JOIN teams t1 ON m.home_team_id = t1.id
        LEFT JOIN teams t2 ON m.away_team_id = t2.id
        LEFT JOIN rounds r ON m.round_id = r.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        LEFT JOIN match_protocols p ON m.id = p.match_id
        WHERE m.id = :match_id
    ");
    $stmt->execute(['match_id' => $match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        die('A mérkőzés nem található.');
    }

    // Jegyzőkönyv adatainak betöltése, ha létezik
    $protocol_data = [];
    if (!empty($match['protocol_data'])) {
        $protocol_data = json_decode($match['protocol_data'], true);
    }
    ?>
    <!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Jegyzőkönyv - <?php echo htmlspecialchars($match['home_team_name']); ?> vs <?php echo htmlspecialchars($match['away_team_name']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .protocol-header {
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
            }
            .team-name {
                font-size: 1.5em;
                font-weight: bold;
            }
            .match-info {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }
            .match-info-item {
                padding: 5px;
                border: 1px solid #eee;
            }
            .player-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }
            .player-table th, .player-table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .player-table th {
                background-color: #f8f9fa;
            }
            .form-control-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .checkbox-center {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
            }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <form method="post" action="match_protocol.php">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                <input type="hidden" name="tournament_id" value="<?php echo $match['tournament_id']; ?>">

                <div class="protocol-header">
                    <div class="team-name text-center mb-4">
                        <?php echo htmlspecialchars($match['home_team_name']); ?> vs <?php echo htmlspecialchars($match['away_team_name']); ?>
                    </div>

                    <div class="match-info mt-3">
                        <div class="match-info-item">
                            <strong>Verseny neve:</strong><br>
                            <?php echo htmlspecialchars($match['tournament_name']); ?>
                        </div>
                        <div class="match-info-item">
                            <strong>Szervezet:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="organization" value="<?php echo htmlspecialchars($match['organization'] ?? 'MKSZ'); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Forduló:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="round_name" value="<?php echo htmlspecialchars($match['round_name'] ?? ''); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Szakág:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="sport_type" value="<?php echo htmlspecialchars($match['sport_type'] ?? 'Teremkézilabda'); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Nem:</strong><br>
                            <select class="form-control form-control-sm" name="gender">
                                <option value="férfi" <?php echo ($match['gender'] ?? '') === 'férfi' ? 'selected' : ''; ?>>férfi</option>
                                <option value="női" <?php echo ($match['gender'] ?? '') === 'női' ? 'selected' : ''; ?>>női</option>
                            </select>
                        </div>
                        <div class="match-info-item">
                            <strong>Korosztály:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="age_group" value="<?php echo htmlspecialchars($match['age_group'] ?? 'Felnőtt'); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Típus:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="competition_type" value="<?php echo htmlspecialchars($match['competition_type'] ?? 'Bajnokság'); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Idény:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="season" value="<?php echo htmlspecialchars($match['season'] ?? date('Y') . '/' . (date('Y') + 1)); ?>">
                        </div>
                        <div class="match-info-item">
                            <strong>Mérkőzés kód:</strong><br>
                            <input type="text" class="form-control form-control-sm" name="match_code" value="<?php echo htmlspecialchars($match['match_code'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong>Helyszín:</strong>
                                <input type="text" class="form-control form-control-sm" name="venue" value="<?php echo htmlspecialchars($match['venue'] ?? ''); ?>">
                            </div>
                            <div class="mb-2">
                                <strong>Nézőszám:</strong>
                                <input type="number" class="form-control form-control-sm" name="spectators" value="<?php echo htmlspecialchars($match['spectators'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong>Játékvezető-1:</strong>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="referee1_name" placeholder="Név" value="<?php echo htmlspecialchars($match['referee1_name'] ?? ''); ?>">
                                    <input type="text" class="form-control form-control-sm" name="referee1_code" placeholder="Kód" value="<?php echo htmlspecialchars($match['referee1_code'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-2">
                                <strong>Játékvezető-2:</strong>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="referee2_name" placeholder="Név" value="<?php echo htmlspecialchars($match['referee2_name'] ?? ''); ?>">
                                    <input type="text" class="form-control form-control-sm" name="referee2_code" placeholder="Kód" value="<?php echo htmlspecialchars($match['referee2_code'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-2">
                                <strong>Mérkőzés ellenőr:</strong>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="match_supervisor" placeholder="Név" value="<?php echo htmlspecialchars($match['match_supervisor'] ?? ''); ?>">
                                    <input type="text" class="form-control form-control-sm" name="match_supervisor_code" placeholder="Kód" value="<?php echo htmlspecialchars($match['match_supervisor_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">Félidő eredmény</span>
                            <input type="number" class="form-control" name="home_score_half" value="<?php echo htmlspecialchars($match['home_score_half'] ?? ''); ?>" placeholder="Hazai">
                            <input type="number" class="form-control" name="away_score_half" value="<?php echo htmlspecialchars($match['away_score_half'] ?? ''); ?>" placeholder="Vendég">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">Végeredmény</span>
                            <input type="number" class="form-control" name="home_score_final" value="<?php echo htmlspecialchars($match['home_score_final'] ?? ''); ?>" placeholder="Hazai">
                            <input type="number" class="form-control" name="away_score_final" value="<?php echo htmlspecialchars($match['away_score_final'] ?? ''); ?>" placeholder="Vendég">
                        </div>
                    </div>
                </div>

                <!-- Hazai csapat játékosai -->
                <h4>Hazai csapat játékosai</h4>
                <div class="table-responsive">
                    <table class="player-table">
                        <thead>
                            <tr>
                                <th>Mezszám</th>
                                <th>Játékos neve</th>
                                <th>Játékengedély száma</th>
                                <th>2 perc</th>
                                <th>Sárga lap</th>
                                <th>Piros lap</th>
                                <th>7m</th>
                                <th>Gól</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 16; $i++): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <input type="number" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][number]" min="1" max="99" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['number'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][name]" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['name'] ?? ''); ?>">
                                </td>
                                <td style="width: 120px;">
                                    <input type="text" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][license]" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['license'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][suspensions]" min="0" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['suspensions'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <div class="checkbox-center">
                                        <input type="checkbox" class="form-check-input" name="home_players[<?php echo $i; ?>][yellow]" <?php echo isset($protocol_data['home_players'][$i]['yellow']) && $protocol_data['home_players'][$i]['yellow'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td style="width: 60px;">
                                    <div class="checkbox-center">
                                        <input type="checkbox" class="form-check-input" name="home_players[<?php echo $i; ?>][red]" <?php echo isset($protocol_data['home_players'][$i]['red']) && $protocol_data['home_players'][$i]['red'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][goals_7m]" min="0" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['goals_7m'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="home_players[<?php echo $i; ?>][goals]" min="0" value="<?php echo htmlspecialchars($protocol_data['home_players'][$i]['goals'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Összesen:</strong></td>
                                <td id="home_suspensions_total">0</td>
                                <td id="home_yellow_total">0</td>
                                <td id="home_red_total">0</td>
                                <td id="home_7m_goals_total">0</td>
                                <td id="home_goals_total">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Vendég csapat játékosai -->
                <h4 class="mt-4">Vendég csapat játékosai</h4>
                <div class="table-responsive">
                    <table class="player-table">
                        <thead>
                            <tr>
                                <th>Mezszám</th>
                                <th>Játékos neve</th>
                                <th>Játékengedély száma</th>
                                <th>2 perc</th>
                                <th>Sárga lap</th>
                                <th>Piros lap</th>
                                <th>7m</th>
                                <th>Gól</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 16; $i++): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <input type="number" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][number]" min="1" max="99" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['number'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][name]" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['name'] ?? ''); ?>">
                                </td>
                                <td style="width: 120px;">
                                    <input type="text" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][license]" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['license'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][suspensions]" min="0" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['suspensions'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <div class="checkbox-center">
                                        <input type="checkbox" class="form-check-input" name="away_players[<?php echo $i; ?>][yellow]" <?php echo isset($protocol_data['away_players'][$i]['yellow']) && $protocol_data['away_players'][$i]['yellow'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td style="width: 60px;">
                                    <div class="checkbox-center">
                                        <input type="checkbox" class="form-check-input" name="away_players[<?php echo $i; ?>][red]" <?php echo isset($protocol_data['away_players'][$i]['red']) && $protocol_data['away_players'][$i]['red'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][goals_7m]" min="0" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['goals_7m'] ?? ''); ?>">
                                </td>
                                <td style="width: 60px;">
                                    <input type="number" class="form-control form-control-sm" name="away_players[<?php echo $i; ?>][goals]" min="0" value="<?php echo htmlspecialchars($protocol_data['away_players'][$i]['goals'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Összesen:</strong></td>
                                <td id="away_suspensions_total">0</td>
                                <td id="away_yellow_total">0</td>
                                <td id="away_red_total">0</td>
                                <td id="away_7m_goals_total">0</td>
                                <td id="away_goals_total">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" name="save_protocol" class="btn btn-primary">Jegyzőkönyv mentése</button>
                        <a href="tournament.php?id=<?php echo $match['tournament_id']; ?>" class="btn btn-secondary">Vissza a tornához</a>
                    </div>
                </div>
            </form>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Összesítések frissítése
            function updateTotals() {
                // Hazai csapat
                let homeSuspensionsTotal = 0;
                let homeYellowTotal = 0;
                let homeRedTotal = 0;
                let home7mGoalsTotal = 0;
                let homeGoalsTotal = 0;

                document.querySelectorAll('input[name^="home_players"]').forEach(input => {
                    if (input.name.includes('[suspensions]')) {
                        homeSuspensionsTotal += parseInt(input.value || 0);
                    } else if (input.name.includes('[yellow]')) {
                        if (input.checked) homeYellowTotal++;
                    } else if (input.name.includes('[red]')) {
                        if (input.checked) homeRedTotal++;
                    } else if (input.name.includes('[goals_7m]')) {
                        home7mGoalsTotal += parseInt(input.value || 0);
                    } else if (input.name.includes('[goals]')) {
                        homeGoalsTotal += parseInt(input.value || 0);
                    }
                });

                document.getElementById('home_suspensions_total').textContent = homeSuspensionsTotal;
                document.getElementById('home_yellow_total').textContent = homeYellowTotal;
                document.getElementById('home_red_total').textContent = homeRedTotal;
                document.getElementById('home_7m_goals_total').textContent = home7mGoalsTotal;
                document.getElementById('home_goals_total').textContent = homeGoalsTotal;

                // Vendég csapat
                let awaySuspensionsTotal = 0;
                let awayYellowTotal = 0;
                let awayRedTotal = 0;
                let away7mGoalsTotal = 0;
                let awayGoalsTotal = 0;

                document.querySelectorAll('input[name^="away_players"]').forEach(input => {
                    if (input.name.includes('[suspensions]')) {
                        awaySuspensionsTotal += parseInt(input.value || 0);
                    } else if (input.name.includes('[yellow]')) {
                        if (input.checked) awayYellowTotal++;
                    } else if (input.name.includes('[red]')) {
                        if (input.checked) awayRedTotal++;
                    } else if (input.name.includes('[goals_7m]')) {
                        away7mGoalsTotal += parseInt(input.value || 0);
                    } else if (input.name.includes('[goals]')) {
                        awayGoalsTotal += parseInt(input.value || 0);
                    }
                });

                document.getElementById('away_suspensions_total').textContent = awaySuspensionsTotal;
                document.getElementById('away_yellow_total').textContent = awayYellowTotal;
                document.getElementById('away_red_total').textContent = awayRedTotal;
                document.getElementById('away_7m_goals_total').textContent = away7mGoalsTotal;
                document.getElementById('away_goals_total').textContent = awayGoalsTotal;

                // Végeredmény frissítése
                document.querySelector('input[name="home_score_final"]').value = homeGoalsTotal + home7mGoalsTotal;
                document.querySelector('input[name="away_score_final"]').value = awayGoalsTotal + away7mGoalsTotal;
            }

            // Eseményfigyelők hozzáadása
            document.querySelectorAll('input[type="number"], input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', updateTotals);
            });

            // Kezdeti összesítés
            updateTotals();
        </script>
    </body>
    </html>
    <?php
}
?>