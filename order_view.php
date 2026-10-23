<?php
/**
 * Campus Canteen Management System - Order View
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

$pageTitle = 'Order Details';

$order_id = intval($_GET['id'] ?? 0);
$order = getOrderDetails($order_id);

if (!$order) {
    setFlash('error', 'Order not found.');
    header("Location: orders.php");
    exit();
}

$orderItems = getOrderItems($order_id);
$payment = fetchOne("SELECT * FROM payments WHERE order_id = ?", [$order_id], "i");

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-receipt" style="color: var(--primary); margin-right: 8px;"></i>
            Order <?php echo generateOrderId($order['order_id']); ?>
        </h3>
        <div class="card-actions">
            <button class="btn btn-outline btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="orders.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="invoice-section">
                <h4>Customer Info</h4>
                <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
                <p><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></p>
            </div>
            <div class="invoice-section">
                <h4>Order Info</h4>
                <p><strong>Date:</strong> <?php echo formatDateTime($order['order_date']); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></p>
                <p><strong>Status:</strong> <span class="badge <?php echo getStatusBadge($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></p>
            </div>
            <div class="invoice-section">
                <h4>Payment Info</h4>
                <p><strong>Status:</strong> <span class="badge <?php echo getStatusBadge($order['payment_status']); ?>"><?php echo ucfirst($order['payment_status']); ?></span></p>
                <?php if ($payment): ?>
                <p><strong>Method:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
                <p><strong>Transaction:</strong> <?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($order['notes'])): ?>
            <div class="invoice-section">
                <h4>Notes</h4>
                <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['food_name']); ?></strong></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatPrice($item['unit_price']); ?></td>
                        <td><strong><?php echo formatPrice($item['total_price']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: var(--light); font-size: 18px;">
                        <td colspan="4" style="text-align: right; font-weight: 700;">Grand Total:</td>
                        <td style="font-weight: 800; color: var(--primary);"><?php echo formatPrice($order['total_amount']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if (isAdmin() || isCashier()): ?>
        <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
            <?php if ($order['payment_status'] !== 'paid'): ?>
            <a href="billing.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                <i class="fas fa-credit-card"></i> Process Payment
            </a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
            <form method="POST" action="orders.php" style="display: inline;" onsubmit="return confirmDelete('Delete this order?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Order
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
