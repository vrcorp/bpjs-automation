<?php
require_once 'includes/header.php';

// Get stats
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM parents) as total_parents,
    (SELECT COUNT(*) FROM parents WHERE status = 'success') as completed_parents,
    (SELECT COUNT(*) FROM result WHERE sipp_status = 'success') as completed_sipp,
    (SELECT COUNT(*) FROM result WHERE lasik_status = 'success') as completed_lasik,
    (SELECT COUNT(*) FROM result WHERE eklp_status = 'success') as completed_eklp,
    (SELECT COUNT(*) FROM result WHERE dpt_status = 'success') as completed_dpt");
$stats = $stmt->fetch();
?>

<div class="container mx-auto px-4 py-6">
    <!-- Running Jobs Section -->
    <div id="runningJobsSection" class="mb-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl shadow-sm p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5 text-yellow-600 dark:text-yellow-400"></i>
                    <span class="font-semibold text-yellow-800 dark:text-yellow-200">Tugas yang Sedang Berjalan</span>
                </div>
                <button id="stopAllJobsBtn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-all text-sm font-medium flex items-center gap-1">
                    <i data-lucide="x-octagon" class="w-4 h-4"></i> Stop All
                </button>
            </div>
            <div id="runningJobsList">
                <div class="text-gray-500 dark:text-gray-300 text-sm">Sedang memuat...</div>
            </div>
        </div>
    </div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Dashboard Overview</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monitor and control data processing</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">Last updated: <?= date('Y-m-d H:i') ?></span>
            <button id="refreshBtn" class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <!-- Total Parents Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Parents</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_parents'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= $stats['completed_parents'] ?> completed</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50 transition-colors">
                    <i data-lucide="users" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_parents'] / $stats['total_parents']) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- SIPP Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed SIPP</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_sipp'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php if ($stats['total_parents'] > 0): ?>
                            <?= round(($stats['completed_sipp'] / $stats['total_parents']) * 100, 1) ?>% berhasil
                        <?php else: ?>
                            Tidak ada data
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center group-hover:bg-green-100 dark:group-hover:bg-green-900/50 transition-colors">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <?php if ($stats['total_parents'] > 0): ?>
                        <div class="h-full bg-green-500 rounded-full" style="width: <?= ($stats['completed_sipp'] / $stats['total_parents']) * 100 ?>%"></div>
                    <?php else: ?>
                        <div class="h-full bg-green-500 rounded-full" style="width: 0%"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- LASIK Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed LASIK</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_lasik'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php if ($stats['total_parents'] > 0): ?>
                            <?= round(($stats['completed_lasik'] / $stats['total_parents']) * 100, 1) ?>% berhasil
                        <?php else: ?>
                            Tidak ada data
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-100 dark:group-hover:bg-purple-900/50 transition-colors">
                    <i data-lucide="eye" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <?php if ($stats['total_parents'] > 0): ?>
                        <div class="h-full bg-purple-500 rounded-full" style="width: <?= ($stats['completed_lasik'] / $stats['total_parents']) * 100 ?>%"></div>
                    <?php else: ?>
                        <div class="h-full bg-purple-500 rounded-full" style="width: 0%"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- EKLP Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed EKLP</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_eklp'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php if ($stats['total_parents'] > 0): ?>
                            <?= round(($stats['completed_eklp'] / $stats['total_parents']) * 100, 1) ?>% success
                        <?php else: ?>
                            Tidak ada data
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center group-hover:bg-orange-100 dark:group-hover:bg-orange-900/50 transition-colors">
                    <i data-lucide="book-open" class="w-6 h-6 text-orange-600 dark:text-orange-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-orange-500 rounded-full" style="width: <?= ($stats['total_parents'] && $stats['total_parents'] != 0) ? ($stats['completed_eklp'] / $stats['total_parents']) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- DPT Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed DPT</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_dpt'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php if ($stats['total_parents'] > 0): ?>
                            <?= round(($stats['completed_dpt'] / $stats['total_parents']) * 100, 1) ?>% success
                        <?php else: ?>
                            Tidak ada data
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/50 transition-colors">
                    <i data-lucide="shield" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-500 rounded-full" style="width: <?= ($stats['total_parents'] && $stats['total_parents'] != 0) ? ($stats['completed_dpt'] / $stats['total_parents']) * 100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Control Buttons -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h2 class="text-lg md:text-xl font-semibold mb-4 text-gray-800 dark:text-white">Process Controls</h2>
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <!-- Generate All Control -->
            <button id="openGenerateAllModal" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                <span class="text-sm md:text-base font-medium">Generate Massal Sekali Klik</span>
                <i data-lucide="zap" class="w-4 h-4 ml-2"></i>
            </button>
            <!-- Export Button -->
            <button id="exportBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-800 to-gray-700 text-white rounded-lg hover:from-gray-900 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                <span class="text-sm md:text-base font-medium">Ekspor Semua Data</span>
                <i data-lucide="download" class="w-4 h-4 ml-2"></i>
            </button>
            <button id="uploadFileBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-700 to-green-600 text-white rounded-lg hover:from-green-800 hover:to-green-700 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                <span class="text-sm md:text-base font-medium">Upload File</span>
                <i data-lucide="upload" class="w-4 h-4 ml-2"></i>
            </button>
        </div>
    </div>

    <!-- Generate All Modal -->
    <div id="generateAllModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all duration-300 ease-in-out w-full max-w-md">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pilih Proses Generate Massal</h3>
                <button id="closeGenerateAllModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="radio" id="mode1" name="generateMode" value="sipp_lasik_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700" checked>
                        <label for="mode1" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → LASIK → DPT</label>
                    </div>
                    <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="radio" id="mode2" name="generateMode" value="sipp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <label for="mode2" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → DPT</label>
                    </div>
                    <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="radio" id="mode3" name="generateMode" value="sipp_eklp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <label for="mode3" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → EKLP → DPT</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancelGenerateAll" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Batal
                    </button>
                    <button id="confirmGenerateAll" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">
                        Mulai Generate
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Parents -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg md:text-xl font-semibold text-gray-800 dark:text-white">Recent Parents</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Last 5 processed records</p>
            </div>
            <a href="parents.php" class="mt-2 sm:mt-0 inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                View All <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
            </a>
        </div>
        
        <!-- Desktop Table -->
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
                    <?php
                    $stmt = $pdo->query("SELECT p.*, 
                        (SELECT COUNT(*) FROM result WHERE parent_id = p.id) as child_count
                        FROM parents p WHERE status = 'success' ORDER BY created_at DESC LIMIT 5");
                    while ($parent = $stmt->fetch()):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($parent['kpj']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                <?= ucfirst($parent['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= $parent['child_count'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="children.php?parent_id=<?= $parent['id'] ?>" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">
                                <i data-lucide="list" class="w-4 h-4 mr-1"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards -->
        <div class="md:hidden grid grid-cols-1 gap-4 p-4">
            <?php
            $stmt = $pdo->query("SELECT p.*, 
                (SELECT COUNT(*) FROM result WHERE parent_id = p.id) as child_count
                FROM parents p WHERE status = 'success' ORDER BY created_at DESC LIMIT 5");
            while ($parent = $stmt->fetch()):
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($parent['kpj']) ?></h3>
                        <div class="flex items-center mt-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
                                <?= ucfirst($parent['status']) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400"><?= $parent['child_count'] ?> children</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 dark:text-gray-500"><?= date('Y-m-d H:i', strtotime($parent['created_at'])) ?></p>
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="children.php?parent_id=<?= $parent['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm flex items-center">
                        <i data-lucide="list" class="w-4 h-4 mr-1"></i> View Details
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Induk Table Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg md:text-xl font-semibold text-gray-800 dark:text-white">Manajemen Induk</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data induk, pilih, tambah, dan cari</p>
            </div>
            <button id="addIndukBtn" class="mt-2 sm:mt-0 inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors bg-green-100 dark:bg-green-900 rounded-lg">
                <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Tambah Induk
            </button>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                <input id="searchInduk" type="text" placeholder="Cari induk..." class="w-full md:w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200" />
            </div>
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="indukTable">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Induk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="indukTableBody">
                        <!-- Data will be loaded by JS -->
                    </tbody>
                </table>
            </div>
            <!-- Mobile Cards -->
            <div class="md:hidden grid grid-cols-1 gap-4" id="indukCards">
                <!-- Data will be loaded by JS -->
            </div>
            <!-- Pagination -->
            <div class="flex justify-center mt-4" id="indukPagination"></div>
        </div>
    </div>

    <!-- Akun SIPP & EKLP Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg md:text-xl font-semibold text-gray-800 dark:text-white">Manajemen Akun SIPP & EKLP</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola akun SIPP dan EKLP, pilih, tambah, dan cari</p>
            </div>
            <button id="addAkunBtn" class="mt-2 sm:mt-0 inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors bg-green-100 dark:bg-green-900 rounded-lg">
                <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Tambah Akun
            </button>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                <div class="flex flex-col md:flex-row gap-2">
                    <input id="searchAkun" type="text" placeholder="Cari email..." class="w-full md:w-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200" />
                    <select id="filterTipe" class="w-full md:w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        <option value="">Semua Tipe</option>
                        <option value="sipp">SIPP</option>
                        <option value="eklp">EKLP</option>
                    </select>
                </div>
            </div>
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="akunTable">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="akunTableBody">
                        <!-- Data will be loaded by JS -->
                    </tbody>
                </table>
            </div>
            <!-- Mobile Cards -->
            <div class="md:hidden grid grid-cols-1 gap-4" id="akunCards">
                <!-- Data will be loaded by JS -->
            </div>
            <!-- Pagination -->
            <div class="flex justify-center mt-4" id="akunPagination"></div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all duration-300 ease-in-out w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Export Data</h3>
            <button id="closeExportModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div class="space-y-3">
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="exportOption1" name="exportOption" value="sipp_lasik_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700" checked>
                    <label for="exportOption1" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → LASIK → DPT</label>
                </div>
                
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="exportOption2" name="exportOption" value="sipp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="exportOption2" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → DPT</label>
                </div>
                
                <div class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <input type="radio" id="exportOption3" name="exportOption" value="sipp_eklp_dpt" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <label for="exportOption3" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">SIPP → EKLP → DPT</label>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button id="cancelExport" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button id="confirmExport" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">
                    Export
                </button>
            </div>
        </div>
    </div>
</div>



<!-- Modal for Add Induk -->
<div id="addIndukModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tambah Induk Baru</h3>
        <input id="newIndukInput" type="text" placeholder="Nama induk" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 mb-4" />
        <div class="flex justify-end space-x-3">
            <button id="cancelAddInduk" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button id="confirmAddInduk" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-md hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md hover:shadow-lg">Tambah</button>
        </div>
    </div>
</div>

<!-- Modal for Add Akun -->
<div id="addAkunModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tambah Akun Baru</h3>
        <div class="space-y-4">
            <input id="newAkunEmail" type="email" placeholder="Email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200" />
            <input id="newAkunPassword" type="password" placeholder="Password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200" />
            <select id="newAkunTipe" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                <option value="">Pilih Tipe</option>
                <option value="sipp">SIPP</option>
                <option value="eklp">EKLP</option>
            </select>
        </div>
        <div class="flex justify-end space-x-3 mt-4">
            <button id="cancelAddAkun" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button id="confirmAddAkun" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-md hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md hover:shadow-lg">Tambah</button>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    document.addEventListener('DOMContentLoaded', function() {
        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('animate-spin');
            
            // Simulate refresh (in a real app, this would reload data)
            setTimeout(() => {
                icon.classList.remove('animate-spin');
                showToast('Dashboard refreshed', 'success');
            }, 1000);
        });
        
        // Generate All Modal
        const openGenerateAllModal = document.getElementById('openGenerateAllModal');
        const generateAllModal = document.getElementById('generateAllModal');
        const closeGenerateAllModal = document.getElementById('closeGenerateAllModal');
        const cancelGenerateAll = document.getElementById('cancelGenerateAll');
        const confirmGenerateAll = document.getElementById('confirmGenerateAll');
        // 打开弹窗
        openGenerateAllModal.addEventListener('click', () => {
            generateAllModal.classList.remove('hidden');
            setTimeout(() => {
                generateAllModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
                generateAllModal.querySelector('div').classList.add('opacity-100', 'scale-100');
            }, 10);
        });
        // 关闭弹窗
        function closeGenModal() {
            generateAllModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
            generateAllModal.querySelector('div').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                generateAllModal.classList.add('hidden');
            }, 200);
        }
        closeGenerateAllModal.addEventListener('click', closeGenModal);
        cancelGenerateAll.addEventListener('click', closeGenModal);
        generateAllModal.addEventListener('click', (e) => {
            if (e.target === generateAllModal) closeGenModal();
        });
        // 确认生成
        confirmGenerateAll.addEventListener('click', () => {
            const selectedMode = document.querySelector('input[name="generateMode"]:checked').value;
            confirmGenerateAll.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Sedang menghasilkan...';
            confirmGenerateAll.disabled = true;
            lucide.createIcons();
            fetch('<?php echo $url_api;?>generate-all', {
                method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        mode: selectedMode
                    })
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || 'Tugas generate massal telah dikirim', 'success');
                closeGenModal();
            })
            .catch(err => {
                showToast('Gagal melakukan generate massal', 'error');
            })
            .finally(() => {
                confirmGenerateAll.innerHTML = 'Mulai Generate';
                confirmGenerateAll.disabled = false;
                lucide.createIcons();
            });
        });
        
        // Export modal
        const exportBtn = document.getElementById('exportBtn');
        const exportModal = document.getElementById('exportModal');
        const closeExportModal = document.getElementById('closeExportModal');
        const cancelExport = document.getElementById('cancelExport');
        const confirmExport = document.getElementById('confirmExport');
        
        // Open modal with animation
        const openModal = () => {
            exportModal.classList.remove('hidden');
            setTimeout(() => {
                exportModal.querySelector('div').classList.remove('opacity-0', 'scale-95');
                exportModal.querySelector('div').classList.add('opacity-100', 'scale-100');
            }, 10);
        };
        
        // Close modal with animation
        const closeModal = () => {
            exportModal.querySelector('div').classList.remove('opacity-100', 'scale-100');
            exportModal.querySelector('div').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                exportModal.classList.add('hidden');
            }, 200);
        };
        
        exportBtn.addEventListener('click', openModal);
        closeExportModal.addEventListener('click', closeModal);
        cancelExport.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        exportModal.addEventListener('click', (e) => {
            if (e.target === exportModal) {
                closeModal();
            }
        });
        
        confirmExport.addEventListener('click', () => {
            const selectedOption = document.querySelector('input[name="exportOption"]:checked').value;
            
            // Show loading state
            confirmExport.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i> Exporting...';
            confirmExport.disabled = true;
            lucide.createIcons();
            
            fetch('<?= $url_api ?>export-all', {
                method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        mode: selectedOption
                    })
            })
            .then(async res => {
                if (!res.ok) throw new Error('Export gagal');
                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                    a.download = `export_all_${selectedOption}.xlsx`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                showToast('Export berhasil', 'success');
                closeExportModal();
            })
            .catch(err => {
                showToast('Export gagal', 'error');
            })
            .finally(() => {
                confirmExportAll.innerHTML = 'Ekspor';
                confirmExportAll.disabled = false;
                lucide.createIcons();
            });
        });
        
        // Toggle buttons
        // The original toggleGenerate, toggleLasik, toggleEklp, toggleDpt are removed.
        // The new Generate All modal handles the batch generation.
        
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

        // Induk Table Logic
        const indukTableBody = document.getElementById('indukTableBody');
        const indukCards = document.getElementById('indukCards');
        const indukPagination = document.getElementById('indukPagination');
        const searchInduk = document.getElementById('searchInduk');
        let indukData = [];
        let indukPage = 1;
        let indukPageSize = 5;
        let indukSearch = '';

        function fetchInduk() {
            fetch(`/admin_panel/api/induk.php?page=${indukPage}&size=${indukPageSize}&search=${encodeURIComponent(indukSearch)}`)
                .then(res => res.json())
                .then(data => {
                    indukData = data.data;
                    renderIndukTable();
                    renderIndukCards();
                    renderIndukPagination(data.total, data.page, data.size);
                });
        }

        function renderIndukTable() {
            if (!indukTableBody) return;
            indukTableBody.innerHTML = '';
            indukData.forEach(row => {
                indukTableBody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium">${row.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">${row.induk}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${row.is_selected 
                                ? '<span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Ya</span>' 
                                : '<span class="inline-block px-2 py-1 text-xs font-semibold bg-gray-200 text-gray-500 rounded-full">Tidak</span>'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${row.created_at}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${row.is_selected 
                                ? '' 
                                : `<button class="setSelectedBtn px-3 py-1 bg-blue-500 text-white rounded mr-2" data-id="${row.id}">Set Selected</button>`
                            }
                        </td>
                    </tr>
                `;
            });
            document.querySelectorAll('.setSelectedBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    fetch('/admin_panel/api/induk.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'set_selected',
                            id
                        })
                    }).then(res => res.json()).then(() => fetchInduk());
                });
            });
        }

        function renderIndukCards() {
            if (!indukCards) return;
            indukCards.innerHTML = '';
            indukData.forEach(row => {
                indukCards.innerHTML += `
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-2">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white">${row.induk}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: ${row.id}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">${row.created_at}</p>
                                <p class="text-xs ${row.is_selected ? 'text-green-600' : 'text-gray-400'}">${row.is_selected ? 'Selected' : 'Not selected'}</p>
                            </div>
                            <button class="setSelectedBtn px-3 py-1 bg-blue-500 text-white rounded" data-id="${row.id}" ${row.is_selected ? 'disabled' : ''}>Set Selected</button>
                        </div>
                    </div>
                `;
            });
            document.querySelectorAll('.setSelectedBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    fetch('/admin_panel/api/induk.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'set_selected',
                            id
                        })
                    }).then(res => res.json()).then(() => fetchInduk());
                });
            });
        }

        function renderIndukPagination(total, page, size) {
            if (!indukPagination) return;
            const totalPages = Math.ceil(total / size);
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="mx-1 px-3 py-1 rounded ${i === page ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200'}" onclick="indukGoToPage(${i})">${i}</button>`;
            }
            indukPagination.innerHTML = html;
        }

        function indukGoToPage(page) {
            indukPage = page;
            fetchInduk();
        }
        window.indukGoToPage = indukGoToPage;
        searchInduk.addEventListener('input', function() {
            indukSearch = this.value;
            indukPage = 1;
            fetchInduk();
        });
        // Modal logic
        const addIndukBtn = document.getElementById('addIndukBtn');
        const addIndukModal = document.getElementById('addIndukModal');
        const cancelAddInduk = document.getElementById('cancelAddInduk');
        const confirmAddInduk = document.getElementById('confirmAddInduk');
        const newIndukInput = document.getElementById('newIndukInput');
        addIndukBtn.addEventListener('click', () => {
            addIndukModal.classList.remove('hidden');
        });
        cancelAddInduk.addEventListener('click', () => {
            addIndukModal.classList.add('hidden');
            newIndukInput.value = '';
        });
        confirmAddInduk.addEventListener('click', () => {
            const induk = newIndukInput.value.trim();
            if (!induk) return showToast('Nama induk wajib diisi', 'error');
            fetch('/admin_panel/api/induk.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add',
                    induk
                })
            }).then(res => res.json()).then(() => {
                addIndukModal.classList.add('hidden');
                newIndukInput.value = '';
                fetchInduk();
            });
        });
        // 初始化加载
        fetchInduk();

        // Akun SIPP & EKLP Section Logic
        const addAkunBtn = document.getElementById('addAkunBtn');
        const addAkunModal = document.getElementById('addAkunModal');
        const cancelAddAkun = document.getElementById('cancelAddAkun');
        const confirmAddAkun = document.getElementById('confirmAddAkun');
        const newAkunEmail = document.getElementById('newAkunEmail');
        const newAkunPassword = document.getElementById('newAkunPassword');
        const newAkunTipe = document.getElementById('newAkunTipe');
        const searchAkun = document.getElementById('searchAkun');
        const filterTipe = document.getElementById('filterTipe');
        const akunTableBody = document.getElementById('akunTableBody');
        const akunCards = document.getElementById('akunCards');
        const akunPagination = document.getElementById('akunPagination');
        let akunData = [];
        let akunPage = 1;
        let akunPageSize = 5;
        let akunSearch = '';
        let akunFilterTipe = '';

        function fetchAkun() {
            const params = new URLSearchParams({
                page: akunPage,
                size: akunPageSize,
                search: encodeURIComponent(akunSearch),
                tipe: akunFilterTipe
            });
            fetch(`/admin_panel/api/akun_sipp.php?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    akunData = data.data;
                    renderAkunTable();
                    renderAkunCards();
                    renderAkunPagination(data.total, data.page, data.size);
                });
        }

        function renderAkunTable() {
            if (!akunTableBody) return;
            akunTableBody.innerHTML = '';
            akunData.forEach(row => {
                akunTableBody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium">${row.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">${row.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${row.tipe}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${row.is_selected 
                                ? '<span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Ya</span>' 
                                : '<span class="inline-block px-2 py-1 text-xs font-semibold bg-gray-200 text-gray-500 rounded-full">Tidak</span>'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${row.created_at}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${row.is_selected 
                                ? '' 
                                : `<button class="setSelectedAkunBtn px-3 py-1 bg-blue-500 text-white rounded mr-2" data-id="${row.id}" data-tipe="${row.tipe}">Set Selected</button>`
                            }
                            <button class="deleteAkunBtn px-3 py-1 bg-red-500 text-white rounded" data-id="${row.id}">Delete</button>
                        </td>
                    </tr>
                `;
            });
            document.querySelectorAll('.setSelectedAkunBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const tipe = this.getAttribute('data-tipe');
                    fetch('/admin_panel/api/akun_sipp.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'set_selected',
                            id,
                            tipe
                        })
                    }).then(res => res.json()).then(() => fetchAkun());
                });
            });
            document.querySelectorAll('.deleteAkunBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Yakin ingin menghapus akun ini?')) {
                        fetch('/admin_panel/api/akun_sipp.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                id
                            })
                        }).then(res => res.json()).then(() => fetchAkun());
                    }
                });
            });
        }

        function renderAkunCards() {
            if (!akunCards) return;
            akunCards.innerHTML = '';
            akunData.forEach(row => {
                akunCards.innerHTML += `
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-2">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white">${row.email}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: ${row.id}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tipe: ${row.tipe}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">${row.created_at}</p>
                                <p class="text-xs ${row.is_selected ? 'text-green-600' : 'text-gray-400'}">${row.is_selected ? 'Selected' : 'Not selected'}</p>
                            </div>
                            <div class="flex flex-col gap-2">
                                ${row.is_selected 
                                    ? '' 
                                    : `<button class="setSelectedAkunBtn px-3 py-1 bg-blue-500 text-white rounded text-xs" data-id="${row.id}" data-tipe="${row.tipe}">Set Selected</button>`
                                }
                                <button class="deleteAkunBtn px-3 py-1 bg-red-500 text-white rounded text-xs" data-id="${row.id}">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            document.querySelectorAll('.setSelectedAkunBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const tipe = this.getAttribute('data-tipe');
                    fetch('/admin_panel/api/akun_sipp.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'set_selected',
                            id,
                            tipe
                        })
                    }).then(res => res.json()).then(() => fetchAkun());
                });
            });
            document.querySelectorAll('.deleteAkunBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Yakin ingin menghapus akun ini?')) {
                        fetch('/admin_panel/api/akun_sipp.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                id
                            })
                        }).then(res => res.json()).then(() => fetchAkun());
                    }
                });
            });
        }

        function renderAkunPagination(total, page, size) {
            if (!akunPagination) return;
            const totalPages = Math.ceil(total / size);
            let html = '';
            for (let i = 1; i <= totalPages; i++) {
                html += `<button class="mx-1 px-3 py-1 rounded ${i === page ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200'}" onclick="akunGoToPage(${i})">${i}</button>`;
            }
            akunPagination.innerHTML = html;
        }

        function akunGoToPage(page) {
            akunPage = page;
            fetchAkun();
        }
        window.akunGoToPage = akunGoToPage;
        searchAkun.addEventListener('input', function() {
            akunSearch = this.value;
            akunPage = 1;
            fetchAkun();
        });
        filterTipe.addEventListener('change', function() {
            akunFilterTipe = this.value;
            akunPage = 1;
            fetchAkun();
        });

        addAkunBtn.addEventListener('click', () => {
            addAkunModal.classList.remove('hidden');
        });
        cancelAddAkun.addEventListener('click', () => {
            addAkunModal.classList.add('hidden');
            newAkunEmail.value = '';
            newAkunPassword.value = '';
            newAkunTipe.value = '';
        });
        confirmAddAkun.addEventListener('click', () => {
            const email = newAkunEmail.value.trim();
            const password = newAkunPassword.value.trim();
            const tipe = newAkunTipe.value;

            if (!email) return showToast('Email wajib diisi', 'error');
            if (!password) return showToast('Password wajib diisi', 'error');
            if (!tipe) return showToast('Tipe akun wajib dipilih', 'error');

            fetch('/admin_panel/api/akun_sipp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add',
                    email,
                    password,
                    tipe
                })
            }).then(res => res.json()).then(() => {
                addAkunModal.classList.add('hidden');
                newAkunEmail.value = '';
                newAkunPassword.value = '';
                newAkunTipe.value = '';
                fetchAkun();
                showToast('Akun berhasil ditambahkan', 'success');
            }).catch(err => {
                showToast('Gagal menambahkan akun', 'error');
            });
        });
        // 初始化加载
        fetchAkun();

        document.getElementById('uploadFileBtn').addEventListener('click', function() {
            window.location.href = 'parents_file.php';
        });

        // Running Jobs Section
        const runningJobsSection = document.getElementById('runningJobsSection');
        const runningJobsList = document.getElementById('runningJobsList');
        const stopAllJobsBtn = document.getElementById('stopAllJobsBtn');

        function fetchRunningJobs() {
            fetch('/admin_panel/api/running_jobs.php')
                .then(res => res.json())
                .then(data => {
                    const jobs = data.data || [];
                    if (jobs.length === 0) {
                        runningJobsList.innerHTML = '<div class="text-gray-400 dark:text-gray-500 text-sm">Tidak ada tugas yang sedang berjalan</div>';
                        stopAllJobsBtn.disabled = true;
                        stopAllJobsBtn.classList.add('opacity-50','cursor-not-allowed');
                    } else {
                        stopAllJobsBtn.disabled = false;
                        stopAllJobsBtn.classList.remove('opacity-50','cursor-not-allowed');
                        runningJobsList.innerHTML = `
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs md:text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">ID</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Mode</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Parent</th>
                                            <th class="px-3 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">File?</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Waktu Mulai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        ${jobs.map(j => `
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">${j.id}</td>
                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">${j.mode}</td>
                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">${j.parent_id ?? '-'}</td>
                                                <td class="px-3 py-2 text-center">
                                                    ${j.is_file == 1 
                                                        ? '<span class="inline-block text-green-600 dark:text-green-400 text-lg">✔️</span>' 
                                                        : '<span class="inline-block text-gray-400 dark:text-gray-600 text-lg">—</span>'}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                                                        ${j.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200' : ''}
                                                        ${j.status === 'process' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : ''}
                                                        ${j.status === 'finish' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : ''}
                                                        ${j.status === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' : ''}
                                                    ">
                                                        ${j.status}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">${j.created_at}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                });
        }
        stopAllJobsBtn.addEventListener('click', function() {
            if (!confirm('Yakin ingin menghentikan semua tugas dan menutup semua browser?')) return;
            stopAllJobsBtn.disabled = true;
            stopAllJobsBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2"></i>Stopping...';
            fetch('<?php echo $url_api;?>close-all-tabs', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Semua tugas telah dihentikan dan semua browser telah ditutup', 'success');
                } else {
                    showToast(data.error || 'Operasi gagal', 'error');
                }
                fetchRunningJobs();
            })
            .catch(() => {
                showToast('Permintaan gagal', 'error');
                fetchRunningJobs();
            })
            .finally(() => {
                stopAllJobsBtn.innerHTML = '<i data-lucide="x-octagon" class="w-4 h-4"></i> Stop All';
                stopAllJobsBtn.disabled = false;
                lucide.createIcons();
            });
        });
        // 页面加载时自动刷新
        fetchRunningJobs();
        // 可选：定时刷新
        setInterval(fetchRunningJobs, 10000);
    });
</script>

<?php require_once 'includes/footer.php'; ?>