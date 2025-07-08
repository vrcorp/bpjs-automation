<?php
require_once 'includes/header.php';

// 分页、搜索参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 查询条件
$whereClause = 'WHERE is_file = 1';
$params = [];
if ($search !== '') {
    $whereClause .= ' AND (kpj LIKE :search)';
    $params[':search'] = "%$search%";
}
// 总数
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM parents $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
// 查询数据
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
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$parents = $stmt->fetchAll();

// 检查是否有 is_file=1 且 status=pending/process 的 running_jobs
$jobStmt = $pdo->prepare("SELECT * FROM running_jobs WHERE is_file=1 AND status IN ('pending','process')");
$jobStmt->execute();
$runningMassal = $jobStmt->fetch();
// 获取所有 parent_id 的 running_jobs
$jobParentStmt = $pdo->prepare("SELECT parent_id, status FROM running_jobs WHERE is_file=1 AND status IN ('pending','process')");
$jobParentStmt->execute();
$parentJobs = [];
foreach ($jobParentStmt->fetchAll() as $row) {
    $parentJobs[$row['parent_id']] = $row['status'];
}
?>
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-6">
        <button id="addFileParentBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-lg hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
            <span class="text-sm md:text-base font-medium">Tambah Data</span>
            <i data-lucide="plus" class="w-4 h-4 ml-2"></i>
        </button>
        <button id="generateAllFileBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
            <span class="text-sm md:text-base font-medium">
                <?= $runningMassal ? 'Stop Massal' : 'Generate Massal' ?>
            </span>
            <i data-lucide="<?= $runningMassal ? 'square' : 'zap' ?>" class="w-4 h-4 ml-2"></i>
        </button>
        <button id="exportAllFileBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-800 to-gray-700 text-white rounded-lg hover:from-gray-900 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
            <span class="text-sm md:text-base font-medium">Ekspor Semua Data</span>
            <i data-lucide="download" class="w-4 h-4 ml-2"></i>
        </button>
    </div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Parents dari File</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Data parent yang diupload via file excel</p>
        </div>
        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2">
            <div class="relative flex-grow">
                <input type="text" id="searchFileParent" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" class="w-full pl-10 pr-4 py-2 border rounded-md bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
            </div>
            <button id="searchFileParentBtn" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
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
                                <div class="flex gap-2 text-xs mt-1">
                                    <span class="text-green-600 dark:text-green-400"><?= $parent['success_lasik'] ?> LASIK</span>
                                    <span class="text-green-600 dark:text-green-400"><?= $parent['success_eklp'] ?> EKLP</span>
                                    <span class="text-green-600 dark:text-green-400"><?= $parent['success_dpt'] ?> DPT</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <?php
                            $allDone = ($parent['pending_lasik'] == 0 && $parent['pending_eklp'] == 0 && $parent['pending_dpt'] == 0);
                            $allEmpty = ($parent['pending_lasik'] == $parent['child_count'] && $parent['pending_eklp'] == $parent['child_count'] && $parent['pending_dpt'] == $parent['child_count']);
                            ?>
                            <?php if (!$allDone): ?>
                                <?php if (isset($parentJobs[$parent['id']])): ?>
                                    <a href="#" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-200 stop-parent" data-id="<?= $parent['id'] ?>">
                                        <i data-lucide="square" class="w-4 h-4 inline mr-1"></i> Stop
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors duration-200 generate-parent" data-id="<?= $parent['id'] ?>">
                                        <i data-lucide="zap" class="w-4 h-4 inline mr-1"></i> Generate
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!$allEmpty): ?>
                                <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 transition-colors duration-200 export-parent" data-id="<?= $parent['id'] ?>">
                                    <i data-lucide="download" class="w-4 h-4 inline mr-1"></i> Export
                                </a>
                            <?php endif; ?>
                            <?php if ($parent['child_count'] > 0): ?>
                                <a href="children.php?parent_id=<?= $parent['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors duration-200 detail-parent">
                                    <i data-lucide="list" class="w-4 h-4 inline mr-1"></i> Detail
                                </a>
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
                    <?php
                    $allDone = ($parent['pending_lasik'] == 0 && $parent['pending_eklp'] == 0 && $parent['pending_dpt'] == 0);
                    $allEmpty = ($parent['pending_lasik'] == $parent['child_count'] && $parent['pending_eklp'] == $parent['child_count'] && $parent['pending_dpt'] == $parent['child_count']);
                    ?>
                    <?php if (!$allDone): ?>
                        <?php if (isset($parentJobs[$parent['id']])): ?>
                            <a href="#" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm flex items-center stop-parent" data-id="<?= $parent['id'] ?>">
                                <i data-lucide="square" class="w-4 h-4 mr-1"></i> Stop
                            </a>
                        <?php else: ?>
                            <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm flex items-center generate-parent" data-id="<?= $parent['id'] ?>">
                                <i data-lucide="zap" class="w-4 h-4 mr-1"></i> Generate
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!$allEmpty): ?>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 text-sm flex items-center export-parent" data-id="<?= $parent['id'] ?>">
                            <i data-lucide="download" class="w-4 h-4 mr-1"></i> Export
                        </a>
                    <?php endif; ?>
                    <?php if ($parent['child_count'] > 0): ?>
                        <a href="children.php?parent_id=<?= $parent['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm flex items-center detail-parent">
                            <i data-lucide="list" class="w-4 h-4 mr-1"></i> Detail
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
                        <?php 
                        $totalPages = ceil($total / $perPage);
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        $searchParam = $search !== '' ? '&search=' . urlencode($search) : '';
                        if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                            <span class="sr-only">Previous</span>
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </a>
                        <?php endif; ?>
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?><?= $searchParam ?>" class="<?= $i === $page ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-100' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors duration-200">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $searchParam ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                            <span class="sr-only">Next</span>
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Upload File -->
    <div id="addFileParentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tambah Data dari File</h3>
            <form id="uploadFileForm" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Tugas (KPJ)</label>
                    <input name="kpj" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File Excel (.xlsx)</label>
                    <input name="file" type="file" accept=".xlsx" class="w-full" required />
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelAddFileParent" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-md hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md hover:shadow-lg">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
lucide.createIcons();
// 搜索功能
const searchInput = document.getElementById('searchFileParent');
const searchBtn = document.getElementById('searchFileParentBtn');
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
        searchBtn.click();
    }
});
// 上传弹窗逻辑
const addFileParentBtn = document.getElementById('addFileParentBtn');
const addFileParentModal = document.getElementById('addFileParentModal');
const cancelAddFileParent = document.getElementById('cancelAddFileParent');
addFileParentBtn.addEventListener('click', () => {
    addFileParentModal.classList.remove('hidden');
});
cancelAddFileParent.addEventListener('click', () => {
    addFileParentModal.classList.add('hidden');
    document.getElementById('uploadFileForm').reset();
});
// 上传表单提交
const uploadFileForm = document.getElementById('uploadFileForm');
uploadFileForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api/upload_file_parent.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Upload gagal');
        }
    })
    .catch(() => alert('Upload gagal'));
});
// 生成/导出功能
const generateAllFileBtn = document.getElementById('generateAllFileBtn');
const exportAllFileBtn = document.getElementById('exportAllFileBtn');

