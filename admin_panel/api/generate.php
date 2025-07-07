<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'ERROR', 'message' => 'Method Not Allowed']);
    exit;
}

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'start'; // 'start' or 'stop'

// API endpoint
$apiUrl = 'http://localhost:3000/generate';

// Prepare data for the Node.js API
$postData = json_encode(['action' => $action]);

// Use cURL to send the request
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

// Respond to the client
http_response_code($httpcode);
echo $response;
