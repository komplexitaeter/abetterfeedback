<?php
header('Content-Type: application/json');

include '../config.php';

$host = _MYSQL_HOST;
$dbname = _MYSQL_DB;
$username = _MYSQL_USER;
$password = _MYSQL_PWD;
$port = _MYSQL_PORT;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Datenbankverbindung fehlgeschlagen: " . $e->getMessage()]);
    exit;
}

// Prüfen, ob Textdaten gesendet wurden
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['text_content'])) {
    $text_content = $_POST['text_content'];

    $sql = "INSERT INTO abf_feedback_tbl (text_content, mime_type) VALUES (:text_content, 'text/plain')";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':text_content', $text_content, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Text erfolgreich gespeichert.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern des Textes.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Keinen Text zum Hochladen erhalten.']);
}