<?php
require_once 'auth.php';
requireAuth(); // 确保用户已登录
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* 移动端卡片视图自定义样式，支持暗黑模式 */
        @media (max-width: 768px) {
            .responsive-table {
                border: none;
            }
            .responsive-table thead {
                display: none;
            }
            .responsive-table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                padding: 1rem;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
                background-color: #fff;
            }
            .dark .responsive-table tr {
                border: 1px solid #374151;
                background-color: #1f2937;
                box-shadow: 0 1px 3px 0 rgba(0,0,0,0.3), 0 1px 2px 0 rgba(0,0,0,0.25);
            }
            .responsive-table td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border: none;
                color: #1f2937;
            }
            .dark .responsive-table td {
                color: #f3f4f6;
            }
            .responsive-table td:before {
                content: attr(data-label);
                font-weight: 600;
                padding-right: 0.5rem;
                color: #374151;
            }
            .dark .responsive-table td:before {
                color: #d1d5db;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <header class="bg-white dark:bg-gray-800 shadow-md dark:shadow-lg">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold text-blue-600 dark:text-blue-400">BPJS Automation</a>
                </div>
                <div class="flex items-center">
                    <a href="logout.php" class="flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 dark:text-white">
                        <i data-lucide="log-out" class="w-4 h-4 mr-2"></i>
                        <span class="hidden dark:inline">Logout</span>
                        <span class="inline dark:hidden">Logout</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>
    <main>