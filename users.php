<?php
/**
 * Campus Canteen Management System - Users Management
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only admin can access
if (!isAdmin()) {
    setFlash('error', 'Access denied. Admin only.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Users';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $user_id = $_POST['user_id'] ?? null;
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $role = sanitize($_POST['role'] ?? 'student');
        $status = sanitize($_POST['status'] ?? 'active');
        $password = $_POST['password'] ?? '';
        
        if (empty($full_name) || empty($email)) {
            setFlash('error', 'Full name and email are required.');
        } else {
            if ($action === 'add') {
                // Check if email exists
                $existing = fetchOne("SELECT * FROM users WHERE email = ?", [$email], "s");
                if ($existing) {
                    setFlash('error', 'Email already exists.');
                } else {
                    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : password_hash('password123', PASSWORD_DEFAULT);
                    $result = execute("INSERT INTO users (full_name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, ?)",
                        [$full_name, $email, $hashedPassword, $phone, $role, $status], "ssssss");
                    if ($result) {
                        logActivity($_SESSION['user_id'], 'Create', "Added new user: $full_name");
                        setFlash('success', "User '$full_name' added successfully!");
                    } else {
                        setFlash('error', 'Failed to add user.');
                    }
                }
            } else {
                // Update
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    execute("UPDATE users SET full_name = ?, email = ?, password = ?, phone = ?, role = ?, status = ? WHERE user_id = ?",
                        [$full_name, $email, $hashedPassword, $phone, $role, $status, $user_id], "ssssssi");
                } else {
                    execute("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?",
                        [$full_name, $email, $phone, $role, $status, $user_id], "sssssi");
                }
                logActivity($_SESSION['user_id'], 'Update', "Updated user: $full_name");
                setFlash('success', "User '$full_name' updated successfully!");
            }
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? 0;
        $user = fetchOne("SELECT full_name FROM users WHERE user_id = ?", [$user_id], "i");
        if ($user) {
            execute("DELETE FROM users WHERE user_id = ?", [$user_id], "i");
            logActivity($_SESSION['user_id'], 'Delete', "Deleted user: {$user['full_name']}");
            setFlash('success', "User '{$user['full_name']}' deleted successfully!");
        }
    }
    
    header("Location: users.php");
    exit();
}

// Get all users
$users = getAllUsers();

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users" style="color: var(--primary); margin-right: 8px;"></i>User Management</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filters-bar">
            <div class="search-filter">
                <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                <select class="form-control" id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="cashier">Cashier</option>
                    <option value="student">Student</option>
                </select>
                <select class="form-control" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">
                        <td>#<?php echo $user['user_id']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px;">
                                    <?php echo getUserInitials($user['full_name']); ?>
                                </div>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td><span class="badge <?php echo getStatusBadge($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td><span class="badge <?php echo getStatusBadge($user['status']); ?>"><?php echo ucfirst($user['status']); ?></span></td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-info btn-icon" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
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

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave empty for default">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="cashier">Cashier</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span class="required">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password (leave empty to keep)</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="cashier">Cashier</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span class="required">*</span></label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    openModal('editUserModal');
}

// Filter functionality
document.getElementById('userSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});

document.getElementById('roleFilter').addEventListener('change', filterUsers);
document.getElementById('statusFilter').addEventListener('change', filterUsers);

function filterUsers() {
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        const rowRole = row.dataset.role;
        const rowStatus = row.dataset.status;
        const showRole = !role || rowRole === role;
        const showStatus = !status || rowStatus === status;
        row.style.display = (showRole && showStatus) ? '' : 'none';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
