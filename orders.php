<?php
/**
 * Campus Canteen Management System - Orders Management
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

$pageTitle = 'Orders';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Place new order
        $user_id = $_SESSION['user_id'];
        $order_type = sanitize($_POST['order_type'] ?? 'dine_in');
        $notes = sanitize($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? [];
        
        if (empty($items)) {
            setFlash('error', 'Please add at least one item to the order.');
        } else {
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $total += floatval($item['total_price']);
            }
            
            // Create order
            $order_id = execute("INSERT INTO orders (user_id, total_amount, order_type, notes) VALUES (?, ?, ?, ?)",
                [$user_id, $total, $order_type, $notes], "idss");
            
            if ($order_id) {
                // Add order items
                foreach ($items as $item) {
                    execute("INSERT INTO order_items (order_id, food_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)",
                        [$order_id, $item['food_id'], $item['quantity'], $item['unit_price'], $item['total_price']], "iiidd");
                }
                
                logActivity($user_id, 'Order', "Placed order #" . generateOrderId($order_id));
                setFlash('success', 'Order placed successfully! Order ID: ' . generateOrderId($order_id));
            }
        }
    } elseif ($action === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        
        if ($order_id && $status) {
            execute("UPDATE orders SET status = ? WHERE order_id = ?", [$status, $order_id], "si");
            logActivity($_SESSION['user_id'], 'Update', "Updated order #" . generateOrderId($order_id) . " status to $status");
            setFlash('success', 'Order status updated to ' . ucfirst($status));
        }
    } elseif ($action === 'delete') {
        $order_id = intval($_POST['order_id'] ?? 0);
        execute("DELETE FROM orders WHERE order_id = ?", [$order_id], "i");
        logActivity($_SESSION['user_id'], 'Delete', "Deleted order #" . generateOrderId($order_id));
        setFlash('success', 'Order deleted successfully.');
    }
    
    header("Location: orders.php");
    exit();
}

// Get all orders or user's orders
if (isAdmin() || isStaff() || isCashier()) {
    $orders = getAllOrders();
} else {
    $orders = fetchAll("SELECT o.*, u.full_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC", [$_SESSION['user_id']], "i");
}

// Get foods for order creation
$foods = fetchAll("SELECT f.*, c.category_name FROM foods f LEFT JOIN categories c ON f.category_id = c.category_id WHERE f.is_available = 1 ORDER BY f.food_name");

require_once '../includes/header.php';
?>

<div class="tabs-container">
    <div class="tabs">
        <button class="tab-btn active" data-tab="ordersList"><i class="fas fa-list"></i> All Orders</button>
        <button class="tab-btn" data-tab="placeOrder"><i class="fas fa-plus-circle"></i> Place Order</button>
    </div>
    
    <!-- Orders List Tab -->
    <div class="tab-panel active" id="ordersList">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shopping-cart" style="color: var(--primary); margin-right: 8px;"></i>Orders</h3>
                <div class="search-filter">
                    <input type="text" class="form-control" id="orderSearch" placeholder="Search orders...">
                    <select class="form-control" id="orderStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="data-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <?php if (isAdmin() || isStaff() || isCashier()): ?>
                                <th>Customer</th>
                                <?php endif; ?>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr data-status="<?php echo $order['status']; ?>">
                                <td><strong><?php echo generateOrderId($order['order_id']); ?></strong></td>
                                <?php if (isAdmin() || isStaff() || isCashier()): ?>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo formatDateTime($order['order_date']); ?></td>
                                <td><strong style="color: var(--primary);"><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                    <td><strong style="color: var(--primary);"><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                <td><span class="badge <?php echo getStatusBadge($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><span class="badge <?php echo getStatusBadge($order['payment_status']); ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="order_view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info btn-icon" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isAdmin() || isStaff()): ?>
                                        <button class="btn btn-sm btn-warning btn-icon" onclick='updateStatus(<?php echo $order["order_id"]; ?>, "<?php echo $order["status"]; ?>")' title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (isAdmin()): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Delete this order?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Place Order Tab -->
    <div class="tab-panel" id="placeOrder">
        <form method="POST" id="orderForm">
            <input type="hidden" name="action" value="add">
            <div class="chart-row">
                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-utensils" style="color: var(--primary); margin-right: 8px;"></i>Select Items</h3>
                    </div>
                    <div class="card-body">
                        <div class="filters-bar">
                            <input type="text" class="form-control" id="itemSearch" placeholder="Search menu items...">
                            <select class="form-control" id="itemCategoryFilter">
                                <option value="">All Categories</option>
                                <?php 
                                $cats = fetchAll("SELECT * FROM categories ORDER BY category_name");
                                foreach ($cats as $cat): 
                                ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="menu-grid" id="itemsGrid" style="margin-top: 20px;">
                            <?php foreach ($foods as $food): ?>
                            <div class="menu-card item-card" data-id="<?php echo $food['food_id']; ?>" data-price="<?php echo $food['price']; ?>" data-name="<?php echo htmlspecialchars($food['food_name']); ?>" data-category="<?php echo $food['category_id']; ?>">
                                <?php $img = $food['image'] ?? 'default-food.jpg'; ?>
                                <div class="menu-card-image" style="height: 200px;">
                                    <img src="/CanteenManagement/assets/images/foods/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div class="menu-card-body" style="padding: 12px;">
                                    <h4 class="menu-card-title" style="font-size: 14px;"><?php echo htmlspecialchars($food['food_name']); ?></h4>
                                    <p class="menu-card-category"><?php echo htmlspecialchars($food['category_name']); ?></p>
                                    <div class="menu-card-footer">
                                        <span class="menu-card-price" style="font-size: 16px;"><?php echo formatPrice($food['price']); ?></span>
                                            <span class="menu-card-price" style="font-size: 16px;"><?php echo formatPrice($food['price']); ?></span>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addToOrder(<?php echo $food['food_id']; ?>, '<?php echo htmlspecialchars($food['food_name']); ?>', <?php echo $food['price']; ?>)">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="flex: 1;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-receipt" style="color: var(--primary); margin-right: 8px;"></i>Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div id="orderItems" style="max-height: 300px; overflow-y: auto;">
                            <div class="table-empty" id="emptyOrder">
                                <i class="fas fa-shopping-basket"></i>
                                <p>No items added yet</p>
                            </div>
                        </div>
                        
                        <div style="border-top: 2px solid var(--gray-light); margin-top: 15px; padding-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Subtotal:</span>
                                <strong id="subtotal">$0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 800;">
                                <span>Total:</span>
                                <span style="color: var(--primary);" id="grandTotal">$0.00</span>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label class="form-label">Order Type</label>
                            <select name="order_type" class="form-control">
                                <option value="dine_in">Dine In</option>
                                <option value="takeaway">Takeaway</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;" id="placeOrderBtn" disabled>
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Update Order Status</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="status_order_id">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" id="status_select">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
let orderItems = [];
let itemCounter = 0;

function addToOrder(foodId, foodName, price) {
    const existing = orderItems.find(item => item.food_id === foodId);
    if (existing) {
        existing.quantity++;
        existing.total_price = existing.quantity * existing.unit_price;
    } else {
        itemCounter++;
        orderItems.push({
            counter: itemCounter,
            food_id: foodId,
            food_name: foodName,
            unit_price: price,
            quantity: 1,
            total_price: price
        });
    }
    renderOrderItems();
}

function removeFromOrder(counter) {
    orderItems = orderItems.filter(item => item.counter !== counter);
    renderOrderItems();
}

function updateQuantity(counter, change) {
    const item = orderItems.find(item => item.counter === counter);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromOrder(counter);
            return;
        }
        item.total_price = item.quantity * item.unit_price;
    }
    renderOrderItems();
}

function renderOrderItems() {
    const container = document.getElementById('orderItems');
    
    if (orderItems.length === 0) {
        container.innerHTML = `
            <div class="table-empty" id="emptyOrder">
                <i class="fas fa-shopping-basket"></i>
                <p>No items added yet</p>
            </div>`;
        document.getElementById('placeOrderBtn').disabled = true;
    } else {
        let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
        let total = 0;
        
        orderItems.forEach(item => {
            total += item.total_price;
            html += `
                <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--light); border-radius: 8px;">
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.food_name}</div>
                        <div style="font-size: 12px; color: var(--gray);">$${item.unit_price.toFixed(2)} each</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="btn btn-sm btn-secondary btn-icon" style="width: 28px; height: 28px;" onclick="updateQuantity(${item.counter}, -1)">-</button>
                        <span style="font-weight: 700; min-width: 20px; text-align: center;">${item.quantity}</span>
                        <button type="button" class="btn btn-sm btn-secondary btn-icon" style="width: 28px; height: 28px;" onclick="updateQuantity(${item.counter}, 1)">+</button>
                    </div>
                    <div style="font-weight: 700; min-width: 60px; text-align: right;">$${item.total_price.toFixed(2)}</div>
                    <button type="button" class="btn btn-sm btn-danger btn-icon" style="width: 28px; height: 28px;" onclick="removeFromOrder(${item.counter})">
                        <i class="fas fa-times"></i>
                    </button>
                    <input type="hidden" name="items[${item.counter}][food_id]" value="${item.food_id}">
                    <input type="hidden" name="items[${item.counter}][quantity]" value="${item.quantity}">
                    <input type="hidden" name="items[${item.counter}][unit_price]" value="${item.unit_price}">
                    <input type="hidden" name="items[${item.counter}][total_price]" value="${item.total_price}">
                </div>`;
        });
        
        html += '</div>';
        container.innerHTML = html;
        document.getElementById('placeOrderBtn').disabled = false;
    }
    
    document.getElementById('subtotal').textContent = '¥' + total.toFixed(2);
    document.getElementById('grandTotal').textContent = '¥' + total.toFixed(2);
}

function updateStatus(orderId, currentStatus) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('status_select').value = currentStatus;
    openModal('statusModal');
}

document.getElementById('orderSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

document.getElementById('orderStatusFilter').addEventListener('change', function() {
    const status = this.value;
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        row.style.display = !status || row.dataset.status === status ? '' : 'none';
    });
});

document.getElementById('itemSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.item-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

document.getElementById('itemCategoryFilter').addEventListener('change', function() {
    const cat = this.value;
    document.querySelectorAll('.item-card').forEach(card => {
        card.style.display = !cat || card.dataset.category === cat ? '' : 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
