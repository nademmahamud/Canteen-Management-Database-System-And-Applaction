<?php
/**
 * Campus Canteen Management System - Suppliers Management
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    setFlash('error', 'Access denied. Admin only.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Suppliers';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $supplier_id = $_POST['supplier_id'] ?? null;
        $supplier_name = sanitize($_POST['supplier_name'] ?? '');
        $contact_person = sanitize($_POST['contact_person'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        
        if (empty($supplier_name)) {
            setFlash('error', 'Supplier name is required.');
        } else {
            if ($action === 'add') {
                $result = execute("INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, status) VALUES (?, ?, ?, ?, ?, ?)",
                    [$supplier_name, $contact_person, $email, $phone, $address, $status], "ssssss");
                if ($result) {
                    logActivity($_SESSION['user_id'], 'Create', "Added supplier: $supplier_name");
                    setFlash('success', "Supplier '$supplier_name' added!");
                }
            } else {
                execute("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ? WHERE supplier_id = ?",
                    [$supplier_name, $contact_person, $email, $phone, $address, $status, $supplier_id], "ssssssi");
                logActivity($_SESSION['user_id'], 'Update', "Updated supplier: $supplier_name");
                setFlash('success', "Supplier '$supplier_name' updated!");
            }
        }
    } elseif ($action === 'delete') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $supplier = fetchOne("SELECT supplier_name FROM suppliers WHERE supplier_id = ?", [$supplier_id], "i");
        if ($supplier) {
            execute("DELETE FROM suppliers WHERE supplier_id = ?", [$supplier_id], "i");
            logActivity($_SESSION['user_id'], 'Delete', "Deleted supplier: {$supplier['supplier_name']}");
            setFlash('success', "Supplier '{$supplier['supplier_name']}' deleted!");
        }
    }
    
    header("Location: suppliers.php");
    exit();
}

$suppliers = getAllSuppliers();

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-truck" style="color: var(--primary); margin-right: 8px;"></i>Suppliers</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="openModal('addSupplierModal')">
                <i class="fas fa-plus"></i> Add Supplier
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table" id="suppliersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td>#<?php echo $supplier['supplier_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?></td>
                        <td><span class="badge <?php echo getStatusBadge($supplier['status']); ?>"><?php echo ucfirst($supplier['status']); ?></span></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-info btn-icon" onclick='editSupplier(<?php echo json_encode($supplier); ?>)' title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Delete this supplier?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
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

<!-- Add Supplier Modal -->
<div class="modal-overlay" id="addSupplierModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Supplier</h3>
            <button class="modal-close" onclick="closeModal('addSupplierModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Supplier Name <span class="required">*</span></label>
                    <input type="text" name="supplier_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSupplierModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal-overlay" id="editSupplierModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Supplier</h3>
            <button class="modal-close" onclick="closeModal('editSupplierModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="supplier_id" id="edit_sup_id">
                <div class="form-group">
                    <label class="form-label">Supplier Name <span class="required">*</span></label>
                    <input type="text" name="supplier_name" id="edit_sup_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" id="edit_sup_person" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_sup_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_sup_phone" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_sup_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_sup_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSupplierModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(supplier) {
    document.getElementById('edit_sup_id').value = supplier.supplier_id;
    document.getElementById('edit_sup_name').value = supplier.supplier_name;
    document.getElementById('edit_sup_person').value = supplier.contact_person || '';
    document.getElementById('edit_sup_email').value = supplier.email || '';
    document.getElementById('edit_sup_phone').value = supplier.phone || '';
    document.getElementById('edit_sup_address').value = supplier.address || '';
    document.getElementById('edit_sup_status').value = supplier.status;
    openModal('editSupplierModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>
