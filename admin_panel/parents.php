<?php
require_once 'includes/header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$statusFilter = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$whereClause = '';
$params = [];
if ($statusFilter !== 'all' && $search !== '') {
    $whereClause = 'WHERE status = :status AND (kpj LIKE :search)';
    $params[':status'] = $statusFilter;
    $params[':search'] = "%$search%";
} elseif ($statusFilter !== 'all') {
    $whereClause = 'WHERE status = :status';
    $params[':status'] = $statusFilter;
} elseif ($search !== '') {
    $whereClause = 'WHERE kpj LIKE :search';
    $params[':search'] = "%$search%";
}

// Get total count
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM parents $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

// Get parents with child count and status counts
$query = "SELECT p.*, 
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id) as child_count,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND (lasik_status IS NULL OR lasik_status != 'success')) as pending_lasik,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND (eklp_status IS NULL OR eklp_status != 'success')) as pending_eklp,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND (dpt_status IS NULL OR dpt_status != 'success')) as pending_dpt,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND lasik_status = 'success') as success_lasik,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND eklp_status = 'success') as success_eklp,
    (SELECT COUNT(*) FROM result WHERE parent_id = p.id AND dpt_status = 'success') as success_dpt
    FROM parents p $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

// Bind parameters
if ($statusFilter !== 'all' && $search !== '') {
    $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} elseif ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
} elseif ($search !== '') {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$parents = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Parents</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage and monitor parent records</p>
        </div>
        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2">
            <div class="relative flex-grow">
                <select id="statusFilter" class="w-full pl-10 pr-4 py-2 border rounded-md bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="success" <?= $statusFilter === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="error" <?= $statusFilter === 'error' ? 'selected' : '' ?>>Error</option>
                    <option value="not found" <?= $statusFilter === 'not found' ? 'selected' : '' ?>>Not Found</option>
                </select>
            </div>
            <div class="relative flex-grow">
                <input type="text" id="searchInput" placeholder="Search..." 
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
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6 border border-gray-200 dark:border-gray-700">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">KPJ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Children</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($parents as $parent): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?= htmlspecialchars($parent['kpj']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                   ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 
                                   ($parent['status'] === 'not found' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100')) ?>">
                                <?= ucfirst($parent['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col">
                                <span><?= $parent['child_count'] ?> total</span>
                                <?php if ($parent['status'] === 'success'): ?>
                                    <div class="flex gap-2 text-xs mt-1">
                                        <span class="text-green-600 dark:text-green-400"><?= $parent['success_lasik'] ?> LASIK</span>
                                        <span class="text-green-600 dark:text-green-400"><?= $parent['success_eklp'] ?> EKLP</span>
                                        <span class="text-green-600 dark:text-green-400"><?= $parent['success_dpt'] ?> DPT</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <?php if ($parent['status'] !== 'not found'): ?>
                                <a href="children.php?parent_id=<?= $parent['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200">
                                    <i data-lucide="list" class="w-4 h-4 inline mr-1"></i> View
                                </a>
                                
                                <?php if ($parent['status'] !== 'success'): ?>
                                    <a href="#" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors duration-200 toggle-parent" data-id="<?= $parent['id'] ?>">
                                        <i data-lucide="play" class="w-4 h-4 inline mr-1"></i> Resume SIPP
                                    </a>
                                <?php else: ?>
                                    <?php if ($parent['pending_lasik'] > 0): ?>
                                        <a href="#" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 transition-colors duration-200 generate-lasik" data-id="<?= $parent['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 inline mr-1"></i> LASIK
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($parent['pending_eklp'] > 0): ?>
                                        <a href="#" class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300 transition-colors duration-200 generate-eklp" data-id="<?= $parent['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 inline mr-1"></i> EKLP
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($parent['pending_dpt'] > 0): ?>
                                        <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors duration-200 generate-dpt" data-id="<?= $parent['id'] ?>">
                                            <i data-lucide="zap" class="w-4 h-4 inline mr-1"></i> DPT
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors duration-200 export-parent" data-id="<?= $parent['id'] ?>">
                                    <i data-lucide="download" class="w-4 h-4 inline mr-1"></i> Export
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">No actions available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="md:hidden grid grid-cols-1 gap-4 p-4">
            <?php foreach ($parents as $parent): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($parent['kpj']) ?></h3>
                        <div class="flex items-center mt-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                   ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 
                                   ($parent['status'] === 'not found' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100')) ?>">
                                <?= ucfirst($parent['status']) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400"><?= $parent['child_count'] ?> children</span>
                        </div>
                        <?php if ($parent['status'] === 'success' && ($parent['pending_lasik'] > 0 || $parent['pending_eklp'] > 0 || $parent['pending_dpt'] > 0)): ?>
                            <div class="flex gap-2 text-xs mt-2">
                                <?php if ($parent['pending_lasik'] > 0): ?>
                                    <span class="text-yellow-600 dark:text-yellow-400"><?= $parent['pending_lasik'] ?> LASIK pending</span>
                                <?php endif; ?>
                                <?php if ($parent['pending_eklp'] > 0): ?>
                                    <span class="text-yellow-600 dark:text-yellow-400"><?= $parent['pending_eklp'] ?> EKLP pending</span>
                                <?php endif; ?>
                                <?php if ($parent['pending_dpt'] > 0): ?>
                                    <span class="text-yellow-600 dark:text-yellow-400"><?= $parent['pending_dpt'] ?> DPT pending</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 dark:text-gray-500"><?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></p>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
                    <?php if ($parent['status'] !== 'not found'): ?>
                        <a href="children.php?parent_id=<?= $parent['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm flex items-center">
                            <i data-lucide="list" class="w-4 h-4 mr-1"></i> View
                        </a>
                        
                        <?php if ($parent['status'] !== 'success'): ?>
                            <a href="#" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 text-sm flex items-center toggle-parent" data-id="<?= $parent['id'] ?>">
                                <i data-lucide="play" class="w-4 h-4 mr-1"></i> Resume SIPP
                            </a>
                        <?php else: ?>
                            <?php if ($parent['pending_lasik'] > 0): ?>
                                <a href="#" class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300 text-sm flex items-center generate-lasik" data-id="<?= $parent['id'] ?>">
                                    <i data-lucide="zap" class="w-4 h-4 mr-1"></i> LASIK
                                </a>
                            <?php endif; ?>
                            <?php if ($parent['pending_eklp'] > 0): ?>
                                <a href="#" class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300 text-sm flex items-center generate-eklp" data-id="<?= $parent['id'] ?>">
                                    <i data-lucide="zap" class="w-4 h-4 mr-1"></i> EKLP
                                </a>
                            <?php endif; ?>
                            <?php if ($parent['pending_dpt'] > 0): ?>
                                <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm flex items-center generate-dpt" data-id="<?= $parent['id'] ?>">
                                    <i data-lucide="zap" class="w-4 h-4 mr-1"></i> DPT
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 text-sm flex items-center export-parent" data-id="<?= $parent['id'] ?>">
                            <i data-lucide="download" class="w-4 h-4 mr-1"></i> Export
                        </a>
                    <?php else: ?>
                        <span class="text-gray-400 dark:text-gray-500 text-sm">No actions available</span>
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
                        <a href="?page=<?= $page - 1 ?><?= $statusParam ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                            <span class="sr-only">Previous</span>
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $totalPages = ceil($total / $perPage);
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        $statusParam = $statusFilter !== 'all' ? '&status=' . urlencode($statusFilter) : '';
                        $searchParam = $search !== '' ? '&search=' . urlencode($search) : '';
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <a href="?page=<?= $i ?><?= $statusParam ?><?= $searchParam ?>" class="<?= $i === $page ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-100' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors duration-200">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $statusParam ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
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

<!-- Parent Export Modal -->
<div id="parentExportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md shadow-xl transform transition-all duration-300 ease-in-out">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Export Parent Data</h3>
            <button id="closeParentExportModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <input type="hidden" id="exportParentId">
        
        <div class="space-y-4 mb-6">
            <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <input type="radio" id="parentExportOption1" name="parentExportOption" value="sipp_lasik_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700" checked>
                <label for="parentExportOption1" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → LASIK → DPT</label>
            </div>
            <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <input type="radio" id="parentExportOption2" name="parentExportOption" value="sipp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <label for="parentExportOption2" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → DPT</label>
            </div>
            <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                <input type="radio" id="parentExportOption3" name="parentExportOption" value="sipp_eklp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <label for="parentExportOption3" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → EKLP → DPT</label>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <button id="cancelParentExport" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                Cancel
            </button>
            <button id="confirmParentExport" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">
                Export
            </button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('status', status);
        window.location.href = url.toString();
    });
    
    document.addEventListener('DOMContentLoaded', function() {
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

        // Parent export modal
        const parentExportModal = document.getElementById('parentExportModal');
        const closeParentExportModal = document.getElementById('closeParentExportModal');
        const cancelParentExport = document.getElementById('cancelParentExport');
        const confirmParentExport = document.getElementById('confirmParentExport');
        const exportParentId = document.getElementById('exportParentId');
        
        // Open modal with animation
        const openModal = (id) => {
            exportParentId.value = id;
            parentExportModal.classList.remove('hidden');
            setTimeout(() => {
                parentExportModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
                parentExportModal.querySelector('div').classList.add('opacity-100', 'scale-100');
            }, 10);
        };
        
        // Close modal with animation
        const closeModal = () => {
            parentExportModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
            parentExportModal.querySelector('div').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                parentExportModal.classList.add('hidden');
            }, 200);
        };
        
        // Open modal when export link is clicked
        document.querySelectorAll('.export-parent').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openModal(this.getAttribute('data-id'));
            });
        });
        
        closeParentExportModal.addEventListener('click', closeModal);
        cancelParentExport.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        parentExportModal.addEventListener('click', (e) => {
            if (e.target === parentExportModal) {
                closeModal();
            }
        });
        
        confirmParentExport.addEventListener('click', () => {
            const parentId = exportParentId.value;
            const selectedOption = document.querySelector('input[name="parentExportOption"]:checked').value;
            
            // Show loading state
            const originalText = confirmParentExport.innerHTML;
            confirmParentExport.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Exporting...';
            confirmParentExport.disabled = true;
            lucide.createIcons();
            
            fetch('api/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    export_option: selectedOption,
                    parent_id: parentId
                })
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message || 'Export completed successfully', 'success');
                closeModal();
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred during export', 'error');
            })
            .finally(() => {
                confirmParentExport.innerHTML = originalText;
                confirmParentExport.disabled = false;
                lucide.createIcons();
            });
        });
        
        // Generic function to handle action buttons
        function handleActionButton(selector, endpoint, successMessage) {
            document.querySelectorAll(selector).forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parentId = this.getAttribute('data-id');
                    const icon = this.querySelector('i');
                    const originalText = this.innerHTML;
                    
                    // Show loading state if icon exists
                    if (icon) {
                        const originalIconClass = icon.className;
                        icon.className = 'w-4 h-4 animate-spin mr-1';
                        icon.setAttribute('data-lucide', 'loader-2');
                        this.disabled = true;
                        lucide.createIcons();
                    } else {
                        this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-1"></i> Processing...';
                        this.disabled = true;
                        lucide.createIcons();
                    }
                    
                    fetch(endpoint.replace(':id', parentId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ action: 'start', type: 'parent' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message || successMessage, 'success');
                        // Refresh the page after 2 seconds
                        setTimeout(() => location.reload(), 2000);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred', 'error');
                    })
                    .finally(() => {
                        // Restore original state
                        if (icon) {
                            icon.className = originalIconClass;
                            icon.setAttribute('data-lucide', originalText.includes('Resume') ? 'play' : 'zap');
                        }
                        this.innerHTML = originalText;
                        this.disabled = false;
                        lucide.createIcons();
                    });
                });
            });
        }
        
        // Set up all action buttons
        handleActionButton('.toggle-parent', 'api/resume.php?type=parent&id=:id', 'Process resumed successfully');
        handleActionButton('.generate-lasik', 'api/generate.php?type=lasik&parent_id=:id', 'LASIK generation started');
        handleActionButton('.generate-eklp', 'api/generate.php?type=eklp&parent_id=:id', 'EKLP generation started');
        handleActionButton('.generate-dpt', 'api/generate.php?type=dpt&parent_id=:id', 'DPT generation started');
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        // 保持输入框内容和URL同步
        searchInput.value = new URL(window.location.href).searchParams.get('search') || '';
        
        searchBtn.addEventListener('click', () => {
            const query = searchInput.value.trim();
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', 1); // 搜索时回到第一页
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
    });
</script>

<?php require_once 'includes/footer.php'; ?>