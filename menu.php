<?php
/**
 * Campus Canteen Management System - Menu Items Management
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isStaff()) {
    setFlash('error', 'Access denied.');
    header("Location: dashboard.php");
    exit();
}

$pageTitle = 'Menu Items';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $food_id = $_POST['food_id'] ?? null;
        $food_name = sanitize($_POST['food_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        // Image upload handling
        $imageName = null;
        if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK && in_array($_FILES['image']['type'], $allowed)) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $uploadDir = __DIR__ . '/../assets/images/foods/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $target = $uploadDir . $imageName;
                move_uploaded_file($_FILES['image']['tmp_name'], $target);
            } else {
                setFlash('error', 'Invalid image file. Allowed: JPG, PNG, GIF, WEBP');
            }
        }
        
        if (empty($food_name) || $category_id === 0 || $price <= 0) {
            setFlash('error', 'Please fill in all required fields.');
        } else {
            if ($action === 'add') {
                $imageToSave = $imageName ?: 'default-food.jpg';
                $result = execute("INSERT INTO foods (food_name, description, category_id, price, image, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$food_name, $description, $category_id, $price, $imageToSave, $is_available, $is_featured], "ssidsii");
                if ($result) {
                    logActivity($_SESSION['user_id'], 'Create', "Added menu item: $food_name");
                    setFlash('success', "Menu item '$food_name' added successfully!");
                }
            } else {
                // Preserve existing image unless a new one was uploaded
                $existing = fetchOne("SELECT image FROM foods WHERE food_id = ?", [$food_id], "i");
                $imageToSave = $imageName ?: ($existing['image'] ?? 'default-food.jpg');
                // If new image uploaded and existing image is not default, delete old file
                if ($imageName && !empty($existing['image']) && $existing['image'] !== 'default-food.jpg') {
                    $oldPath = __DIR__ . '/../assets/images/foods/' . $existing['image'];
                    if (file_exists($oldPath)) @unlink($oldPath);
                }

                execute("UPDATE foods SET food_name = ?, description = ?, category_id = ?, price = ?, image = ?, is_available = ?, is_featured = ? WHERE food_id = ?",
                    [$food_name, $description, $category_id, $price, $imageToSave, $is_available, $is_featured, $food_id], "ssidsiii");
                logActivity($_SESSION['user_id'], 'Update', "Updated menu item: $food_name");
                setFlash('success', "Menu item '$food_name' updated successfully!");
            }
        }
    } elseif ($action === 'delete') {
        $food_id = $_POST['food_id'] ?? 0;
        $food = fetchOne("SELECT food_name FROM foods WHERE food_id = ?", [$food_id], "i");
        if ($food) {
            execute("DELETE FROM foods WHERE food_id = ?", [$food_id], "i");
            logActivity($_SESSION['user_id'], 'Delete', "Deleted menu item: {$food['food_name']}");
            setFlash('success', "Menu item '{$food['food_name']}' deleted!");
        }
    }
    
    header("Location: menu.php");
    exit();
}

// Get all foods with categories
$foods = fetchAll("SELECT f.*, c.category_name FROM foods f LEFT JOIN categories c ON f.category_id = c.category_id ORDER BY f.created_at DESC");
$categories = fetchAll("SELECT * FROM categories ORDER BY category_name");

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-utensils" style="color: var(--primary); margin-right: 8px;"></i>Menu Items</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="openModal('addFoodModal')">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filters-bar">
            <div class="search-filter">
                <input type="text" class="form-control" id="foodSearch" placeholder="Search items...">
                <select class="form-control" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" id="availabilityFilter">
                    <option value="">All</option>
                    <option value="1">Available</option>
                    <option value="0">Unavailable</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="data-table" id="foodsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($foods as $food): ?>
                    <tr data-category="<?php echo $food['category_id']; ?>" data-available="<?php echo $food['is_available']; ?>">
                        <td>
                            <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <?php $img = $food['image'] ?? 'default-food.jpg'; ?>
                                <img src="/CanteenManagement/assets/images/foods/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($food['food_name']); ?></strong>
                            <p style="font-size: 12px; color: var(--gray); margin: 0;"><?php echo htmlspecialchars(substr($food['description'] ?? '', 0, 50)) . '...'; ?></p>
                        </td>
                        <td><?php echo htmlspecialchars($food['category_name'] ?? 'N/A'); ?></td>
                        <td><strong style="color: var(--primary);"><?php echo formatPrice($food['price']); ?></strong></td>
                            <td><strong style="color: var(--primary);"><?php echo formatPrice($food['price']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $food['is_available'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $food['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($food['is_featured']): ?>
                            <span class="badge badge-warning"><i class="fas fa-star"></i> Featured</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-info btn-icon" onclick='editFood(<?php echo json_encode($food); ?>)' title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Delete this menu item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
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

<!-- Add Food Modal -->
<div class="modal-overlay" id="addFoodModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Menu Item</h3>
            <button class="modal-close" onclick="closeModal('addFoodModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Food Name <span class="required">*</span></label>
                    <input type="text" name="food_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" accept="image/*" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category <span class="required">*</span></label>
                        <select name="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) <span class="required">*</span></label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_available" value="1" checked> Available
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_featured" value="1"> Featured
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addFoodModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Food Modal -->
<div class="modal-overlay" id="editFoodModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Menu Item</h3>
            <button class="modal-close" onclick="closeModal('editFoodModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="food_id" id="edit_food_id">
                <div class="form-group">
                    <label class="form-label">Food Name <span class="required">*</span></label>
                    <input type="text" name="food_name" id="edit_food_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Image (leave empty to keep existing)</label>
                    <input type="file" name="image" accept="image/*" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category <span class="required">*</span></label>
                        <select name="category_id" id="edit_category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) <span class="required">*</span></label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_available" id="edit_is_available" value="1"> Available
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_featured" id="edit_is_featured" value="1"> Featured
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editFoodModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFood(food) {
    document.getElementById('edit_food_id').value = food.food_id;
    document.getElementById('edit_food_name').value = food.food_name;
    document.getElementById('edit_description').value = food.description || '';
    document.getElementById('edit_category_id').value = food.category_id;
    document.getElementById('edit_price').value = food.price;
    document.getElementById('edit_is_available').checked = food.is_available == 1;
    document.getElementById('edit_is_featured').checked = food.is_featured == 1;
    openModal('editFoodModal');
}

document.getElementById('foodSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#foodsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

document.getElementById('categoryFilter').addEventListener('change', filterFoods);
document.getElementById('availabilityFilter').addEventListener('change', filterFoods);

function filterFoods() {
    const cat = document.getElementById('categoryFilter').value;
    const avail = document.getElementById('availabilityFilter').value;
    
    document.querySelectorAll('#foodsTable tbody tr').forEach(row => {
        const rowCat = row.dataset.category;
        const rowAvail = row.dataset.available;
        const showCat = !cat || rowCat === cat;
        const showAvail = !avail || rowAvail === avail;
        row.style.display = (showCat && showAvail) ? '' : 'none';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
