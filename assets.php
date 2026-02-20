<?php
require_once 'config.php';
checkLogin();

// Handle Search and Filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$floor_filter = isset($_GET['floor']) ? sanitize($_GET['floor']) : '';

$where_clauses = [];
if ($search) {
    $where_clauses[] = "(a.asset_tag LIKE '%$search%' OR a.name LIKE '%$search%' OR a.serial_number LIKE '%$search%' OR a.model LIKE '%$search%')";
}
if ($category_filter) {
    $where_clauses[] = "a.category_id = '$category_filter'";
}
if ($status_filter) {
    $where_clauses[] = "a.status = '$status_filter'";
}
if ($floor_filter) {
    $where_clauses[] = "a.floor = '$floor_filter'";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get assets
$assets_query = "SELECT a.*, c.name as category_name, s.name as supplier_name, u.full_name as created_by_name 
                 FROM assets a 
                 LEFT JOIN categories c ON a.category_id = c.id 
                 LEFT JOIN suppliers s ON a.supplier_id = s.id 
                 LEFT JOIN users u ON a.created_by = u.id 
                 $where_sql 
                 ORDER BY a.created_at DESC";
$assets = $conn->query($assets_query);

// Get filters data
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$floors = $conn->query("SELECT DISTINCT floor FROM assets WHERE floor IS NOT NULL AND floor != '' ORDER BY floor");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
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
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            font-weight: 500;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .table-responsive {
            border-radius: 8px;
        }
        .badge-custom {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.1);
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
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="assets.php" class="active">
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
                <h5 class="mb-0">Assets Management</h5>
                <small class="text-muted">Manage and track all electronics assets</small>
            </div>
            <div class="d-flex align-items-center">
                <a href="asset_add.php" class="btn btn-primary-custom me-3">
                    <i class="fas fa-plus me-2"></i>Add New Asset
                </a>
                <span class="badge bg-primary me-3"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small"><i class="fas fa-search me-1"></i>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Asset tag, name, serial..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-tags me-1"></i>Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-info-circle me-1"></i>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="retired" <?php echo $status_filter == 'retired' ? 'selected' : ''; ?>>Retired</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-building me-1"></i>Floor</label>
                            <select name="floor" class="form-select">
                                <option value="">All Floors</option>
                                <?php while ($floor = $floors->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($floor['floor']); ?>" <?php echo $floor_filter == $floor['floor'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($floor['floor']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Assets Table -->
            <div class="card card-custom">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Assets List</h5>
                        <span class="badge bg-secondary"><?php echo $assets->num_rows; ?> Assets</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                  <!--  <th>Asset Tag</th>-->
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Purchase Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($assets->num_rows > 0): ?>
                                    <?php while ($asset = $assets->fetch_assoc()): ?>
                                    <tr>
                                      <!--  <td><strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong></td> -->
                                        <td>
                                            <div><?php echo htmlspecialchars($asset['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($asset['model']); ?></small>
                                        </td>
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
                                        <td>
                                            <div><?php echo htmlspecialchars($asset['location'] ?: 'N/A'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($asset['floor'] ?: ''); ?></small>
                                        </td>
                                        <td><?php echo $asset['purchase_date'] ? formatDate($asset['purchase_date']) : 'N/A'; ?></td>
                                        <td>
                                            <a href="asset_detail.php?id=<?php echo $asset['id']; ?>" class="action-btn bg-primary text-white" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (hasPermission('admin')): ?>
                                            <a href="asset_edit.php?id=<?php echo $asset['id']; ?>" class="action-btn bg-warning text-white" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No assets found matching your criteria
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