<?php
header('Content-Type: application/json');

include '../config.php';

$host = _MYSQL_HOST;
$dbname = _MYSQL_DB;
$username = _MYSQL_USER;
$password = _MYSQL_PWD;
$port = _MYSQL_PORT;
$slack_token = _SLACK_BOT_TOKEN;
$slack_channel_id = _SLACK_CHANNEL_ID;

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
    global $slack_token, $slack_channel_id;

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
    global $slack_token, $slack_channel_id;

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
        postFileToSlack($context, $file['tmp_name'], $file_name, $mime_type, $file_type, $slack_token, $slack_channel_id);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern der Datei.']);
    }
}

function postTextToSlack($context, $text_content) {
    global $slack_token, $slack_channel_id;
    $message = [
        'channel' => $slack_channel_id,
        'text' => "Neues Text-Feedback erhalten aus dem Kontext: $context\n*Feedback:* $text_content",
    ];

    $ch = curl_init('https://slack.com/api/chat.postMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $slack_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    $result = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($result, true);
    if (!$response_data['ok']) {
        error_log('Slack API Fehler beim Senden der Nachricht: ' . ($response_data['error'] ?? 'Unbekannter Fehler'));
    } else {
        error_log('Text erfolgreich zu Slack gesendet');
    }
}

function uploadFileToSlack($token, $channelId, $filePath, $filename, $file_type, $context) {
    $filesize = filesize($filePath);
    $title = $filename;
    $initialComment = "Neues $file_type-Feedback erhalten aus dem Kontext: $context";

    try {
        $uploadUrlResponse = getUploadUrl($token, $filename, $filesize);
        if (!isset($uploadUrlResponse['upload_url']) || !isset($uploadUrlResponse['file_id'])) {
            throw new Exception("Failed to get upload URL or file ID: " . json_encode($uploadUrlResponse));
        }

        $uploadUrl = $uploadUrlResponse['upload_url'];
        $fileId = $uploadUrlResponse['file_id'];

        $uploadResponse = uploadFileToSlackUrl($uploadUrl, $filePath);
        if (!$uploadResponse || !isset($uploadResponse['ok']) || $uploadResponse['ok'] !== true) {
            throw new Exception("File upload failed: " . json_encode($uploadResponse));
        }

        $completeResponse = completeUpload($token, $fileId, $title, $channelId, $initialComment);
        if (!isset($completeResponse['ok']) || $completeResponse['ok'] !== true) {
            throw new Exception("Failed to complete upload: " . json_encode($completeResponse));
        }

        echo json_encode(['message' => 'Datei erfolgreich zu Slack hochgeladen.']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getUploadUrl($token, $filename, $length) {
    $url = 'https://slack.com/api/files.getUploadURLExternal';
    $data = [
        'filename' => $filename,
        'length' => $length
    ];
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL Error in getUploadUrl: ' . curl_error($ch));
    }
    curl_close($ch);
    $decodedResult = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error in getUploadUrl: " . json_last_error_msg());
    }
    return $decodedResult;
}

function uploadFileToSlackUrl($uploadUrl, $filePath) {
    $file = new CURLFile(realpath($filePath), mime_content_type($filePath), basename($filePath));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $file]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('cURL Error in uploadFileToSlack: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (strpos($result, 'OK') === 0) {
        return ['ok' => true, 'message' => $result];
    } else {
        throw new Exception("Unexpected response from Slack: " . $result);
    }
}

function completeUpload($token, $fileId, $title, $channelId = null, $initialComment = null) {
    $url = 'https://slack.com/api/files.completeUploadExternal';
    $data = [
        'files' => json_encode([['id' => $fileId, 'title' => $title]])
    ];
    if ($channelId) {
        $data['channel_id'] = $channelId;
    }
    if ($initialComment) {
        $data['initial_comment'] = $initialComment;
    }
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL Error in completeUpload: ' . curl_error($ch));
    }
    curl_close($ch);
    $decodedResult = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error in completeUpload: " . json_last_error_msg());
    }
    return $decodedResult;
}