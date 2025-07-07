<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'ERROR', 'message' => 'Method Not Allowed']);
    exit;
}

$type = $_GET['type'] ?? null; // 'parent' or 'child'
$id = $_GET['id'] ?? null;

if (!$type || !$id) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'Missing type or id']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'start';

$apiUrl = "http://localhost:3000/resume-{$type}/{$id}";

if ($action === 'stop') {
    $apiUrl .= '/stop';
}

$postData = json_encode(['action' => $action]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpcode);
echo $response;
