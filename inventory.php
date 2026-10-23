<?php
/**
 * Campus Canteen Management System - Inventory Management
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin() && !isStaff()) {
    setFlash('error', 'Access denied.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Inventory';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $inventory_id = $_POST['inventory_id'] ?? null;
        $item_name = sanitize($_POST['item_name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $unit = sanitize($_POST['unit'] ?? '');
        $stock_in = floatval($_POST['stock_in'] ?? 0);
        $stock_out = floatval($_POST['stock_out'] ?? 0);
        $reorder_level = floatval($_POST['reorder_level'] ?? 10);
        $current_stock = $stock_in - $stock_out;
        
        // Determine status
        if ($current_stock <= 0) {
            $status = 'out_of_stock';
        } elseif ($current_stock <= $reorder_level) {
            $status = 'low_stock';
        } else {
            $status = 'in_stock';
        }
        
        if (empty($item_name) || empty($unit)) {
            setFlash('error', 'Item name and unit are required.');
        } else {
            if ($action === 'add') {
                $result = execute("INSERT INTO inventory (item_name, category, unit, stock_in, stock_out, current_stock, reorder_level, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$item_name, $category, $unit, $stock_in, $stock_out, $current_stock, $reorder_level, $status], "sssddddds");
                if ($result) {
                    logActivity($_SESSION['user_id'], 'Create', "Added inventory item: $item_name");
                    setFlash('success', "Item '$item_name' added to inventory!");
                }
            } else {
                execute("UPDATE inventory SET item_name = ?, category = ?, unit = ?, stock_in = ?, stock_out = ?, current_stock = ?, reorder_level = ?, status = ? WHERE inventory_id = ?",
                    [$item_name, $category, $unit, $stock_in, $stock_out, $current_stock, $reorder_level, $status, $inventory_id], "sssdddddsi");
                logActivity($_SESSION['user_id'], 'Update', "Updated inventory item: $item_name");
                setFlash('success', "Item '$item_name' updated!");
            }
        }
    } elseif ($action === 'delete') {
        $inventory_id = intval($_POST['inventory_id'] ?? 0);
        $item = fetchOne("SELECT item_name FROM inventory WHERE inventory_id = ?", [$inventory_id], "i");
        if ($item) {
            execute("DELETE FROM inventory WHERE inventory_id = ?", [$inventory_id], "i");
            logActivity($_SESSION['user_id'], 'Delete', "Deleted inventory item: {$item['item_name']}");
            setFlash('success', "Item '{$item['item_name']}' deleted!");
        }
    } elseif ($action === 'adjust_stock') {
        $inventory_id = intval($_POST['inventory_id'] ?? 0);
        $adjustment = floatval($_POST['adjustment'] ?? 0);
        $adjustment_type = sanitize($_POST['adjustment_type'] ?? 'add');
        
        $item = fetchOne("SELECT * FROM inventory WHERE inventory_id = ?", [$inventory_id], "i");
        if ($item) {
            $new_stock_in = floatval($item['stock_in']);
            $new_stock_out = floatval($item['stock_out']);
            
            if ($adjustment_type === 'add') {
                $new_stock_in += $adjustment;
            } else {
                $new_stock_out += $adjustment;
            }
            
            $current_stock = $new_stock_in - $new_stock_out;
            $reorder_level = floatval($item['reorder_level']);
            
            if ($current_stock <= 0) {
                $status = 'out_of_stock';
            } elseif ($current_stock <= $reorder_level) {
                $status = 'low_stock';
            } else {
                $status = 'in_stock';
            }
            
            execute("UPDATE inventory SET stock_in = ?, stock_out = ?, current_stock = ?, status = ? WHERE inventory_id = ?",
                [$new_stock_in, $new_stock_out, $current_stock, $status, $inventory_id], "ddddsi");
            logActivity($_SESSION['user_id'], 'Update', "Adjusted stock for: {$item['item_name']}");
            setFlash('success', "Stock adjusted for '{$item['item_name']}'!");
        }
    }
    
    header("Location: inventory.php");
    exit();
}

// Get inventory stats
$totalItems = getCount("SELECT COUNT(*) FROM inventory");
$inStock = getCount("SELECT COUNT(*) FROM inventory WHERE status = 'in_stock'");
$lowStock = getCount("SELECT COUNT(*) FROM inventory WHERE status = 'low_stock'");
$outOfStock = getCount("SELECT COUNT(*) FROM inventory WHERE status = 'out_of_stock'");

// Get all inventory items
$inventory = getAllInventory();

// Get inventory data for chart
$chartData = fetchAll("SELECT item_name, stock_in, stock_out, current_stock FROM inventory ORDER BY current_stock DESC LIMIT 10");

require_once '../includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <span class="stat-title">Total Items</span>
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $totalItems; ?>"><?php echo $totalItems; ?></div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">In Stock</span>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $inStock; ?>"><?php echo $inStock; ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-header">
            <span class="stat-title">Low Stock</span>
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $lowStock; ?>"><?php echo $lowStock; ?></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-header">
            <span class="stat-title">Out of Stock</span>
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
        </div>
        <div class="stat-value" data-counter="<?php echo $outOfStock; ?>"><?php echo $outOfStock; ?></div>
    </div>
</div>

<div class="chart-row">
    <div class="card" style="flex: 2;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes" style="color: var(--primary); margin-right: 8px;"></i>Inventory List</h3>
            <div class="card-actions">
                <button class="btn btn-primary btn-sm" onclick="openModal('addInventoryModal')">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="filters-bar">
                <input type="text" class="form-control" id="inventorySearch" placeholder="Search items...">
                <select class="form-control" id="inventoryStatusFilter">
                    <option value="">All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Stock In</th>
                            <th>Stock Out</th>
                            <th>Current</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                        <tr data-status="<?php echo $item['status']; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                            <td><?php echo $item['stock_in']; ?> <?php echo $item['unit']; ?></td>
                            <td><?php echo $item['stock_out']; ?> <?php echo $item['unit']; ?></td>
                            <td><strong><?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?></strong></td>
                            <td><span class="badge <?php echo getStatusBadge($item['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-success btn-icon" onclick='adjustStock(<?php echo json_encode($item); ?>)' title="Adjust Stock">
                                        <i class="fas fa-balance-scale"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info btn-icon" onclick='editInventory(<?php echo json_encode($item); ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Delete this item?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="inventory_id" value="<?php echo $item['inventory_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar" style="color: var(--info); margin-right: 8px;"></i>Stock Overview</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="inventoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Add Inventory Modal -->
<div class="modal-overlay" id="addInventoryModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Inventory Item</h3>
            <button class="modal-close" onclick="closeModal('addInventoryModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Name <span class="required">*</span></label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Raw Material">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Unit <span class="required">*</span></label>
                        <input type="text" name="unit" class="form-control" placeholder="kg, L, pcs" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock In</label>
                        <input type="number" name="stock_in" class="form-control" step="0.01" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stock Out</label>
                        <input type="number" name="stock_out" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" class="form-control" step="0.01" value="10">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addInventoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div class="modal-overlay" id="editInventoryModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Inventory Item</h3>
            <button class="modal-close" onclick="closeModal('editInventoryModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="inventory_id" id="edit_inv_id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Name <span class="required">*</span></label>
                        <input type="text" name="item_name" id="edit_inv_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" id="edit_inv_category" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Unit <span class="required">*</span></label>
                        <input type="text" name="unit" id="edit_inv_unit" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock In</label>
                        <input type="number" name="stock_in" id="edit_inv_stock_in" class="form-control" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Stock Out</label>
                        <input type="number" name="stock_out" id="edit_inv_stock_out" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" id="edit_inv_reorder" class="form-control" step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInventoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal-overlay" id="adjustStockModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Adjust Stock</h3>
            <button class="modal-close" onclick="closeModal('adjustStockModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="inventory_id" id="adjust_inv_id">
                <p style="margin-bottom: 15px;">Adjusting stock for: <strong id="adjust_inv_name"></strong></p>
                <div class="form-group">
                    <label class="form-label">Adjustment Type</label>
                    <select name="adjustment_type" class="form-control">
                        <option value="add">Stock In (Add)</option>
                        <option value="remove">Stock Out (Remove)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" name="adjustment" class="form-control" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('adjustStockModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
window.inventoryChartData = {
    labels: <?php echo json_encode(array_column($chartData, 'item_name')); ?>,
    stockIn: <?php echo json_encode(array_map('floatval', array_column($chartData, 'stock_in'))); ?>,
    stockOut: <?php echo json_encode(array_map('floatval', array_column($chartData, 'stock_out'))); ?>
};

function editInventory(item) {
    document.getElementById('edit_inv_id').value = item.inventory_id;
    document.getElementById('edit_inv_name').value = item.item_name;
    document.getElementById('edit_inv_category').value = item.category || '';
    document.getElementById('edit_inv_unit').value = item.unit;
    document.getElementById('edit_inv_stock_in').value = item.stock_in;
    document.getElementById('edit_inv_stock_out').value = item.stock_out;
    document.getElementById('edit_inv_reorder').value = item.reorder_level;
    openModal('editInventoryModal');
}

function adjustStock(item) {
    document.getElementById('adjust_inv_id').value = item.inventory_id;
    document.getElementById('adjust_inv_name').textContent = item.item_name;
    openModal('adjustStockModal');
}

document.getElementById('inventorySearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

document.getElementById('inventoryStatusFilter').addEventListener('change', function() {
    const status = this.value;
    document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
        row.style.display = !status || row.dataset.status === status ? '' : 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
