<?php
require_once 'includes/header.php';

$parentId = $_GET['parent_id'] ?? null;
if (!$parentId) {
    header('Location: parents.php');
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = $search ? '&search=' . urlencode($search) : '';

// Get parent info
$parentStmt = $pdo->prepare("SELECT * FROM parents WHERE id = ?");
$parentStmt->execute([$parentId]);
$parent = $parentStmt->fetch();

// Get total count
if ($search !== '') {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM result WHERE parent_id = :parent_id AND (
        kpj LIKE :like1 OR nik LIKE :like2 OR notif_sipp LIKE :like3 OR notif_lasik LIKE :like4 OR notif_eklp LIKE :like5 OR kota LIKE :like6 OR kecamatan LIKE :like7 OR kelurahan LIKE :like8
    )");
    $like = "%$search%";
    $totalStmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
    $totalStmt->bindValue(':like1', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like2', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like3', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like4', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like5', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like6', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like7', $like, PDO::PARAM_STR);
    $totalStmt->bindValue(':like8', $like, PDO::PARAM_STR);
    $totalStmt->execute();
} else {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM result WHERE parent_id = ?");
    $totalStmt->execute([$parentId]);
}
$total = $totalStmt->fetchColumn();

// Get children
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM result WHERE parent_id = :parent_id AND (
        kpj LIKE :like1 OR nik LIKE :like2 OR notif_sipp LIKE :like3 OR notif_lasik LIKE :like4 OR notif_eklp LIKE :like5 OR kota LIKE :like6 OR kecamatan LIKE :like7 OR kelurahan LIKE :like8
    ) ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $like = "%$search%";
    $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
    $stmt->bindValue(':like1', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like3', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like4', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like5', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like6', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like7', $like, PDO::PARAM_STR);
    $stmt->bindValue(':like8', $like, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM result WHERE parent_id = :parent_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$children = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <a href="parents.php" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4 mr-1"></i> Back to Parents
            </a>
            <div class="mt-2">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Children Records</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">KPJ: <?= htmlspecialchars($parent['kpj']) ?></p>
            </div>
        </div>
        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2">
            <div class="relative flex-grow">
                <input type="text" id="searchInput" placeholder="Search children..." 
                    class="w-full pl-10 pr-4 py-2 border rounded-md bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
            </div>
            <button id="searchBtn" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Search</span>
            </button>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">KPJ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SIPP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">LASIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">EKLP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">DPT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($children as $child): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" 
                        data-notif-sipp="<?= htmlspecialchars($child['notif_sipp'] ?? '') ?>"
                        data-notif-lasik="<?= htmlspecialchars($child['notif_lasik'] ?? '') ?>"
                        data-notif-eklp="<?= htmlspecialchars($child['notif_eklp'] ?? '') ?>"
                        data-kota="<?= htmlspecialchars($child['kota'] ?? '') ?>"
                        data-kecamatan="<?= htmlspecialchars($child['kecamatan'] ?? '') ?>"
                        data-kelurahan="<?= htmlspecialchars($child['kelurahan'] ?? '') ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($child['kpj']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['nik']) ?></td>
                        
                        <!-- SIPP Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                                       ($child['sipp_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                    <?= empty($child['nik']) ? 'not found' : ucfirst($child['sipp_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- LASIK Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                                       ($child['lasik_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                    <?= empty($child['nik']) ? 'not found' : ucfirst($child['lasik_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- EKLP Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                                       ($child['eklp_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                    <?= empty($child['nik']) ? 'not found' : ucfirst($child['eklp_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- DPT Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                                       ($child['dpt_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                    <?= empty($child['nik']) ? 'not found' : ucfirst($child['dpt_status'] ?? 'pending') ?>
                                </span>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-3">
                                <?php if (empty($child['nik'])): ?>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                                <?php elseif (!in_array($child['notif_sipp'] ?? '', ['Sukses', 'Tidak bisa digunakan'])): ?>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                                <?php elseif ($child['sipp_status'] === 'not found'): ?>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                                <?php elseif ($child['sipp_status'] === 'success'): ?>
                                    <?php if ($child['lasik_status'] !== 'success'): ?>
                                        <a href="#" class="inline-flex items-center text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 transition-colors generate-lasik" data-id="<?= $child['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 mr-1"></i> LASIK
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($child['eklp_status'] !== 'success'): ?>
                                        <a href="#" class="inline-flex items-center text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 transition-colors generate-eklp" data-id="<?= $child['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 mr-1"></i> EKLP
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($child['dpt_status'] !== 'success'): ?>
                                        <a href="#" class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors generate-dpt" data-id="<?= $child['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 mr-1"></i> DPT
                                        </a>
                                    <?php endif; ?>
                                    <a href="#" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors view-details" data-id="<?= $child['id'] ?>">
                                        <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="inline-flex items-center text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors resume-child" data-id="<?= $child['id'] ?>">
                                        <i data-lucide="play" class="w-4 h-4 mr-1"></i> Resume
                                    </a>
                                    <a href="#" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors view-details" data-id="<?= $child['id'] ?>">
                                        <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards -->
        <div class="md:hidden grid grid-cols-1 gap-4 p-4">
            <?php foreach ($children as $child): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow"
                data-notif-sipp="<?= htmlspecialchars($child['notif_sipp'] ?? '') ?>"
                data-notif-lasik="<?= htmlspecialchars($child['notif_lasik'] ?? '') ?>"
                data-notif-eklp="<?= htmlspecialchars($child['notif_eklp'] ?? '') ?>"
                data-kota="<?= htmlspecialchars($child['kota'] ?? '') ?>"
                data-kecamatan="<?= htmlspecialchars($child['kecamatan'] ?? '') ?>"
                data-kelurahan="<?= htmlspecialchars($child['kelurahan'] ?? '') ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($child['kpj']) ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">NIK: <?= htmlspecialchars($child['nik']) ?></p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-gray-400 dark:text-gray-500"><?= date('Y-m-d H:i', strtotime($child['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <!-- SIPP Status -->
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">SIPP</p>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                               ($child['sipp_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                            <?= empty($child['nik']) ? 'not found' : ucfirst($child['sipp_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    
                    <!-- LASIK Status -->
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">LASIK</p>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                               ($child['lasik_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                            <?= empty($child['nik']) ? 'not found' : ucfirst($child['lasik_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    
                    <!-- EKLP Status -->
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">EKLP</p>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                               ($child['eklp_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                            <?= empty($child['nik']) ? 'not found' : ucfirst($child['eklp_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    
                    <!-- DPT Status -->
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">DPT</p>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?= empty($child['nik']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                               ($child['dpt_status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                            <?= empty($child['nik']) ? 'not found' : ucfirst($child['dpt_status'] ?? 'pending') ?>
                        </span>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                    <?php if (empty($child['nik'])): ?>
                        <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                    <?php elseif (!in_array($child['notif_sipp'] ?? '', ['Sukses', 'Tidak bisa digunakan'])): ?>
                        <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                    <?php elseif ($child['sipp_status'] === 'not found'): ?>
                        <span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>
                    <?php elseif ($child['sipp_status'] === 'success'): ?>
                        <?php if ($child['lasik_status'] !== 'success'): ?>
                            <a href="#" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 text-sm flex items-center generate-lasik" data-id="<?= $child['id'] ?>">
                                <i data-lucide="zap" class="w-4 h-4 mr-1"></i> LASIK
                            </a>
                        <?php endif; ?>
                        <?php if ($child['eklp_status'] !== 'success'): ?>
                            <a href="#" class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 text-sm flex items-center generate-eklp" data-id="<?= $child['id'] ?>">
                                <i data-lucide="zap" class="w-4 h-4 mr-1"></i> EKLP
                            </a>
                        <?php endif; ?>
                        <?php if ($child['dpt_status'] !== 'success'): ?>
                            <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm flex items-center generate-dpt" data-id="<?= $child['id'] ?>">
                                <i data-lucide="zap" class="w-4 h-4 mr-1"></i> DPT
                            </a>
                        <?php endif; ?>
                        <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm flex items-center view-details" data-id="<?= $child['id'] ?>">
                            <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
                        </a>
                    <?php else: ?>
                        <a href="#" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-sm flex items-center resume-child" data-id="<?= $child['id'] ?>">
                            <i data-lucide="play" class="w-4 h-4 mr-1"></i> Resume
                        </a>
                        <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm flex items-center view-details" data-id="<?= $child['id'] ?>">
                            <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
            <div class="flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                        <span class="font-medium"><?= min($offset + $perPage, $total) ?></span> of 
                        <span class="font-medium"><?= $total ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                        <a href="?parent_id=<?= $parentId ?>&page=<?= $page - 1 ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <span class="sr-only">Previous</span>
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $totalPages = ceil($total / $perPage);
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <a href="?parent_id=<?= $parentId ?>&page=<?= $i ?><?= $searchParam ?>" class="<?= $i === $page ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-100' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?parent_id=<?= $parentId ?>&page=<?= $page + 1 ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <span class="sr-only">Next</span>
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all duration-300 ease-in-out w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Child Record Details</h3>
            <button id="closeDetailsModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Basic Information Column -->
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Basic Information</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">KPJ</p>
                                <p id="detail-kpj" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">NIK</p>
                                <p id="detail-nik" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Created At</p>
                                <p id="detail-created" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Information Column -->
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Process Status</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">SIPP Status</p>
                                <p id="detail-sipp" class="text-sm font-medium"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">SIPP Notification</p>
                                <p id="detail-notif-sipp" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">LASIK Status</p>
                                <p id="detail-lasik" class="text-sm font-medium"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">LASIK Notification</p>
                                <p id="detail-notif-lasik" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information Column -->
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Additional Information</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">EKLP Status</p>
                                <p id="detail-eklp" class="text-sm font-medium"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">EKLP Notification</p>
                                <p id="detail-notif-eklp" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">DPT Status</p>
                                <p id="detail-dpt" class="text-sm font-medium"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Location</p>
                                <p id="detail-kota" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                                <p id="detail-kecamatan" class="text-sm font-medium text-gray-900 dark:text-white mt-1"></p>
                                <p id="detail-kelurahan" class="text-sm font-medium text-gray-900 dark:text-white mt-1"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button id="closeDetailsBtn" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', function() {
        // Details Modal
        const detailsModal = document.getElementById('detailsModal');
        const closeDetailsModal = document.getElementById('closeDetailsModal');
        const closeDetailsBtn = document.getElementById('closeDetailsBtn');
        let currentChildId = null;
        
        // Open modal with animation
        const openModal = (id) => {
            currentChildId = id;
            detailsModal.classList.remove('hidden');
            setTimeout(() => {
                detailsModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
                detailsModal.querySelector('div').classList.add('opacity-100', 'scale-100');
            }, 10);
            
            // Fetch child details
            fetchChildDetails(id);
        };
        
        // Close modal with animation
        const closeModal = () => {
            detailsModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
            detailsModal.querySelector('div').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                detailsModal.classList.add('hidden');
            }, 200);
        };
        
        // Function to fetch child details
        function fetchChildDetails(id) {
            const row = document.querySelector(`[data-id="${id}"]`).closest('tr, .bg-white');
            
            if (row) {
                setDetailField('detail-kpj', row.querySelector('td:first-child').textContent.trim());
                setDetailField('detail-nik', row.querySelector('td:nth-child(2)').textContent.trim());
                
                // Get statuses
                const statuses = row.querySelectorAll('[class*="bg-"]');
                document.getElementById('detail-sipp').innerHTML = statuses[0] ? statuses[0].outerHTML : '-';
                document.getElementById('detail-lasik').innerHTML = statuses[1] ? statuses[1].outerHTML : '-';
                document.getElementById('detail-eklp').innerHTML = statuses[2] ? statuses[2].outerHTML : '-';
                document.getElementById('detail-dpt').innerHTML = statuses[3] ? statuses[3].outerHTML : '-';
                
                // Get created date
                const createdDate = row.querySelector('.text-xs.text-gray-400')?.textContent.trim() || new Date().toLocaleString();
                setDetailField('detail-created', createdDate);
                
                // Get other details from data attributes
                setDetailField('detail-notif-sipp', row.getAttribute('data-notif-sipp'));
                setDetailField('detail-notif-lasik', row.getAttribute('data-notif-lasik'));
                setDetailField('detail-notif-eklp', row.getAttribute('data-notif-eklp'));
                setDetailField('detail-kota', row.getAttribute('data-kota'));
                setDetailField('detail-kecamatan', row.getAttribute('data-kecamatan'));
                setDetailField('detail-kelurahan', row.getAttribute('data-kelurahan'));
            }
        }
        
        // View details buttons
        document.querySelectorAll('.view-details').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openModal(this.getAttribute('data-id'));
            });
        });
        
        closeDetailsModal.addEventListener('click', closeModal);
        closeDetailsBtn.addEventListener('click', closeModal);
        
        // Resume child process
        document.querySelectorAll('.resume-child').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                resumeChildProcess(this.getAttribute('data-id'));
            });
        });

        // Generate LASIK process
        document.querySelectorAll('.generate-lasik').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                generateProcess(this.getAttribute('data-id'), 'lasik');
            });
        });

        // Generate EKLP process
        document.querySelectorAll('.generate-eklp').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                generateProcess(this.getAttribute('data-id'), 'eklp');
            });
        });

        // Generate DPT process
        document.querySelectorAll('.generate-dpt').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                generateProcess(this.getAttribute('data-id'), 'dpt');
            });
        });
        
        function resumeChildProcess(childId) {
            const button = document.querySelector(`.resume-child[data-id="${childId}"]`);
            const icon = button.querySelector('i');
            const originalIcon = icon.className;
            
            // Show loading state
            icon.className = 'w-4 h-4 animate-spin mr-1';
            if (icon) {
                icon.setAttribute('data-lucide', 'loader-2');
            }
            lucide.createIcons();
            
            fetch(`api/resume.php?type=child&id=${childId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'start' })
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message || data.msg, 'success');
                // Close modal if open
                closeModal();
                // Refresh the page after 2 seconds
                setTimeout(() => location.reload(), 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            })
            .finally(() => {
                // Restore original icon
                icon.className = originalIcon;
                if (icon) {
                    icon.setAttribute('data-lucide', 'play');
                }
                lucide.createIcons();
            });
        }

        function generateProcess(childId, processType) {
            const button = document.querySelector(`.generate-${processType}[data-id="${childId}"]`);
            const icon = button.querySelector('i');
            const originalIcon = icon.className;
            
            // Show loading state
            icon.className = 'w-4 h-4 animate-spin mr-1';
            if (icon) {
                icon.setAttribute('data-lucide', 'loader-2');
            }
            lucide.createIcons();
            
            fetch(`api/generate.php?type=child&id=${childId}&process=${processType}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'start' })
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message || data.msg, 'success');
                // Close modal if open
                closeModal();
                // Refresh the page after 2 seconds
                setTimeout(() => location.reload(), 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            })
            .finally(() => {
                // Restore original icon
                icon.className = originalIcon;
                if (icon) {
                    icon.setAttribute('data-lucide', 'zap');
                }
                lucide.createIcons();
            });
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };
            
            toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-md shadow-lg text-white ${colors[type]} flex items-center transform transition-all duration-300 translate-y-2 opacity-0`;
            toast.innerHTML = `
                <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info'}" class="w-5 h-5 mr-2"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(toast);
            lucide.createIcons();
            
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        searchInput.value = new URL(window.location.href).searchParams.get('search') || '';
        searchBtn.addEventListener('click', () => {
            const query = searchInput.value.trim();
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        });
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                const url = new URL(window.location.href);
                if (query) {
                    url.searchParams.set('search', query);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            }
        });

        // Detail modal field handling
        function setDetailField(id, value) {
            const element = document.getElementById(id);
            if (!element) return;
            
            if (value === null || value === undefined || value === '' || value === 'null') {
                element.textContent = '-';
            } else {
                element.textContent = value;
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>