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
                    <div class="h-full bg-blue-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_parents']/$stats['total_parents'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- SIPP Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed SIPP</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_sipp'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= round(($stats['completed_sipp']/$stats['total_parents'])*100, 1) ?>% success</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center group-hover:bg-green-100 dark:group-hover:bg-green-900/50 transition-colors">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_sipp']/$stats['total_parents'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- LASIK Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed LASIK</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_lasik'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= round(($stats['completed_lasik']/$stats['total_parents'])*100, 1) ?>% success</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-100 dark:group-hover:bg-purple-900/50 transition-colors">
                    <i data-lucide="eye" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_lasik']/$stats['total_parents'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- EKLP Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed EKLP</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_eklp'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= round(($stats['completed_eklp']/$stats['total_parents'])*100, 1) ?>% success</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center group-hover:bg-orange-100 dark:group-hover:bg-orange-900/50 transition-colors">
                    <i data-lucide="book-open" class="w-6 h-6 text-orange-600 dark:text-orange-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-orange-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_eklp']/$stats['total_parents'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- DPT Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completed DPT</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['completed_dpt'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1"><?= round(($stats['completed_dpt']/$stats['total_parents'])*100, 1) ?>% success</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/50 transition-colors">
                    <i data-lucide="shield" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-500 rounded-full" style="width: <?= $stats['total_parents'] ? ($stats['completed_dpt']/$stats['total_parents'])*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Control Buttons -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h2 class="text-lg md:text-xl font-semibold mb-4 text-gray-800 dark:text-white">Process Controls</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Generate Control -->
            <div class="flex flex-col">
                <button id="toggleGenerate" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                    <span id="generateText" class="text-sm md:text-base font-medium">Start Generate</span>
                    <i data-lucide="play" class="w-4 h-4 ml-2"></i>
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">SIPP Data Generation</span>
            </div>
            
            <!-- LASIK Control -->
            <div class="flex flex-col">
                <button id="toggleLasik" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-lg hover:from-green-700 hover:to-green-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                    <span id="lasikText" class="text-sm md:text-base font-medium">Start LASIK</span>
                    <i data-lucide="play" class="w-4 h-4 ml-2"></i>
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">LASIK Processing</span>
            </div>
            
            <!-- EKLP Control -->
            <div class="flex flex-col">
                <button id="toggleEklp" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-500 text-white rounded-lg hover:from-purple-700 hover:to-purple-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                    <span id="eklpText" class="text-sm md:text-base font-medium">Start EKLP</span>
                    <i data-lucide="play" class="w-4 h-4 ml-2"></i>
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">EKLP Processing</span>
            </div>
            
            <!-- DPT Control -->
            <div class="flex flex-col">
                <button id="toggleDpt" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-yellow-600 to-yellow-500 text-white rounded-lg hover:from-yellow-700 hover:to-yellow-600 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                    <span id="dptText" class="text-sm md:text-base font-medium">Start DPT</span>
                    <i data-lucide="play" class="w-4 h-4 ml-2"></i>
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">DPT Processing</span>
            </div>
        </div>
        
        <!-- Export Button -->
        <div class="mt-6 flex justify-center">
            <button id="exportBtn" class="flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-800 to-gray-700 text-white rounded-lg hover:from-gray-900 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                <span class="text-sm md:text-base font-medium">Export All Data</span>
                <i data-lucide="download" class="w-4 h-4 ml-2"></i>
            </button>
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
                        FROM parents p ORDER BY created_at DESC LIMIT 5");
                    while ($parent = $stmt->fetch()):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($parent['kpj']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                   ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
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
                FROM parents p ORDER BY created_at DESC LIMIT 5");
            while ($parent = $stmt->fetch()):
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($parent['kpj']) ?></h3>
                        <div class="flex items-center mt-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                <?= $parent['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 
                                   ($parent['status'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100') ?>">
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
        
        // Control buttons functionality
        const toggleGenerate = document.getElementById('toggleGenerate');
        const toggleLasik = document.getElementById('toggleLasik');
        const toggleEklp = document.getElementById('toggleEklp');
        const toggleDpt = document.getElementById('toggleDpt');
        
        // Check current status (you would fetch this from your API)
        let generateRunning = false;
        let lasikRunning = false;
        let eklpRunning = false;
        let dptRunning = false;
        
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
            
            fetch('api/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ export_option: selectedOption })
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
                confirmExport.innerHTML = 'Export';
                confirmExport.disabled = false;
                lucide.createIcons();
            });
        });
        
        // Toggle buttons
        toggleGenerate.addEventListener('click', () => {
            generateRunning = !generateRunning;
            updateButtonState(toggleGenerate, 'generateText', generateRunning, 'Generate', 'from-blue-600 to-blue-500', 'from-red-600 to-red-500');
            callApi('api/generate.php', { action: generateRunning ? 'start' : 'stop' });
        });
        
        toggleLasik.addEventListener('click', () => {
            lasikRunning = !lasikRunning;
            updateButtonState(toggleLasik, 'lasikText', lasikRunning, 'LASIK', 'from-green-600 to-green-500', 'from-red-600 to-red-500');
            callApi('api/lasik.php', { action: lasikRunning ? 'start' : 'stop', type: 'all' });
        });
        
        toggleEklp.addEventListener('click', () => {
            eklpRunning = !eklpRunning;
            updateButtonState(toggleEklp, 'eklpText', eklpRunning, 'EKLP', 'from-purple-600 to-purple-500', 'from-red-600 to-red-500');
            callApi('api/eklp.php', { action: eklpRunning ? 'start' : 'stop', type: 'all' });
        });
        
        toggleDpt.addEventListener('click', () => {
            dptRunning = !dptRunning;
            updateButtonState(toggleDpt, 'dptText', dptRunning, 'DPT', 'from-yellow-600 to-yellow-500', 'from-red-600 to-red-500');
            callApi('api/dpt.php', { action: dptRunning ? 'start' : 'stop', type: 'all' });
        });

        function callApi(url, body) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message || data.msg, 'success');
                // Refresh the page after 2 seconds if action was successful
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
        }
        
        function updateButtonState(button, textId, isRunning, name, startGradient, stopGradient) {
            const textElement = document.getElementById(textId);
            const icon = button.querySelector('i');
            
            // Remove all gradient classes
            button.className = button.className.replace(/from-\w+-\d+ to-\w+-\d+/g, '');
            
            if (isRunning) {
                // Add stop gradient
                button.classList.add(...stopGradient.split(' '));
                textElement.textContent = `Stop ${name}`;
                if (icon) {
                    icon.setAttribute('data-lucide', 'pause');
                }
            } else {
                // Add start gradient
                button.classList.add(...startGradient.split(' '));
                textElement.textContent = `Start ${name}`;
                if (icon) {
                    icon.setAttribute('data-lucide', 'play');
                }
            }
            
            // Refresh the icon
            lucide.createIcons();
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
    });
</script>

<?php require_once 'includes/footer.php'; ?>