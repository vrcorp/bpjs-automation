<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM running_jobs WHERE status IN ('pending','process') ORDER BY created_at DESC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

echo json_encode(['error'=>'Invalid request']); 