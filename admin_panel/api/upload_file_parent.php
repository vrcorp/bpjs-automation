<?php
require_once '../../vendor/autoload.php'; // pastikan sudah安装 phpoffice/phpspreadsheet
require_once '../includes/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$kpj = trim($_POST['kpj'] ?? '');
if ($kpj === '' || !isset($_FILES['file'])) {
    echo json_encode(['error' => 'Nama tugas dan file wajib diisi']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Gagal upload file']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'xlsx') {
    echo json_encode(['error' => 'File harus .xlsx']);
    exit;
}

// 保存文件到临时目录
$tmpPath = sys_get_temp_dir() . '/' . uniqid('parent_', true) . '.xlsx';
if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
    echo json_encode(['error' => 'Gagal menyimpan file']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($tmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    if (count($rows) < 2) {
        echo json_encode(['error' => 'File kosong atau tidak ada data']);
        unlink($tmpPath);
        exit;
    }
    // 插入 parents
    $stmt = $pdo->prepare('INSERT INTO parents (kpj, is_file, file_path, status) VALUES (:kpj, 1, :file_path, "pending")');
    $stmt->execute([
        ':kpj' => $kpj,
        ':file_path' => $file['name']
    ]);
    $parent_id = $pdo->lastInsertId();
    // 插入 result
    $insertResult = $pdo->prepare('INSERT INTO result (parent_id, kpj) VALUES (:parent_id, :kpj)');
    for ($i = 1; $i < count($rows); $i++) { // 跳过表头
        $rowKpj = trim($rows[$i][0] ?? '');
        if ($rowKpj !== '') {
            $insertResult->execute([
                ':parent_id' => $parent_id,
                ':kpj' => $rowKpj
            ]);
        }
    }
    unlink($tmpPath);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (file_exists($tmpPath)) unlink($tmpPath);
    echo json_encode(['error' => 'Gagal membaca file: ' . $e->getMessage()]);
} 