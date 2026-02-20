<?php
require_once 'config.php';
checkLogin();

// Get statistics
$stats = [];
$stats['total_assets'] = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];
$stats['available'] = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'available'")->fetch_assoc()['count'];
$stats['assigned'] = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'assigned'")->fetch_assoc()['count'];
$stats['maintenance'] = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'maintenance'")->fetch_assoc()['count'];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$stats['total_categories'] = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];

// License statistics
$stats['total_licenses'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status != 'cancelled'")->fetch_assoc()['count'] ?? 0;
$stats['expiring_licenses'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expiring_soon'")->fetch_assoc()['count'] ?? 0;

// Upcoming license renewals (next 60 days)
$upcoming_licenses = $conn->query("SELECT contract_name, vendor, due_date, amount, months_left,
                                   DATEDIFF(due_date, CURDATE()) as days_left
                                   FROM licenses 
                                   WHERE status IN ('active', 'expiring_soon')
                                   AND due_date >= CURDATE()
                                   ORDER BY due_date ASC 
                                   LIMIT 5");

// Recent assets
$recent_assets = $conn->query("SELECT a.*, c.name as category_name, u.full_name as created_by_name 
                               FROM assets a 
                               LEFT JOIN categories c ON a.category_id = c.id 
                               LEFT JOIN users u ON a.created_by = u.id 
                               ORDER BY a.created_at DESC LIMIT 5");

// Recent activities
$recent_activities = $conn->query("SELECT al.*, u.full_name 
                                   FROM activity_logs al 
                                   LEFT JOIN users u ON al.user_id = u.id 
                                   ORDER BY al.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard -<?php echo APP_NAME; ?> </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f173bff;
            --secondary-color: #ad87d3ff;
            --sidebar-width: 260px;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-menu {
            padding: 20px 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }
        .sidebar-menu i {
            width: 25px;
            margin-right: 12px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 0;
        }
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .content-area {
            padding: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .badge-custom {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h4>
            
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="assets.php">
                <i class="fas fa-box"></i> Assets
            </a>
            <a href="assignments.php">
                <i class="fas fa-exchange-alt"></i> Assignments
            </a>
            <a href="staff.php">
                <i class="fas fa-users"></i> Staff Directory
            </a>
            <a href="categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="manage_models.php">
                <i class="fas fa-cogs"></i> Asset Models
            </a>
            <a href="suppliers.php">
                <i class="fas fa-truck"></i> Suppliers
            </a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <?php endif; ?>
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="import.php">
                <i class="fas fa-file-import"></i> Import Assets
            </a>
            <a href="inspections.php">
                <i class="fas fa-clipboard-check"></i> Inspections
            </a>
            <a href="licenses.php">
                <i class="fas fa-certificate"></i> Licenses
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h5 class="mb-0">Dashboard</h5>
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-3"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></span>
                <i class="fas fa-bell text-muted me-3" style="font-size: 18px;"></i>
                <i class="fas fa-user-circle" style="font-size: 24px; color: var(--primary-color);"></i>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Assets</h6>
                                <h2 class="mb-0"><?php echo $stats['total_assets']; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Available</h6>
                                <h2 class="mb-0"><?php echo $stats['available']; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Assigned</h6>
                                <h2 class="mb-0"><?php echo $stats['assigned']; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Maintenance</h6>
                                <h2 class="mb-0"><?php echo $stats['maintenance']; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Overview Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Licenses</h6>
                                <h2 class="mb-0"><?php echo $stats['total_licenses']; ?></h2>
                                <small class="text-muted">Active contracts</small>
                            </div>
                            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                                <i class="fas fa-certificate"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Expiring Soon</h6>
                                <h2 class="mb-0 text-warning"><?php echo $stats['expiring_licenses']; ?></h2>
                                <small class="text-muted">Requires attention</small>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Assets & Activities -->
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-box me-2"></i>Recent Assets</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($asset = $recent_assets->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['category_name']); ?></td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'available' => 'success',
                                                    'assigned' => 'warning',
                                                    'maintenance' => 'danger',
                                                    'retired' => 'secondary'
                                                ];
                                                ?>
                                                <span class="badge badge-custom bg-<?php echo $badge_class[$asset['status']]; ?>">
                                                    <?php echo ucfirst($asset['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($asset['created_by_name']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center py-3">
                            <a href="assets.php" class="btn btn-sm btn-outline-primary">View All Assets</a>
                        </div>
                    </div>
                </div>

                <!-- License Renewal Timeline -->
                <div class="col-lg-4">
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Renewals</h5>
                            <a href="licenses.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if ($upcoming_licenses && $upcoming_licenses->num_rows > 0):
                                while ($lic = $upcoming_licenses->fetch_assoc()):
                                    $days = $lic['days_left'];
                                    $progress_class = $days <= 7 ? 'danger' : ($days <= 30 ? 'warning' : 'success');
                                    $percentage = max(0, min(100, (60 - $days) / 60 * 100));
                            ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div style="flex: 1;">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($lic['contract_name']); ?></h6>
                                        <?php if ($lic['vendor']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($lic['vendor']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?php echo $progress_class; ?> ms-2" style="white-space: nowrap;">
                                        <?php echo $days; ?> days
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-<?php echo $progress_class; ?>" 
                                         role="progressbar"
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d M Y', strtotime($lic['due_date'])); ?>
                                    </span>
                                    <?php if ($lic['amount']): ?>
                                    <span class="text-muted">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        <?php echo number_format($lic['amount'], 0); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="text-muted mb-0">No renewals due soon</p>
                                <small class="text-muted">All licenses current</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="d-flex mb-3 pb-3 border-bottom">
                                <div class="me-3">
                                    <i class="fas fa-circle" style="font-size: 8px; color: var(--primary-color);"></i>
                                </div>
                                <div>
                                    <p class="mb-1 small">
                                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>