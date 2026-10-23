<?php
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
$menuItems = getSidebarMenu();
$unreadAlerts = 0;
if (isAdmin()) {
    $unreadAlerts = getCount("SELECT COUNT(*) FROM inventory WHERE status IN ('low_stock', 'out_of_stock')");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | CCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/CanteenManagement/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="preloader-content">
            <div class="preloader-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="preloader-text">CCMS</div>
            <div class="preloader-bar">
                <div class="preloader-progress"></div>
            </div>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-title">CCMS</span>
                    <span class="logo-subtitle">Canteen System</span>
                </div>
            </div>
            <button class="sidebar-close" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo getUserInitials($currentUser['full_name'] ?? 'User'); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></div>
                <div class="user-role">
                    <span class="badge <?php echo getStatusBadge($currentUser['role'] ?? 'student'); ?>">
                        <?php echo ucfirst($currentUser['role'] ?? 'User'); ?>
                    </span>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo $item['url']; ?>" class="nav-item <?php echo $item['active'] ? 'active' : ''; ?>">
                <div class="nav-icon">
                    <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                </div>
                <span class="nav-label"><?php echo $item['label']; ?></span>
                <?php if ($item['label'] === 'Inventory' && $unreadAlerts > 0): ?>
                <span class="nav-badge"><?php echo $unreadAlerts; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="/CanteenManagement/logout.php" class="nav-item logout-item">
                <div class="nav-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title-wrapper">
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <span class="page-date"><?php echo date('l, F d, Y'); ?></span>
                </div>
            </div>
            <div class="topbar-right">
                <!-- Search -->
                <div class="search-box" id="searchBox">
                    <input type="text" placeholder="Search..." class="search-input" id="globalSearch">
                    <button class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- Notifications -->
                <div class="notification-wrapper">
                    <button class="icon-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadAlerts > 0): ?>
                        <span class="notification-dot"></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <?php if ($unreadAlerts > 0): ?>
                            <span class="badge badge-warning"><?php echo $unreadAlerts; ?> alerts</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-body">
                            <?php if ($unreadAlerts > 0): ?>
                                <?php 
                                $alerts = fetchAll("SELECT * FROM inventory WHERE status IN ('low_stock', 'out_of_stock') LIMIT 5");
                                foreach ($alerts as $alert):
                                ?>
                                <div class="notification-item">
                                    <div class="notification-icon <?php echo $alert['status']; ?>">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p><?php echo htmlspecialchars($alert['item_name']); ?> is <?php echo str_replace('_', ' ', $alert['status']); ?></p>
                                        <span><?php echo $alert['current_stock']; ?> <?php echo $alert['unit']; ?> remaining</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-empty">
                                    <i class="fas fa-check-circle"></i>
                                    <p>All caught up! No alerts.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="/CanteenManagement/pages/inventory.php">View All Inventory</a>
                        </div>
                    </div>
                </div>

                <!-- Profile -->
                <div class="profile-wrapper">
                    <button class="profile-btn" onclick="toggleProfile()">
                        <div class="profile-avatar">
                            <?php echo getUserInitials($currentUser['full_name'] ?? 'User'); ?>
                        </div>
                        <span class="profile-name"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'] ?? 'User')[0]); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-header">
                            <div class="profile-avatar-large">
                                <?php echo getUserInitials($currentUser['full_name'] ?? 'User'); ?>
                            </div>
                            <div class="profile-info">
                                <h4><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></h4>
                                <span><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div class="profile-menu">
                            <a href="/CanteenManagement/pages/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="/CanteenManagement/logout.php" class="logout-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Flash Messages -->
            <div class="flash-container">
                <?php showFlash(); ?>
            </div>
