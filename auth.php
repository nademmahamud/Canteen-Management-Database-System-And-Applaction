<?php
/**
 * Campus Canteen Management System - Authentication Handler
 */

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: /CanteenManagement/login.php");
    exit();
}

// Get current user info
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']], "i");
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: /CanteenManagement/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Get user initials for avatar
function getUserInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
        }
    }
    return substr($initials, 0, 2);
}

// Get sidebar menu based on role
function getSidebarMenu() {
    $role = $_SESSION['user_role'] ?? 'student';
    
    $menu = [];
    
    // Dashboard - All users
    $menu[] = [
        'icon' => 'tachometer-alt',
        'label' => 'Dashboard',
        'url' => '/CanteenManagement/pages/dashboard.php',
        'active' => basename($_SERVER['PHP_SELF']) === 'dashboard.php'
    ];
    
    // Users - Admin only
    if ($role === 'admin') {
        $menu[] = [
            'icon' => 'users',
            'label' => 'Users',
            'url' => '/CanteenManagement/pages/users.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'users.php'
        ];
    }
    
    // Menu Items - Admin & Staff
    if (in_array($role, ['admin', 'staff'])) {
        $menu[] = [
            'icon' => 'utensils',
            'label' => 'Menu Items',
            'url' => '/CanteenManagement/pages/menu.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'menu.php'
        ];
    }
    
    // Orders - All users
    $menu[] = [
        'icon' => 'shopping-cart',
        'label' => 'Orders',
        'url' => '/CanteenManagement/pages/orders.php',
        'active' => basename($_SERVER['PHP_SELF']) === 'orders.php'
    ];
    
    // Billing - Admin & Cashier
    if (in_array($role, ['admin', 'cashier'])) {
        $menu[] = [
            'icon' => 'file-invoice-dollar',
            'label' => 'Billing',
            'url' => '/CanteenManagement/pages/billing.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'billing.php'
        ];
    }
    
    // Inventory - Admin & Staff
    if (in_array($role, ['admin', 'staff'])) {
        $menu[] = [
            'icon' => 'boxes',
            'label' => 'Inventory',
            'url' => '/CanteenManagement/pages/inventory.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'inventory.php'
        ];
    }
    
    // Suppliers - Admin only
    if ($role === 'admin') {
        $menu[] = [
            'icon' => 'truck',
            'label' => 'Suppliers',
            'url' => '/CanteenManagement/pages/suppliers.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'suppliers.php'
        ];
    }
    
    // Reports - Admin only
    if ($role === 'admin') {
        $menu[] = [
            'icon' => 'chart-bar',
            'label' => 'Reports',
            'url' => '/CanteenManagement/pages/reports.php',
            'active' => basename($_SERVER['PHP_SELF']) === 'reports.php'
        ];
    }
    
    // Settings - All users
    $menu[] = [
        'icon' => 'cog',
        'label' => 'Settings',
        'url' => '/CanteenManagement/pages/settings.php',
        'active' => basename($_SERVER['PHP_SELF']) === 'settings.php'
    ];
    
    return $menu;
}
?>
