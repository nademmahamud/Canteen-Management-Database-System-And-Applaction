<?php
/**
 * Campus Canteen Management System - Settings & Profile
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

$pageTitle = 'Settings';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            setFlash('error', 'Full name and email are required.');
        } else {
            // Check if email exists for another user
            $existing = fetchOne("SELECT * FROM users WHERE email = ? AND user_id != ?", [$email, $_SESSION['user_id']], "si");
            if ($existing) {
                setFlash('error', 'Email already in use by another account.');
            } else {
                execute("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?",
                    [$full_name, $email, $phone, $_SESSION['user_id']], "sssi");
                
                // Update session
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                
                logActivity($_SESSION['user_id'], 'Update', 'Updated profile information');
                setFlash('success', 'Profile updated successfully!');
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlash('error', 'All password fields are required.');
        } elseif ($new_password !== $confirm_password) {
            setFlash('error', 'New passwords do not match.');
        } elseif (strlen($new_password) < 4) {
            setFlash('error', 'Password must be at least 4 characters.');
        } else {
            // Verify current password
            $user = fetchOne("SELECT password FROM users WHERE user_id = ?", [$_SESSION['user_id']], "i");
            if ($user && password_verify($current_password, $user['password'])) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                execute("UPDATE users SET password = ? WHERE user_id = ?", [$hashed, $_SESSION['user_id']], "si");
                logActivity($_SESSION['user_id'], 'Update', 'Changed password');
                setFlash('success', 'Password changed successfully!');
            } else {
                setFlash('error', 'Current password is incorrect.');
            }
        }
    }
    
    header("Location: settings.php");
    exit();
}

// Get current user details
$user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']], "i");

require_once '../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <!-- Profile Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-circle" style="color: var(--primary); margin-right: 8px;"></i>Profile Settings</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid var(--gray-light);">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: 700;">
                    <?php echo getUserInitials($user['full_name']); ?>
                </div>
                <div>
                    <h2 style="font-size: 24px; font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p style="color: var(--gray);">
                        <span class="badge <?php echo getStatusBadge($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span>
                        <span class="badge <?php echo getStatusBadge($user['status']); ?>"><?php echo ucfirst($user['status']); ?></span>
                    </p>
                    <p style="font-size: 12px; color: var(--gray);">Member since <?php echo formatDate($user['created_at']); ?></p>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock" style="color: var(--primary); margin-right: 8px;"></i>Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Current Password <span class="required">*</span></label>
                    <div class="input-wrapper" style="position: relative;">
                        <input type="password" name="current_password" class="form-control" required style="padding-right: 40px;">
                        <span class="password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray);">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password <span class="required">*</span></label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle" style="color: var(--info); margin-right: 8px;"></i>System Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="padding: 16px; background: var(--light); border-radius: var(--radius-sm);">
                    <p style="font-size: 12px; color: var(--gray); margin-bottom: 4px;">System Name</p>
                    <p style="font-weight: 700; color: var(--secondary);">CCMS</p>
                </div>
                <div style="padding: 16px; background: var(--light); border-radius: var(--radius-sm);">
                    <p style="font-size: 12px; color: var(--gray); margin-bottom: 4px;">Version</p>
                    <p style="font-weight: 700; color: var(--secondary);">1.0.0</p>
                </div>
                <div style="padding: 16px; background: var(--light); border-radius: var(--radius-sm);">
                    <p style="font-size: 12px; color: var(--gray); margin-bottom: 4px;">Database</p>
                    <p style="font-weight: 700; color: var(--secondary);">MySQL 8.0</p>
                </div>
                <div style="padding: 16px; background: var(--light); border-radius: var(--radius-sm);">
                    <p style="font-size: 12px; color: var(--gray); margin-bottom: 4px;">Server</p>
                    <p style="font-weight: 700; color: var(--secondary);">Apache (XAMPP)</p>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center; padding: 20px; background: linear-gradient(135deg, var(--secondary), var(--secondary-light)); border-radius: var(--radius); color: white;">
                <h4 style="margin-bottom: 10px;"><i class="fas fa-utensils" style="margin-right: 8px;"></i>Campus Canteen Management System</h4>
                <p style="opacity: 0.9; font-size: 14px;">A Smart Solution for Modern Canteen Operations</p>
                <p style="opacity: 0.7; font-size: 12px; margin-top: 10px;">Built by: Abrar Abdullah, Sagor Anup Chandra, Mahamud Nadem, Bukhari Syed Ghufran, Kasmi Manal</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
