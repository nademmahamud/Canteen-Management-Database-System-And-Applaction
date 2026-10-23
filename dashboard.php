<?php
/**
 * Campus Canteen Management System - Dashboard
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

$pageTitle = 'Dashboard';

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent orders
$recentOrders = getRecentOrders(8);

// Get top selling items
$topItems = getTopSellingItems(5);

// Get sales data for chart
$dailySales = getDailySales(7);
$salesLabels = array_column($dailySales, 'date');
$salesValues = array_column($dailySales, 'sales');
$orderValues = array_column($dailySales, 'orders');

// Get order status data
$orderStatusData = [
    'labels' => ['Completed', 'Processing', 'Pending', 'Cancelled'],
    'values' => [
        $stats['completed_orders'],
        $stats['processing_orders'],
        $stats['pending_orders'],
        $stats['cancelled_orders']
    ]
];

// Get sales by category
$salesByCategory = getSalesByCategory();
$categoryLabels = array_column($salesByCategory, 'category_name');
$categoryValues = array_column($salesByCategory, 'amount');

// Get payment distribution
$paymentDist = getPaymentDistribution();
$paymentLabels = array_column($paymentDist, 'payment_method');
$paymentValues = array_column($paymentDist, 'count');

// Get low stock items
$lowStockItems = fetchAll("SELECT * FROM inventory WHERE status IN ('low_stock', 'out_of_stock') LIMIT 5");

// Get activity logs
$activityLogs = fetchAll("SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 8");

require_once '../includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card primary animate-fade-in stagger-1">
        <div class="stat-header">
            <span class="stat-title">Total Orders</span>
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $stats['total_orders']; ?>"><?php echo number_format($stats['total_orders']); ?></div>
        <div class="stat-change up">
            <i class="fas fa-arrow-up"></i>
            <span>All time orders</span>
        </div>
    </div>
    
    <div class="stat-card success animate-fade-in stagger-2">
        <div class="stat-header">
            <span class="stat-title">Total Sales</span>
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $stats['total_sales']; ?>"><?php echo formatPrice($stats['total_sales']); ?></div>
        <div class="stat-change up">
            <i class="fas fa-arrow-up"></i>
            <span>Revenue generated</span>
        </div>
    </div>
    
    <div class="stat-card warning animate-fade-in stagger-3">
        <div class="stat-header">
            <span class="stat-title">Today's Orders</span>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $stats['todays_orders']; ?>"><?php echo number_format($stats['todays_orders']); ?></div>
        <div class="stat-change up">
            <i class="fas fa-calendar-day"></i>
            <span><?php echo date('M d, Y'); ?></span>
        </div>
    </div>
    
    <div class="stat-card info animate-fade-in stagger-4">
        <div class="stat-header">
            <span class="stat-title">Total Users</span>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $stats['total_users']; ?>"><?php echo number_format($stats['total_users']); ?></div>
        <div class="stat-change up">
            <i class="fas fa-user-plus"></i>
            <span>Active users</span>
        </div>
    </div>
</div>

<?php if (isAdmin() || isStaff()): ?>
<!-- Admin/Staff Dashboard -->
<div class="chart-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i>Sales Overview</h3>
            <div class="card-actions">
                <span class="badge badge-primary">This Week</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie" style="color: var(--info); margin-right: 8px;"></i>Orders by Status</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="chart-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-fire" style="color: var(--warning); margin-right: 8px;"></i>Top Selling Items</h3>
        </div>
        <div class="card-body">
            <div class="recent-orders-list">
                <?php foreach ($topItems as $index => $item): ?>
                <div class="recent-order-item">
                    <div class="order-avatar" style="background: linear-gradient(135deg, hsl(<?php echo ($index * 60); ?>, 70%, 50%), hsl(<?php echo ($index * 60 + 30); ?>, 70%, 40%));">
                        <?php echo $index + 1; ?>
                    </div>
                    <div class="order-info">
                        <h4><?php echo htmlspecialchars($item['food_name']); ?></h4>
                        <span><?php echo $item['total_sold']; ?> orders</span>
                    </div>
                    <div class="order-amount">
                        <i class="fas fa-crown" style="color: var(--warning); font-size: 12px;"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history" style="color: var(--success); margin-right: 8px;"></i>Recent Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong><?php echo generateOrderId($order['order_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><span class="badge <?php echo getStatusBadge($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- More charts for admin -->
<div class="chart-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar" style="color: var(--primary); margin-right: 8px;"></i>Sales by Category</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card" style="color: var(--info); margin-right: 8px;"></i>Payment Methods</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="paymentChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Alerts & Activity Logs -->
<div class="chart-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-exclamation-triangle" style="color: var(--warning); margin-right: 8px;"></i>Low Stock Alerts</h3>
            <a href="inventory.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($lowStockItems)): ?>
            <div class="table-empty">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <p>All items are well stocked!</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?></td>
                            <td><?php echo $item['reorder_level']; ?> <?php echo $item['unit']; ?></td>
                            <td><span class="badge <?php echo getStatusBadge($item['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-alt" style="color: var(--secondary-light); margin-right: 8px;"></i>Recent Activity</h3>
        </div>
        <div class="card-body">
            <div class="activity-list">
                <?php foreach ($activityLogs as $log): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo strtolower($log['action']); ?>">
                        <i class="fas fa-<?php 
                            echo match(strtolower($log['action'])) {
                                'login' => 'sign-in-alt',
                                'create' => 'plus',
                                'update' => 'edit',
                                'delete' => 'trash',
                                'order' => 'shopping-cart',
                                default => 'circle'
                            };
                        ?>"></i>
                    </div>
                    <div class="activity-content">
                        <p><?php echo htmlspecialchars($log['details'] ?? $log['action']); ?></p>
                        <span><?php echo timeAgo($log['created_at']); ?> by <?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Student/Customer Dashboard -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-utensils" style="color: var(--primary); margin-right: 8px;"></i>Welcome to Campus Canteen</h3>
    </div>
    <div class="card-body" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 80px; color: var(--primary); margin-bottom: 20px;">
            <i class="fas fa-utensils"></i>
        </div>
        <h2 style="font-size: 28px; margin-bottom: 10px; color: var(--secondary);">Order Your Favorite Food!</h2>
        <p style="color: var(--gray); font-size: 16px; max-width: 500px; margin: 0 auto 30px;">
            Browse our menu, place orders, track your order status, and enjoy delicious meals on campus.
        </p>
        <a href="orders.php" class="btn btn-primary btn-lg">
            <i class="fas fa-shopping-cart"></i>
            Place an Order
        </a>
    </div>
</div>

<div class="chart-row">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-fire" style="color: var(--warning); margin-right: 8px;"></i>Popular Items</h3>
        </div>
        <div class="card-body">
            <div class="menu-grid">
                <?php 
                $popularItems = fetchAll("SELECT f.*, c.category_name FROM foods f LEFT JOIN categories c ON f.category_id = c.category_id WHERE f.is_featured = 1 AND f.is_available = 1 LIMIT 4");
                foreach ($popularItems as $item): 
                ?>
                <div class="menu-card">
                    <?php $pimg = $item['image'] ?? 'default-food.jpg'; ?>
                    <div class="menu-card-image">
                        <img src="/CanteenManagement/assets/images/foods/<?php echo htmlspecialchars($pimg); ?>" alt="<?php echo htmlspecialchars($item['food_name']); ?>" style="width:100%; height:160px; object-fit:cover;">
                    </div>
                    <div class="menu-card-body">
                        <h4 class="menu-card-title"><?php echo htmlspecialchars($item['food_name']); ?></h4>
                        <p class="menu-card-category"><?php echo htmlspecialchars($item['category_name']); ?></p>
                        <div class="menu-card-footer">
                            <span class="menu-card-price"><?php echo formatPrice($item['price']); ?></span>
                            <span class="badge badge-success">Available</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Pass chart data to JavaScript
window.salesChartData = {
    labels: <?php echo json_encode($salesLabels); ?>,
    sales: <?php echo json_encode(array_map('floatval', $salesValues)); ?>
};

window.orderStatusData = {
    labels: <?php echo json_encode($orderStatusData['labels']); ?>,
    values: <?php echo json_encode($orderStatusData['values']); ?>
};

window.categoryChartData = {
    labels: <?php echo json_encode($categoryLabels); ?>,
    values: <?php echo json_encode(array_map('floatval', $categoryValues)); ?>
};

window.dailyOrdersData = {
    labels: <?php echo json_encode($salesLabels); ?>,
    values: <?php echo json_encode(array_map('intval', $orderValues)); ?>
};

window.paymentData = {
    labels: <?php echo json_encode($paymentLabels); ?>,
    values: <?php echo json_encode(array_map('intval', $paymentValues)); ?>
};
</script>

<?php require_once '../includes/footer.php'; ?>
