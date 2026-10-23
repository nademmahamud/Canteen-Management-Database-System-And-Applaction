<?php
/**
 * Campus Canteen Management System - Billing & Payment
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin() && !isCashier()) {
    setFlash('error', 'Access denied.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Billing';

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'process_payment') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        
        // Create payment record
        $payment_id = execute("INSERT INTO payments (order_id, amount, payment_method, transaction_id, payment_status) VALUES (?, ?, ?, ?, 'completed')",
            [$order_id, $amount, $payment_method, $transaction_id], "idss");
        
        if ($payment_id) {
            // Update order payment status
            execute("UPDATE orders SET payment_status = 'paid', status = 'completed' WHERE order_id = ?", [$order_id], "i");
            logActivity($_SESSION['user_id'], 'Create', "Processed payment for order #" . generateOrderId($order_id));
            setFlash('success', 'Payment processed successfully!');
        }
        
        header("Location: billing.php");
        exit();
    }
}

// Get unpaid orders
$unpaidOrders = fetchAll("SELECT o.*, u.full_name, u.email, u.phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.payment_status = 'unpaid' OR o.payment_status = 'pending'
    ORDER BY o.order_date DESC");

// Get recent payments
$recentPayments = fetchAll("SELECT p.*, o.order_id, u.full_name 
    FROM payments p 
    JOIN orders o ON p.order_id = o.order_id 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY p.payment_date DESC LIMIT 15");

// Get specific order for payment
$payOrder = null;
if (isset($_GET['order_id'])) {
    $payOrder = fetchOne("SELECT o.*, u.full_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?", [intval($_GET['order_id'])], "i");
}

require_once '../includes/header.php';
?>

<?php if ($payOrder): ?>
<!-- Process Payment for Specific Order -->
<div class="card" id="invoiceSection">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar" style="color: var(--primary); margin-right: 8px;"></i>Invoice</h3>
        <div class="card-actions">
            <button class="btn btn-outline btn-sm" onclick="printInvoice()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="invoice-header">
            <div class="invoice-logo">
                <div class="invoice-logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="invoice-logo-text">
                    <h2>CCMS</h2>
                    <p>Campus Canteen Management</p>
                </div>
            </div>
            <div class="invoice-meta">
                <h3><?php echo generateInvoiceId($payOrder['order_id']); ?></h3>
                <p><strong>Date:</strong> <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></p>
                <p><strong>Status:</strong> <span class="badge badge-warning">Unpaid</span></p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px;">
            <div class="invoice-section">
                <h4>Bill To</h4>
                <p><strong><?php echo htmlspecialchars($payOrder['full_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($payOrder['email']); ?></p>
                <p><?php echo htmlspecialchars($payOrder['phone'] ?? '-'); ?></p>
            </div>
            <div class="invoice-section">
                <h4>Order Info</h4>
                <p><strong>Order ID:</strong> <?php echo generateOrderId($payOrder['order_id']); ?></p>
                <p><strong>Order Date:</strong> <?php echo formatDateTime($payOrder['order_date']); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $payOrder['order_type'])); ?></p>
            </div>
        </div>
        
        <?php
        $orderItems = getOrderItems($payOrder['order_id']);
        ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $i => $item): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['food_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatPrice($item['unit_price']); ?></td>
                            <td><?php echo formatPrice($item['total_price']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="invoice-total" style="margin-top: 20px;">
            <h4>Total Amount</h4>
            <h2><?php echo formatPrice($payOrder['total_amount']); ?></h2>
        </div>
        
        <div style="border-top: 2px solid var(--gray-light); margin-top: 30px; padding-top: 30px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-credit-card" style="color: var(--primary); margin-right: 8px;"></i>Process Payment</h3>
            <form method="POST">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="order_id" value="<?php echo $payOrder['order_id']; ?>">
                <input type="hidden" name="amount" value="<?php echo $payOrder['total_amount']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Payment Method <span class="required">*</span></label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="digital">Digital Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Optional">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <a href="billing.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check"></i> Complete Payment - <?php echo formatPrice($payOrder['total_amount']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Billing Dashboard -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <span class="stat-title">Pending Payments</span>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-value"><?php echo count($unpaidOrders); ?></div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">Today's Revenue</span>
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
        <div class="stat-value"><?php echo formatPrice(getCount("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = CURDATE() AND payment_status = 'completed'")); ?></div>
    </div>
</div>

<div class="chart-row">
    <div class="card" style="flex: 2;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock" style="color: var(--warning); margin-right: 8px;"></i>Pending Payments</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($unpaidOrders)): ?>
            <div class="table-empty">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <p>No pending payments! All caught up.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unpaidOrders as $order): ?>
                        <tr>
                            <td><strong><?php echo generateOrderId($order['order_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                            <td><strong style="color: var(--primary);"><?php echo formatPrice($order['total_amount']); ?></strong></td>
                            <td><?php echo formatDateTime($order['order_date']); ?></td>
                            <td>
                                <a href="billing.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-credit-card"></i> Pay
                                </a>
                            </td>
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
            <h3 class="card-title"><i class="fas fa-history" style="color: var(--info); margin-right: 8px;"></i>Recent Payments</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $payment): ?>
                        <tr>
                            <td><?php echo generatePaymentId($payment['payment_id']); ?></td>
                            <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                            <td><strong><?php echo formatPrice($payment['amount']); ?></strong></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function printInvoice() {
    window.print();
}
</script>

<?php require_once '../includes/footer.php'; ?>
