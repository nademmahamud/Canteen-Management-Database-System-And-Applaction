<?php
/**
 * Campus Canteen Management System - Reports & Analytics
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    setFlash('error', 'Access denied. Admin only.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Reports';

// Get report data
$totalRevenue = getCount("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'");
$totalOrders = getCount("SELECT COUNT(*) FROM orders");
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// Monthly sales data
$monthlySales = fetchAll("SELECT DATE_FORMAT(order_date, '%Y-%m') as month, COUNT(*) as orders, SUM(total_amount) as revenue
    FROM orders WHERE payment_status = 'paid' GROUP BY DATE_FORMAT(order_date, '%Y-%m') ORDER BY month DESC LIMIT 12");

// Top selling items
$topSelling = fetchAll("SELECT f.food_name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_revenue
    FROM order_items oi JOIN foods f ON oi.food_id = f.food_id GROUP BY oi.food_id ORDER BY total_qty DESC LIMIT 10");

// User registrations by month
$userGrowth = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 12");

// Payment method summary
$paymentSummary = fetchAll("SELECT payment_method, COUNT(*) as count, SUM(amount) as total
    FROM payments WHERE payment_status = 'completed' GROUP BY payment_method");

require_once '../includes/header.php';
?>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <span class="stat-title">Total Revenue</span>
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
        <div class="stat-value"><?php echo formatPrice($totalRevenue); ?></div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">Total Orders</span>
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
        <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
    </div>
    <div class="stat-card info">
        <div class="stat-header">
            <span class="stat-title">Avg Order Value</span>
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        </div>
        <div class="stat-value"><?php echo formatPrice($avgOrderValue); ?></div>
    </div>
</div>

<div class="chart-row">
    <!-- Monthly Sales -->
    <div class="card" style="flex: 2;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar" style="color: var(--primary); margin-right: 8px;"></i>Monthly Revenue</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Payment Summary -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card" style="color: var(--info); margin-right: 8px;"></i>Payment Methods</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="paymentSummaryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="chart-row">
    <!-- Top Selling Items -->
    <div class="card" style="flex: 2;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-fire" style="color: var(--warning); margin-right: 8px;"></i>Top Selling Items</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSelling as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['food_name']); ?></strong></td>
                            <td><?php echo number_format($item['total_qty']); ?></td>
                            <td><strong style="color: var(--primary);"><?php echo formatPrice($item['total_revenue']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- User Growth -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users" style="color: var(--success); margin-right: 8px;"></i>User Growth</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>New Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userGrowth as $ug): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($ug['month'] . '-01')); ?></td>
                            <td><strong><?php echo $ug['count']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt" style="color: var(--primary); margin-right: 8px;"></i>Monthly Sales Report</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Orders</th>
                        <th>Revenue</th>
                        <th>Average Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlySales as $ms): 
                        $avg = $ms['orders'] > 0 ? $ms['revenue'] / $ms['orders'] : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo date('F Y', strtotime($ms['month'] . '-01')); ?></strong></td>
                        <td><?php echo number_format($ms['orders']); ?></td>
                        <td><strong style="color: var(--primary);"><?php echo formatPrice($ms['revenue']); ?></strong></td>
                        <td><?php echo formatPrice($avg); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Monthly Sales Chart
const monthlySalesCtx = document.getElementById('monthlySalesChart');
if (monthlySalesCtx) {
    new Chart(monthlySalesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(fn($m) => date('M Y', strtotime($m['month'] . '-01')), array_reverse($monthlySales))); ?>,
                datasets: [{
                label: 'Revenue (¥)',
                data: <?php echo json_encode(array_map('floatval', array_column(array_reverse($monthlySales), 'revenue'))); ?>,
                backgroundColor: 'rgba(230, 57, 70, 0.8)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
}

// Payment Summary Chart
const paymentCtx = document.getElementById('paymentSummaryChart');
if (paymentCtx) {
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', array_column($paymentSummary, 'payment_method'))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map('intval', array_column($paymentSummary, 'count'))); ?>,
                backgroundColor: ['#3498db', '#2ecc71', '#f39c12'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } }
            }
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
