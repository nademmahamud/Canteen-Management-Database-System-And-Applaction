<?php
/**
 * Campus Canteen Management System - Helper Functions
 */

require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['admin']);
}

function isStaff() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['admin', 'staff']);
}

function isCashier() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['admin', 'cashier']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /CanteenManagement/login.php");
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header("Location: /CanteenManagement/pages/dashboard.php");
        exit();
    }
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle');
        echo '<div class="alert alert-' . $flash['type'] . ' animate-slide-in">
            <i class="fas fa-' . $icon . '"></i>
            <span>' . htmlspecialchars($flash['message']) . '</span>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>';
    }
}

// Format helpers
function formatPrice($price) {
    return '¥' . number_format($price, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($date) {
    return date('M d, Y h:i A', strtotime($date));
}

function timeAgo($date) {
    $time = strtotime($date);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($date);
}

// Status badge helpers
function getStatusBadge($status) {
    $badges = [
        'active' => 'badge-success',
        'inactive' => 'badge-secondary',
        'pending' => 'badge-warning',
        'processing' => 'badge-info',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
        'paid' => 'badge-success',
        'unpaid' => 'badge-warning',
        'refunded' => 'badge-secondary',
        'in_stock' => 'badge-success',
        'low_stock' => 'badge-warning',
        'out_of_stock' => 'badge-danger',
        'student' => 'badge-info',
        'admin' => 'badge-primary',
        'staff' => 'badge-success',
        'cashier' => 'badge-warning'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Generate order ID
function generateOrderId($id) {
    return 'ORD-' . str_pad($id, 3, '0', STR_PAD_LEFT);
}

function generatePaymentId($id) {
    return 'PAY-' . str_pad($id, 3, '0', STR_PAD_LEFT);
}

function generateInvoiceId($id) {
    return 'INV-' . str_pad($id, 3, '0', STR_PAD_LEFT);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    execute("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$user_id, $action, $details, $ip], "isss");
}

// Dashboard statistics
function getDashboardStats() {
    return [
        'total_orders' => getCount("SELECT COUNT(*) FROM orders"),
        'total_sales' => getCount("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'"),
        'total_users' => getCount("SELECT COUNT(*) FROM users WHERE role != 'admin'"),
        'total_items' => getCount("SELECT COUNT(*) FROM foods WHERE is_available = 1"),
        'pending_orders' => getCount("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
        'processing_orders' => getCount("SELECT COUNT(*) FROM orders WHERE status = 'processing'"),
        'completed_orders' => getCount("SELECT COUNT(*) FROM orders WHERE status = 'completed'"),
        'cancelled_orders' => getCount("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'"),
        'low_stock_items' => getCount("SELECT COUNT(*) FROM inventory WHERE status = 'low_stock'"),
        'out_of_stock_items' => getCount("SELECT COUNT(*) FROM inventory WHERE status = 'out_of_stock'"),
        'todays_orders' => getCount("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()"),
        'todays_sales' => getCount("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_date) = CURDATE() AND payment_status = 'paid'")
    ];
}

// Recent orders
function getRecentOrders($limit = 10) {
    return fetchAll("SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.order_date DESC LIMIT ?", [$limit], "i");
}

// Top selling items
function getTopSellingItems($limit = 5) {
    return fetchAll("SELECT f.food_name, f.image, SUM(oi.quantity) as total_sold 
        FROM order_items oi 
        JOIN foods f ON oi.food_id = f.food_id 
        GROUP BY oi.food_id 
        ORDER BY total_sold DESC LIMIT ?", [$limit], "i");
}

// Sales by category
function getSalesByCategory() {
    return fetchAll("SELECT c.category_name, SUM(oi.total_price) as amount
        FROM order_items oi
        JOIN foods f ON oi.food_id = f.food_id
        JOIN categories c ON f.category_id = c.category_id
        GROUP BY c.category_id
        ORDER BY amount DESC");
}

// Daily sales for chart
function getDailySales($days = 7) {
    return fetchAll("SELECT DATE(order_date) as date, COUNT(*) as orders, SUM(total_amount) as sales
        FROM orders
        WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(order_date)
        ORDER BY date", [$days], "i");
}

// Payment method distribution
function getPaymentDistribution() {
    return fetchAll("SELECT payment_method, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE payment_status = 'completed'
        GROUP BY payment_method");
}

// Get all foods with category
function getAllFoods() {
    return fetchAll("SELECT f.*, c.category_name 
        FROM foods f 
        LEFT JOIN categories c ON f.category_id = c.category_id 
        ORDER BY f.created_at DESC");
}

// Get all users
function getAllUsers() {
    return fetchAll("SELECT * FROM users ORDER BY created_at DESC");
}

// Get all inventory items
function getAllInventory() {
    return fetchAll("SELECT i.*, f.food_name, f.image 
        FROM inventory i 
        LEFT JOIN foods f ON i.food_id = f.food_id 
        ORDER BY i.last_updated DESC");
}

// Get all suppliers
function getAllSuppliers() {
    return fetchAll("SELECT * FROM suppliers ORDER BY created_at DESC");
}

// Get all orders with details
function getAllOrders() {
    return fetchAll("SELECT o.*, u.full_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC");
}

// Get order details
function getOrderDetails($order_id) {
    return fetchOne("SELECT o.*, u.full_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?", [$order_id], "i");
}

// Get order items
function getOrderItems($order_id) {
    return fetchAll("SELECT oi.*, f.food_name, f.image
        FROM order_items oi
        JOIN foods f ON oi.food_id = f.food_id
        WHERE oi.order_id = ?", [$order_id], "i");
}

// CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Pagination helper
function paginate($page, $total, $per_page = 10) {
    $total_pages = ceil($total / $per_page);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;
    return [
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}
?>