// 复用弹窗
const generateAllModal = document.createElement('div');
generateAllModal.id = 'generateAllFileModal';
generateAllModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4';
generateAllModal.innerHTML = `
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all duration-300 ease-in-out w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pilih Proses Generate Massal</h3>
            <button id="closeGenerateAllFileModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="mode1f" name="generateModeAllFile" value="sipp_lasik_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700" checked>
                    <label for="mode1f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → LASIK → DPT</label>
                </div>
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="mode2f" name="generateModeAllFile" value="sipp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="mode2f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → DPT</label>
                </div>
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="mode3f" name="generateModeAllFile" value="sipp_eklp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="mode3f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → EKLP → DPT</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button id="cancelGenerateAllFile" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Batal</button>
                <button id="confirmGenerateAllFile" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">Mulai Generate</button>
            </div>
        </div>
    </div>
`;
document.body.appendChild(generateAllModal);
lucide.createIcons();

function showGenerateAllFileModal(parentId = null) {
    generateAllModal.classList.remove('hidden');
    setTimeout(() => {
        generateAllModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
        generateAllModal.querySelector('div').classList.add('opacity-100', 'scale-100');
    }, 10);
    generateAllModal.setAttribute('data-parent-id', parentId || '');
}
function closeGenerateAllFileModal() {
    generateAllModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
    generateAllModal.querySelector('div').classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        generateAllModal.classList.add('hidden');
    }, 200);
    generateAllModal.setAttribute('data-parent-id', '');
}
document.getElementById('closeGenerateAllFileModal').onclick = closeGenerateAllFileModal;
document.getElementById('cancelGenerateAllFile').onclick = closeGenerateAllFileModal;
generateAllModal.addEventListener('click', (e) => {
    if (e.target === generateAllModal) closeGenerateAllFileModal();
});

generateAllFileBtn.addEventListener('click', function() {
    <?php if ($runningMassal): ?>
    if (confirm('Yakin ingin menghentikan proses massal?')) {
        fetch('<?= $url_api ?>stop-massal', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_file: true })
        }).then(res => res.json()).then(data => {
            alert(data.message || 'Stop massal dikirim');
            window.location.reload();
        }).catch(() => alert('Gagal stop massal'));
    }
    <?php else: ?>
    showGenerateAllFileModal();
    <?php endif; ?>
});

