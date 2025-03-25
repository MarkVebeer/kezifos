<?php
require_once('tcpdf/tcpdf.php');
require_once('config.php');

// Egyedi PDF osztály létrehozása fejléc és lábléc kezeléséhez
class MatchProtocolPDF extends TCPDF {
    protected $headerData;

    public function setHeaderData($data) {
        $this->headerData = $data;
    }

    public function Header() {
        $this->SetFont('dejavusans', 'B', 16);
        $this->Cell(0, 10, 'KÉZILABDA VERSENYJEGYZŐKÖNYV', 0, 1, 'C');
        $this->SetFont('dejavusans', '', 10);
        
        // Fejléc adatok kiírása
        $this->Ln(5);
        $this->Cell(95, 6, 'Verseny: ' . $this->headerData['tournament_name'], 0, 0);
        $this->Cell(95, 6, 'Szervezet: ' . $this->headerData['organization'], 0, 1);
        
        $this->Cell(95, 6, 'Forduló: ' . $this->headerData['round_number'], 0, 0);
        $this->Cell(95, 6, 'Szakág: ' . $this->headerData['sport_type'], 0, 1);
        
        $this->Cell(95, 6, 'Nem: ' . $this->headerData['gender'], 0, 0);
        $this->Cell(95, 6, 'Korosztály: ' . $this->headerData['age_group'], 0, 1);
        
        $this->Cell(95, 6, 'Típus: ' . $this->headerData['competition_type'], 0, 0);
        $this->Cell(95, 6, 'Idény: ' . $this->headerData['season'], 0, 1);
        
        $this->Cell(0, 6, 'Mérkőzés kód: ' . $this->headerData['match_code'], 0, 1);
        
        $this->Ln(5);
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(95, 8, $this->headerData['home_team'], 0, 0, 'C');
        $this->Cell(0, 8, $this->headerData['away_team'], 0, 1, 'C');
        
        $this->SetFont('dejavusans', '', 10);
        $this->Ln(5);
    }
}

