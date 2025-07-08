<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $size = isset($_GET['size']) ? max(1, intval($_GET['size'])) : 5;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $offset = ($page - 1) * $size;
    $params = [];
    $where = '';
    if ($search !== '') {
        $where = 'WHERE induk LIKE :search';
        $params[':search'] = "%$search%";
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM induk $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM induk $where ORDER BY created_at DESC LIMIT :offset, :size");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':size', $size, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data, 'total' => (int)$total, 'page' => $page, 'size' => $size]);
    exit;
}
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input['action'] === 'add') {
        $induk = trim($input['induk'] ?? '');
        if ($induk === '') {
            echo json_encode(['error' => 'Nama induk wajib diisi']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO induk (induk) VALUES (:induk)');
        $stmt->execute([':induk' => $induk]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($input['action'] === 'set_selected') {
        $id = intval($input['id']);
        $pdo->exec('UPDATE induk SET is_selected = 0');
        $stmt = $pdo->prepare('UPDATE induk SET is_selected = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }
}
echo json_encode(['error' => 'Invalid request']); 