// 单条 generate
let currentGenerateParentId = null;
document.querySelectorAll('.generate-parent').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        currentGenerateParentId = this.getAttribute('data-id');
        showGenerateAllFileModal(currentGenerateParentId);
    });
});

document.getElementById('confirmGenerateAllFile').addEventListener('click', function() {
    const selectedMode = document.querySelector('input[name="generateModeAllFile"]:checked').value;
    const parentId = generateAllModal.getAttribute('data-parent-id');
    this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Sedang menghasilkan...';
    this.disabled = true;
    lucide.createIcons();
    fetch('<?= $url_api ?>generate-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(
            parentId 
                ? { mode: selectedMode, parentId, is_file: true } 
                : { mode: selectedMode, is_file: true }
        )
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || 'Tugas generate telah dikirim');
        closeGenerateAllFileModal();
    })
    .catch(() => alert('Gagal generate'))
    .finally(() => {
        this.innerHTML = 'Mulai Generate';
        this.disabled = false;
        lucide.createIcons();
        currentGenerateParentId = null;
    });
});

// 导出弹窗
const exportAllFileModal = document.createElement('div');
exportAllFileModal.id = 'exportAllFileModal';
exportAllFileModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4';
exportAllFileModal.innerHTML = `
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all duration-300 ease-in-out w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pilih Mode Ekspor Massal</h3>
            <button id="closeExportAllFileModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="emode1f" name="exportModeAllFile" value="sipp_lasik_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700" checked>
                    <label for="emode1f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → LASIK → DPT</label>
                </div>
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="emode2f" name="exportModeAllFile" value="sipp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="emode2f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → DPT</label>
                </div>
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="emode3f" name="exportModeAllFile" value="sipp_eklp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="emode3f" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → EKLP → DPT</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button id="cancelExportAllFile" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Batal</button>
                <button id="confirmExportAllFile" class="px-4 py-2 bg-gradient-to-r from-gray-800 to-gray-700 text-white rounded-md hover:from-gray-900 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg">Ekspor</button>
            </div>
        </div>
    </div>
`;
document.body.appendChild(exportAllFileModal);
lucide.createIcons();

function showExportAllFileModal(parentId = null) {
    exportAllFileModal.classList.remove('hidden');
    setTimeout(() => {
        exportAllFileModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
        exportAllFileModal.querySelector('div').classList.add('opacity-100', 'scale-100');
    }, 10);
    exportAllFileModal.setAttribute('data-parent-id', parentId || '');
}
function closeExportAllFileModal() {
    exportAllFileModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
    exportAllFileModal.querySelector('div').classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        exportAllFileModal.classList.add('hidden');
    }, 200);
    exportAllFileModal.setAttribute('data-parent-id', '');
}
document.getElementById('closeExportAllFileModal').onclick = closeExportAllFileModal;
document.getElementById('cancelExportAllFile').onclick = closeExportAllFileModal;
exportAllFileModal.addEventListener('click', (e) => {
    if (e.target === exportAllFileModal) closeExportAllFileModal();
});

exportAllFileBtn.addEventListener('click', () => showExportAllFileModal());

// 单条 export
let currentExportParentId = null;
document.querySelectorAll('.export-parent').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        currentExportParentId = this.getAttribute('data-id');
        showExportAllFileModal(currentExportParentId);
    });
});

document.getElementById('confirmExportAllFile').addEventListener('click', function() {
    const selectedMode = document.querySelector('input[name="exportModeAllFile"]:checked').value;
    const parentId = exportAllFileModal.getAttribute('data-parent-id');
    this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Exporting...';
    this.disabled = true;
    lucide.createIcons();
    fetch('<?= $url_api ?>export-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(parentId ? { mode: selectedMode, parentId, is_file: true } : { mode: selectedMode, is_file: true })
    })
    .then(async res => {
        if (!res.ok) throw new Error('Export gagal');
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = parentId ? `export_parent_${parentId}_${selectedMode}.xlsx` : `export_all_file_${selectedMode}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        alert('Export berhasil');
        closeExportAllFileModal();
    })
    .catch(() => alert('Export gagal'))
    .finally(() => {
        this.innerHTML = 'Ekspor';
        this.disabled = false;
        lucide.createIcons();
        currentExportParentId = null;
    });
});

document.querySelectorAll('.stop-parent').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const parentId = this.getAttribute('data-id');
        if (confirm('Yakin ingin menghentikan proses parent ini?')) {
            fetch('<?= $url_api ?>stop-job', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ parentId, is_file: true })
            }).then(res => res.json()).then(data => {
                alert(data.message || 'Stop parent dikirim');
                window.location.reload();
            }).catch(() => alert('Gagal stop parent'));
        }
    });
});
</script>
<?php require_once 'includes/footer.php'; ?> 