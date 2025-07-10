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
                <p class="text-sm text-gray-500 dark:text-gray-400">KPJ: <?= htmlspecialchars($parent['kpj'] ?? '') ?></p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">TTL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">HP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">LASIK</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">EKLP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kota</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kecamatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelurahan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($children as $child): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-id="<?= $child['id'] ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($child['kpj'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['nik'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['nama'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['ttl'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['email'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['hp'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['notif_lasik'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['notif_eklp'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['kota'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['kecamatan'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($child['kelurahan'] ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button class="copy-child-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200" data-id="<?= $child['id'] ?>">
                                <i data-lucide="copy" class="w-4 h-4 inline mr-1"></i> Copy
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards -->
        <div class="md:hidden grid grid-cols-1 gap-4 p-4">
            <?php foreach ($children as $child): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow" data-id="<?= $child['id'] ?>">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">KPJ: <?= htmlspecialchars($child['kpj'] ?? '') ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">NIK: <?= htmlspecialchars($child['nik'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Nama: <?= htmlspecialchars($child['nama'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">TTL: <?= htmlspecialchars($child['ttl'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">HP: <?= htmlspecialchars($child['hp'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Email: <?= htmlspecialchars($child['email'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">LASIK: <?= htmlspecialchars($child['notif_lasik'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">EKLP: <?= htmlspecialchars($child['notif_eklp'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kota: <?= htmlspecialchars($child['kota'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kecamatan: <?= htmlspecialchars($child['kecamatan'] ?? '') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelurahan: <?= htmlspecialchars($child['kelurahan'] ?? '') ?></p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button class="copy-child-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm flex items-center" data-id="<?= $child['id'] ?>">
                        <i data-lucide="copy" class="w-4 h-4 mr-1"></i> Copy
                    </button>
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

        // 移除所有 action 相关事件，添加 copy 事件
        function getChildCopyText(row) {
            const kpj = row.querySelector('td:nth-child(1)')?.textContent.trim() || '-';
            const nik = row.querySelector('td:nth-child(2)')?.textContent.trim() || '-';
            const nama = row.querySelector('td:nth-child(3)')?.textContent.trim() || '-';
            const ttl = row.querySelector('td:nth-child(9)')?.textContent.trim() || '-';
            const email = row.querySelector('td:nth-child(10)')?.textContent.trim() || '-';
            const hp = row.querySelector('td:nth-child(11)')?.textContent.trim() || '-';
            const lasik = row.querySelector('td:nth-child(4)')?.textContent.trim() || '-';
            const eklp = row.querySelector('td:nth-child(5)')?.textContent.trim() || '-';
            const kota = row.querySelector('td:nth-child(6)')?.textContent.trim() || '-';
            const kecamatan = row.querySelector('td:nth-child(7)')?.textContent.trim() || '-';
            const kelurahan = row.querySelector('td:nth-child(8)')?.textContent.trim() || '-';
            return `KPJ: ${kpj}\nNIK: ${nik}\nNama: ${nama}\nTTL: ${ttl}\nEmail: ${email}\nHP: ${hp}\nLASIK: ${lasik}\nEKLP: ${eklp}\nKota: ${kota}\nKecamatan: ${kecamatan}\nKelurahan: ${kelurahan}`;
        }
        function copyTextToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            } else {
                // fallback for HTTP 或老浏览器
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    return Promise.resolve();
                } catch (err) {
                    document.body.removeChild(textarea);
                    return Promise.reject(err);
                }
            }
        }
        document.querySelectorAll('.copy-child-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                let row = this.closest('tr');
                if (!row) row = this.closest('.bg-white');
                const text = getChildCopyText(row);
                copyTextToClipboard(text).then(() => {
                    alert('Berhasil disalin!');
                }).catch(() => {
                    alert('Gagal menyalin!');
                });
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>