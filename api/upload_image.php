<?php
header('Content-Type: application/json'); // Setzt den Content-Type der Antwort

$host = 'localhost'; // z.B. localhost
$dbname = 'abetterfeedback';
$username = 'root';
$password = 'root';

// Verbindung zur Datenbank herstellen
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Sendet einen HTTP-Statuscode 500 zurück
    http_response_code(500);
    echo json_encode(['error' => "Datenbankverbindung fehlgeschlagen: " . $e->getMessage()]);
    exit; // Beendet die Ausführung des Skripts
}

// Prüfen, ob eine Datei hochgeladen wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES['photo']['tmp_name'])) {
    $image = file_get_contents($_FILES['photo']['tmp_name']);

    $sql = "INSERT INTO abf_image_tbl (image) VALUES (:image)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':image', $image, PDO::PARAM_LOB);

    if ($stmt->execute()) {
        // Erfolgreiche Antwort
        echo json_encode(['message' => 'Bild erfolgreich gespeichert.']);
    } else {
        // Sendet einen HTTP-Statuscode 500 zurück
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern des Bildes.']);
    }
} else {
    // Sendet einen HTTP-Statuscode 400 zurück
    http_response_code(400);
    echo json_encode(['error' => 'Keine Datei zum Hochladen erhalten.']);
}

