<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

// Handle license status updates (auto-update based on expiry date)
$conn->query("UPDATE licenses SET status = 'expired' WHERE renewal_date < CURDATE() AND status != 'cancelled'");
$conn->query("UPDATE licenses SET status = 'expiring_soon' WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL alert_days DAY) AND status = 'active'");

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT l.*, u.full_name as created_by_name,
          DATEDIFF(l.due_date, CURDATE()) as days_until_expiry
          FROM licenses l
          LEFT JOIN users u ON l.created_by = u.id
          WHERE 1=1";

if ($filter_status != 'all') {
    $query .= " AND l.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if ($filter_type != 'all') {
    $query .= " AND l.license_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (l.contract_name LIKE '%$search_term%' OR l.vendor LIKE '%$search_term%')";
}

$query .= " ORDER BY l.due_date ASC, l.contract_name ASC";
$licenses = $conn->query($query);

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status != 'cancelled'")->fetch_assoc()['count'];
$stats['active'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'active'")->fetch_assoc()['count'];
$stats['expiring_soon'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expiring_soon'")->fetch_assoc()['count'];
$stats['expired'] = $conn->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expired'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f173bff;
            --secondary: #ad87d3ff;
            --sidebar-width: 260px;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white; overflow-y: auto; z-index: 1000;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 25px;
            color: rgba(255,255,255,.8); text-decoration: none; transition: all .3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,.1); color: white; border-left: 4px solid white;
        }
        .sidebar-menu i { width: 25px; margin-right: 12px; }

        /* Layout */
        .main-content { margin-left: var(--sidebar-width); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .content-area { padding: 30px; }

        /* Cards */
        .stat-card {
            background: white; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05); transition: transform .3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }

        /* License status badges */
        .status-badge {
            padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-expiring_soon { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }

        /* Days until expiry indicator */
        .days-indicator {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 15px; font-size: .75rem; font-weight: 600;
        }
        .days-critical { background: #f8d7da; color: #721c24; }
        .days-warning { background: #fff3cd; color: #856404; }
        .days-good { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h5>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><i class="fas fa-home"></i>Dashboard</a>
        <a href="assets.php"><i class="fas fa-box"></i>Assets</a>
        <a href="assignments.php"><i class="fas fa-exchange-alt"></i>Assignments</a>
        <a href="inspections.php"><i class="fas fa-clipboard-check"></i>Inspections</a>
        <a href="licenses.php" class="active"><i class="fas fa-certificate"></i>Licenses</a>
        <a href="staff.php"><i class="fas fa-users"></i>Staff Directory</a>
        <a href="categories.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="manage_models.php"><i class="fas fa-cogs"></i>Asset Models</a>
        <a href="suppliers.php"><i class="fas fa-truck"></i>Suppliers</a>
        <?php if (hasPermission('admin')): ?>
        <a href="users.php"><i class="fas fa-user-shield"></i>Users</a>
        <?php endif; ?>
        <a href="reports.php"><i class="fas fa-chart-bar"></i>Reports</a>
        <hr style="border-color:rgba(255,255,255,.1)">
        <a href="profile.php"><i class="fas fa-user"></i>Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>License Management</h5>
            <small class="text-muted">Monitor and manage software licenses</small>
        </div>
        <div>
            <a href="license_import.php" class="btn btn-success btn-sm me-2">
                <i class="fas fa-file-import me-1"></i>Import from Excel
            </a>
            <a href="license_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add License
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Total Licenses</small>
                            <h3 class="mb-0 mt-1"><?php echo $stats['total']; ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(15,23,59,.1); color: var(--primary);">
                            <i class="fas fa-certificate"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Active</small>
                            <h3 class="mb-0 mt-1"><?php echo $stats['active']; ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(40,167,69,.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Expiring Soon</small>
                            <h3 class="mb-0 mt-1"><?php echo $stats['expiring_soon']; ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(255,193,7,.1); color: #ffc107;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Expired</small>
                            <h3 class="mb-0 mt-1"><?php echo $stats['expired']; ?></h3>
                        </div>
                        <div class="stat-icon" style="background: rgba(220,53,69,.1); color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>All Status</option>
                            <option value="active" <?php echo $filter_status=='active'?'selected':''; ?>>Active</option>
                            <option value="expiring_soon" <?php echo $filter_status=='expiring_soon'?'selected':''; ?>>Expiring Soon</option>
                            <option value="expired" <?php echo $filter_status=='expired'?'selected':''; ?>>Expired</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="all" <?php echo $filter_type=='all'?'selected':''; ?>>All Types</option>
                            <option value="software" <?php echo $filter_type=='software'?'selected':''; ?>>Software</option>
                            <option value="antivirus" <?php echo $filter_type=='antivirus'?'selected':''; ?>>Antivirus</option>
                            <option value="cloud_service" <?php echo $filter_type=='cloud_service'?'selected':''; ?>>Cloud Service</option>
                            <option value="domain" <?php echo $filter_type=='domain'?'selected':''; ?>>Domain</option>
                            <option value="ssl_certificate" <?php echo $filter_type=='ssl_certificate'?'selected':''; ?>>SSL Certificate</option>
                            <option value="other" <?php echo $filter_type=='other'?'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="License name or vendor..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Licenses Table -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Licenses (Sorted by Expiry Date)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Contract Name</th>
                                <th>Type</th>
                                <th>Due Date</th>
                                <th>Days Left</th>
                                <th>Months Left</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($licenses->num_rows > 0):
                                while ($lic = $licenses->fetch_assoc()):
                                    $days = $lic['days_until_expiry'];
                                    $days_class = $days < 0 ? 'days-critical' : ($days <= 30 ? 'days-warning' : 'days-good');
                                    $days_text = $days < 0 ? abs($days) . ' days ago' : $days . ' days';
                                    $months = $lic['months_left'] ?? round($days / 30);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($lic['contract_name']); ?></strong>
                                    <?php if ($lic['auto_renewal'] == 'yes'): ?>
                                    <i class="fas fa-sync-alt text-primary ms-1" title="Auto-renewal enabled"></i>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $lic['license_type'])); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($lic['due_date'])); ?></td>
                                <td>
                                    <span class="days-indicator <?php echo $days_class; ?>">
                                        <?php if ($days < 0): ?>
                                            <i class="fas fa-times"></i>
                                        <?php elseif ($days <= 7): ?>
                                            <i class="fas fa-exclamation-circle"></i>
                                        <?php elseif ($days <= 30): ?>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check"></i>
                                        <?php endif; ?>
                                        <?php echo $days_text; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php echo $months; ?> month<?php echo abs($months) != 1 ? 's' : ''; ?>
                                </td>
                                <td>
                                    <?php if ($lic['amount']): ?>
                                        $<?php echo number_format($lic['amount'], 2); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $lic['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $lic['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="license_view.php?id=<?php echo $lic['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="license_edit.php?id=<?php echo $lic['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No licenses found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>