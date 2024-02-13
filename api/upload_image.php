<?php
header('Content-Type: application/json'); // Setzt den Content-Type der Antwort

include '../config.php';

$host = _MYSQL_HOST; // z.B. localhost
$dbname = _MYSQL_DB;
$username = _MYSQL_USER;
$password = _MYSQL_PWD;
$port = _MYSQL_PORT;

$mime_type = substr( filter_input(INPUT_GET, "mime_type", FILTER_SANITIZE_STRING	) ,0,100);
$mime_type = substr( filter_input(INPUT_GET, "file_name", FILTER_SANITIZE_STRING	) ,0,200);


// Verbindung zur Datenbank herstellen
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
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

    $sql = "INSERT INTO abf_feedback_tbl (image_conent, file_name, mime_type) VALUES (:image, :file_name, :mime_type)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':image', $image, PDO::PARAM_LOB);
    $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
    $stmt->bindParam(':file_name', $mime_type, PDO::PARAM_STR);

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

