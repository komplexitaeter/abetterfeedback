<?php
header('Content-Type: application/json');

include '../config.php';

$host = _MYSQL_HOST;
$dbname = _MYSQL_DB;
$username = _MYSQL_USER;
$password = _MYSQL_PWD;
$port = _MYSQL_PORT;
$slack_webhook_url = _SLACK_WEBHOOK;

$context = substr(filter_input(INPUT_GET, "context", FILTER_SANITIZE_STRING), 0, 200);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Datenbankverbindung fehlgeschlagen: " . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['text_content'])) {
        handleTextFeedback($pdo, $context, $_POST['text_content']);
    } elseif (!empty($_FILES['photo']['tmp_name']) || !empty($_FILES['audio']['tmp_name'])) {
        handleFileFeedback($pdo, $context);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Keine Datei oder Text zum Hochladen erhalten.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt.']);
}

function handleTextFeedback($pdo, $context, $text_content) {
    $sql = "INSERT INTO abf_feedback_tbl (context, text_content, mime_type) VALUES (:context, :text_content, 'text/plain')";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':context', $context, PDO::PARAM_STR);
    $stmt->bindParam(':text_content', $text_content, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Text erfolgreich gespeichert.']);
        postTextToSlack($context, $text_content);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern des Textes.']);
    }
}

function handleFileFeedback($pdo, $context) {
    if (!empty($_FILES['photo']['tmp_name'])) {
        $file = $_FILES['photo'];
        $file_type = 'photo';
    } else {
        $file = $_FILES['audio'];
        $file_type = 'audio';
    }

    $file_name = $file['name'];
    $mime_type = $file['type'];
    $file_content = file_get_contents($file['tmp_name']);

    $sql = "INSERT INTO abf_feedback_tbl (context, binary_content, file_name, mime_type) VALUES (:context, :file, :file_name, :mime_type)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':context', $context, PDO::PARAM_STR);
    $stmt->bindParam(':file', $file_content, PDO::PARAM_LOB);
    $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
    $stmt->bindParam(':mime_type', $mime_type, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Datei erfolgreich gespeichert.']);
        postFileNotificationToSlack($context, $file_name, $file_type);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern der Datei.']);
    }
}

function postTextToSlack($context, $text_content) {
    global $slack_webhook_url;
    $message = [
        'text' => "Neues Text-Feedback erhalten aus dem Kontext: $context\n*Feedback:* $text_content",
    ];

    $ch = curl_init($slack_webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_exec($ch);
    curl_close($ch);
}

function postFileNotificationToSlack($context, $file_name, $file_type) {
    global $slack_webhook_url;
    $message = [
        'text' => "Neues $file_type-Feedback erhalten aus dem Kontext: $context\n*Datei:* $file_name",
    ];

    $ch = curl_init($slack_webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_exec($ch);
    curl_close($ch);
}