// Adatbázis kapcsolat létrehozása
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Kapcsolódási hiba: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = $_POST['match_id'];
    
    // Mérkőzés adatainak lekérdezése
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.name as home_team_name, 
               t2.name as away_team_name,
               r.name as round_name,
               tour.name as tournament_name
        FROM matches m
        LEFT JOIN teams t1 ON m.home_team_id = t1.id
        LEFT JOIN teams t2 ON m.away_team_id = t2.id
        LEFT JOIN rounds r ON m.round_id = r.id
        LEFT JOIN tournaments tour ON m.tournament_id = tour.id
        WHERE m.id = :match_id
    ");
    $stmt->execute(['match_id' => $match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        die('A mérkőzés nem található.');
    }

    // Jegyzőkönyv adatainak összegyűjtése
    $protocol_data = [
        'match_info' => [
            'organization' => $_POST['organization'],
            'round_number' => $_POST['round_number'],
            'sport_type' => $_POST['sport_type'],
            'gender' => $_POST['gender'],
            'age_group' => $_POST['age_group'],
            'competition_type' => $_POST['competition_type'],
            'season' => $_POST['season'],
            'match_code' => $_POST['match_code'],
            'venue' => $_POST['venue'],
            'spectators' => $_POST['spectators'],
            'referee1_name' => $_POST['referee1_name'],
            'referee1_code' => $_POST['referee1_code'],
            'referee2_name' => $_POST['referee2_name'],
            'referee2_code' => $_POST['referee2_code'],
            'match_supervisor' => $_POST['match_supervisor'],
            'match_supervisor_code' => $_POST['match_supervisor_code'],
            'home_score_half' => $_POST['home_score_half'],
            'away_score_half' => $_POST['away_score_half'],
            'home_score_final' => $_POST['home_score_final'],
            'away_score_final' => $_POST['away_score_final']
        ],
        'home_players' => $_POST['home_players'],
        'away_players' => $_POST['away_players']
    ];

    // Jegyzőkönyv mentése vagy frissítése az adatbázisban
    $stmt = $pdo->prepare("
        INSERT INTO match_protocols (match_id, protocol_data, created_at)
        VALUES (:match_id, :protocol_data, NOW())
        ON DUPLICATE KEY UPDATE protocol_data = :protocol_data, updated_at = NOW()
    ");
    $stmt->execute([
        'match_id' => $match_id,
        'protocol_data' => json_encode($protocol_data)
    ]);

    // PDF generálása
    $pdf = new MatchProtocolPDF('P', 'mm', 'A4', true, 'UTF-8');

    // PDF metaadatok beállítása
    $pdf->SetCreator('Kézilabda Jegyzőkönyv Rendszer');
    $pdf->SetAuthor('MKSZ');
    $pdf->SetTitle('Mérkőzés jegyzőkönyv - ' . $match['home_team_name'] . ' vs ' . $match['away_team_name']);

    // Fejléc adatok beállítása
    $pdf->setHeaderData([
        'tournament_name' => $match['tournament_name'],
        'organization' => $protocol_data['match_info']['organization'],
        'round_number' => $protocol_data['match_info']['round_number'],
        'sport_type' => $protocol_data['match_info']['sport_type'],
        'gender' => $protocol_data['match_info']['gender'],
        'age_group' => $protocol_data['match_info']['age_group'],
        'competition_type' => $protocol_data['match_info']['competition_type'],
        'season' => $protocol_data['match_info']['season'],
        'match_code' => $protocol_data['match_info']['match_code'],
        'home_team' => $match['home_team_name'],
        'away_team' => $match['away_team_name']
    ]);

    // PDF beállítások
    $pdf->SetMargins(15, 50, 15);
    $pdf->AddPage();

    // Mérkőzés információk
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(95, 6, 'Helyszín: ' . $protocol_data['match_info']['venue'], 0, 0);
    $pdf->Cell(95, 6, 'Nézőszám: ' . $protocol_data['match_info']['spectators'], 0, 1);

    $pdf->Cell(0, 6, 'Játékvezető-1: ' . $protocol_data['match_info']['referee1_name'] . ' (' . $protocol_data['match_info']['referee1_code'] . ')', 0, 1);
    $pdf->Cell(0, 6, 'Játékvezető-2: ' . $protocol_data['match_info']['referee2_name'] . ' (' . $protocol_data['match_info']['referee2_code'] . ')', 0, 1);
    $pdf->Cell(0, 6, 'Játékvezető ellenőr: ' . $protocol_data['match_info']['match_supervisor'] . ' (' . $protocol_data['match_info']['match_supervisor_code'] . ')', 0, 1);

    $pdf->Ln(5);
    $pdf->Cell(95, 6, 'Félidő eredmény: ' . $protocol_data['match_info']['home_score_half'] . ' - ' . $protocol_data['match_info']['away_score_half'], 0, 0);
    $pdf->Cell(95, 6, 'Végeredmény: ' . $protocol_data['match_info']['home_score_final'] . ' - ' . $protocol_data['match_info']['away_score_final'], 0, 1);

    // Játékosok táblázat fejléc
    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 8, 'Hazai csapat játékosai', 0, 1);
    $pdf->SetFont('dejavusans', '', 8);

    // Táblázat fejléc
    $header = ['Mezszám', 'Játékos neve', 'Játékengedély', '2 perc', 'Sárga', 'Piros', '7m', 'Gól'];
    $w = [15, 50, 25, 15, 15, 15, 15, 15];
    
    foreach($header as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
    }
    $pdf->Ln();

    // Hazai játékosok
    $home_totals = ['suspensions' => 0, 'yellow' => 0, 'red' => 0, 'goals_7m' => 0, 'goals' => 0];
    foreach($protocol_data['home_players'] as $player) {
        if (empty($player['number']) && empty($player['name'])) continue;
        
        $pdf->Cell($w[0], 6, $player['number'], 1);
        $pdf->Cell($w[1], 6, $player['name'], 1);
        $pdf->Cell($w[2], 6, $player['license'], 1);
        $pdf->Cell($w[3], 6, $player['suspension'], 1, 0, 'C');
        $pdf->Cell($w[4], 6, !empty($player['yellow']) ? '✓' : '', 1, 0, 'C');
        $pdf->Cell($w[5], 6, !empty($player['red']) ? '✓' : '', 1, 0, 'C');
        $pdf->Cell($w[6], 6, $player['goals_7m'], 1, 0, 'C');
        $pdf->Cell($w[7], 6, $player['goals'], 1, 0, 'C');
        $pdf->Ln();

        // Összesítés
        $home_totals['suspensions'] += intval($player['suspension']);
        $home_totals['yellow'] += !empty($player['yellow']) ? 1 : 0;
        $home_totals['red'] += !empty($player['red']) ? 1 : 0;
        $home_totals['goals_7m'] += intval($player['goals_7m']);
        $home_totals['goals'] += intval($player['goals']);
    }

    // Hazai összesítés
    $pdf->SetFont('dejavusans', 'B', 8);
    $pdf->Cell($w[0] + $w[1] + $w[2], 6, 'Összesen:', 1);
    $pdf->Cell($w[3], 6, $home_totals['suspensions'], 1, 0, 'C');
    $pdf->Cell($w[4], 6, $home_totals['yellow'], 1, 0, 'C');
    $pdf->Cell($w[5], 6, $home_totals['red'], 1, 0, 'C');
    $pdf->Cell($w[6], 6, $home_totals['goals_7m'], 1, 0, 'C');
    $pdf->Cell($w[7], 6, $home_totals['goals'], 1, 0, 'C');
    $pdf->Ln(10);

    // Vendég játékosok
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 8, 'Vendég csapat játékosai', 0, 1);
    $pdf->SetFont('dejavusans', '', 8);

    // Táblázat fejléc
    foreach($header as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
    }
    $pdf->Ln();

    // Vendég játékosok
    $away_totals = ['suspensions' => 0, 'yellow' => 0, 'red' => 0, 'goals_7m' => 0, 'goals' => 0];
    foreach($protocol_data['away_players'] as $player) {
        if (empty($player['number']) && empty($player['name'])) continue;
        
        $pdf->Cell($w[0], 6, $player['number'], 1);
        $pdf->Cell($w[1], 6, $player['name'], 1);
        $pdf->Cell($w[2], 6, $player['license'], 1);
        $pdf->Cell($w[3], 6, $player['suspension'], 1, 0, 'C');
        $pdf->Cell($w[4], 6, !empty($player['yellow']) ? '✓' : '', 1, 0, 'C');
        $pdf->Cell($w[5], 6, !empty($player['red']) ? '✓' : '', 1, 0, 'C');
        $pdf->Cell($w[6], 6, $player['goals_7m'], 1, 0, 'C');
        $pdf->Cell($w[7], 6, $player['goals'], 1, 0, 'C');
        $pdf->Ln();

        // Összesítés
        $away_totals['suspensions'] += intval($player['suspension']);
        $away_totals['yellow'] += !empty($player['yellow']) ? 1 : 0;
        $away_totals['red'] += !empty($player['red']) ? 1 : 0;
        $away_totals['goals_7m'] += intval($player['goals_7m']);
        $away_totals['goals'] += intval($player['goals']);
    }

    // Vendég összesítés
    $pdf->SetFont('dejavusans', 'B', 8);
    $pdf->Cell($w[0] + $w[1] + $w[2], 6, 'Összesen:', 1);
    $pdf->Cell($w[3], 6, $away_totals['suspensions'], 1, 0, 'C');
    $pdf->Cell($w[4], 6, $away_totals['yellow'], 1, 0, 'C');
    $pdf->Cell($w[5], 6, $away_totals['red'], 1, 0, 'C');
    $pdf->Cell($w[6], 6, $away_totals['goals_7m'], 1, 0, 'C');
    $pdf->Cell($w[7], 6, $away_totals['goals'], 1, 0, 'C');

    // PDF kiküldése
    $pdf->Output('jegyzokonyv.pdf', 'I');
    exit;
}
?> 