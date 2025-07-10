<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $size = isset($_GET['size']) ? max(1, intval($_GET['size'])) : 5;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $tipe = isset($_GET['tipe']) ? trim($_GET['tipe']) : '';
    $offset = ($page - 1) * $size;
    
    $params = [];
    $where = [];
    
    if ($search !== '') {
        $where[] = 'email LIKE :search';
        $params[':search'] = "%$search%";
    }
    
    if ($tipe !== '') {
        $where[] = 'tipe = :tipe';
        $params[':tipe'] = $tipe;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $total = $pdo->prepare("SELECT COUNT(*) FROM akun_sipp $whereClause");
    $total->execute($params);
    $total = $total->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT * FROM akun_sipp $whereClause ORDER BY created_at DESC LIMIT :offset, :size");
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
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $tipe = trim($input['tipe'] ?? '');
        
        if ($email === '' || $password === '' || $tipe === '') {
            echo json_encode(['error' => 'Email, password, dan tipe wajib diisi']);
            exit;
        }
        
        if (!in_array($tipe, ['sipp', 'eklp'])) {
            echo json_encode(['error' => 'Tipe harus sipp atau eklp']);
            exit;
        }
        
        $stmt = $pdo->prepare('INSERT INTO akun_sipp (email, password, tipe) VALUES (:email, :password, :tipe)');
        $stmt->execute([
            ':email' => $email,
            ':password' => $password,
            ':tipe' => $tipe
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($input['action'] === 'set_selected') {
        $id = intval($input['id']);
        $tipe = trim($input['tipe'] ?? '');
        
        if ($tipe === '') {
            echo json_encode(['error' => 'Tipe wajib diisi']);
            exit;
        }
        
        // 只更新相同类型的账户
        $stmt = $pdo->prepare('UPDATE akun_sipp SET is_selected = 0 WHERE tipe = :tipe');
        $stmt->execute([':tipe' => $tipe]);
        
        $stmt = $pdo->prepare('UPDATE akun_sipp SET is_selected = 1 WHERE id = :id AND tipe = :tipe');
        $stmt->execute([':id' => $id, ':tipe' => $tipe]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($input['action'] === 'delete') {
        $id = intval($input['id']);
        
        $stmt = $pdo->prepare('DELETE FROM akun_sipp WHERE id = :id');
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']); 