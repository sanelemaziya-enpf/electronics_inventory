<?php
require_once 'config.php';
checkLogin();

// Get current quarter and year
$current_month = date('n');
$current_quarter = 'Q' . ceil($current_month / 3);
$current_year = date('Y');

// Handle filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$quarter_filter = isset($_GET['quarter']) ? sanitize($_GET['quarter']) : '';
$year_filter = isset($_GET['year']) ? sanitize($_GET['year']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where_clauses = [];
if ($search) {
    $where_clauses[] = "(a.asset_tag LIKE '%$search%' OR a.name LIKE '%$search%' OR i.inspector_username LIKE '%$search%')";
}
if ($quarter_filter) {
    $where_clauses[] = "i.inspection_quarter = '$quarter_filter'";
}
if ($year_filter) {
    $where_clauses[] = "i.inspection_year = '$year_filter'";
}
if ($status_filter) {
    $where_clauses[] = "i.status = '$status_filter'";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get inspections
$inspections_query = "SELECT i.*, a.asset_tag, a.name as asset_name, c.name as category_name,
                      u.full_name as inspector_name
                      FROM asset_inspections i
                      INNER JOIN assets a ON i.asset_id = a.id
                      LEFT JOIN categories c ON a.category_id = c.id
                      LEFT JOIN users u ON i.inspector_id = u.id
                      $where_sql
                      ORDER BY i.inspection_date DESC, i.created_at DESC";
$inspections = $conn->query($inspections_query);

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM asset_inspections")->fetch_assoc()['count'];
$stats['this_quarter'] = $conn->query("SELECT COUNT(*) as count FROM asset_inspections WHERE inspection_quarter = '$current_quarter' AND inspection_year = '$current_year'")->fetch_assoc()['count'];
$stats['passed'] = $conn->query("SELECT COUNT(*) as count FROM asset_inspections WHERE status = 'completed'")->fetch_assoc()['count'];
$stats['failed'] = $conn->query("SELECT COUNT(*) as count FROM asset_inspections WHERE status = 'failed'")->fetch_assoc()['count'];

// Get upcoming/overdue inspections - with error handling for collation issues
try {
    $upcoming = $conn->query("SELECT * FROM v_upcoming_inspections WHERE urgency IN ('overdue', 'due_soon') LIMIT 5");
} catch (Exception $e) {
    // If view has collation issues, use direct query
    $upcoming = $conn->query("
        SELECT 
            s.id,
            s.asset_id,
            a.asset_tag,
            a.name as asset_name,
            s.scheduled_date,
            s.scheduled_quarter,
            s.scheduled_year,
            s.status,
            DATEDIFF(s.scheduled_date, CURDATE()) as days_until_due,
            CASE 
                WHEN CURDATE() > s.scheduled_date THEN 'overdue'
                WHEN DATEDIFF(s.scheduled_date, CURDATE()) <= 7 THEN 'due_soon'
                ELSE 'upcoming'
            END as urgency
        FROM inspection_schedules s
        INNER JOIN assets a ON s.asset_id = a.id
        WHERE s.status = 'pending'
        HAVING urgency IN ('overdue', 'due_soon')
        ORDER BY s.scheduled_date ASC
        LIMIT 5
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections - <?php echo APP_NAME; ?></title>
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
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
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
        }
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .urgency-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .urgency-overdue { background: #fee; color: #c00; }
        .urgency-due-soon { background: #fff3cd; color: #856404; }
        .urgency-upcoming { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h4>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="assets.php"><i class="fas fa-box"></i> Assets</a>
            <a href="assignments.php"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="inspections.php" class="active"><i class="fas fa-clipboard-check"></i> Inspections</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Asset Inspections</h5>
                    <small class="text-muted">Quarterly inspection records</small>
                </div>
                <a href="inspection_add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Inspection
                </a>
            </div>
        </div>

        <div class="content-area">
            <!-- Statistics -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Inspections</h6>
                                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">This Quarter</h6>
                                <h3 class="mb-0"><?php echo $stats['this_quarter']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Passed</h6>
                                <h3 class="mb-0"><?php echo $stats['passed']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(0, 200, 100, 0.1); color: #00c864;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Failed</h6>
                                <h3 class="mb-0"><?php echo $stats['failed']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming/Overdue Inspections -->
            <?php if ($upcoming && $upcoming->num_rows > 0): ?>
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Upcoming & Overdue Inspections</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php while ($item = $upcoming->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($item['asset_tag']); ?></strong> - 
                                <?php echo htmlspecialchars($item['asset_name']); ?>
                                <br>
                                <small class="text-muted">
                                    Due: <?php echo formatDate($item['scheduled_date']); ?> (<?php echo $item['scheduled_quarter'] . ' ' . $item['scheduled_year']; ?>)
                                </small>
                            </div>
                            <div>
                                <span class="urgency-badge urgency-<?php echo $item['urgency']; ?>">
                                    <?php 
                                    if ($item['urgency'] == 'overdue') echo 'OVERDUE';
                                    elseif ($item['urgency'] == 'due_soon') echo 'DUE SOON';
                                    else echo 'UPCOMING';
                                    ?>
                                </span>
                                <a href="inspection_add.php?asset_id=<?php echo $item['asset_id']; ?>" class="btn btn-sm btn-primary ms-2">
                                    <i class="fas fa-clipboard-check"></i> Inspect
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small"><i class="fas fa-search me-1"></i>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Asset tag, inspector..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-calendar me-1"></i>Quarter</label>
                            <select name="quarter" class="form-select">
                                <option value="">All Quarters</option>
                                <option value="Q1" <?php echo $quarter_filter == 'Q1' ? 'selected' : ''; ?>>Q1</option>
                                <option value="Q2" <?php echo $quarter_filter == 'Q2' ? 'selected' : ''; ?>>Q2</option>
                                <option value="Q3" <?php echo $quarter_filter == 'Q3' ? 'selected' : ''; ?>>Q3</option>
                                <option value="Q4" <?php echo $quarter_filter == 'Q4' ? 'selected' : ''; ?>>Q4</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-calendar-alt me-1"></i>Year</label>
                            <select name="year" class="form-select">
                                <option value="">All Years</option>
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-info-circle me-1"></i>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Inspections List -->
            <div class="card card-custom">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Inspection Records</h5>
                        <span class="badge bg-secondary"><?php echo $inspections->num_rows; ?> Records</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Asset</th>
                                    <th>Inspector</th>
                                    <th>Date</th>
                                    <th>Quarter</th>
                                    <th>Unit Type</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($inspections->num_rows > 0): ?>
                                    <?php while ($inspection = $inspections->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($inspection['asset_tag']); ?></strong></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($inspection['asset_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($inspection['inspector_name'] ?: $inspection['inspector_username']); ?></td>
                                        <td><?php echo formatDate($inspection['inspection_date']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $inspection['inspection_quarter'] . ' ' . $inspection['inspection_year']; ?></span></td>
                                        <td><?php echo ucfirst($inspection['unit_type']); ?></td>
                                        <td>
                                            <?php
                                            $condition_class = [
                                                'excellent' => 'success',
                                                'good' => 'primary',
                                                'fair' => 'warning',
                                                'poor' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $condition_class[$inspection['overall_condition']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($inspection['overall_condition']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                'pending' => 'warning'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_class[$inspection['status']]; ?>">
                                                <?php echo ucfirst($inspection['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="inspection_detail.php?id=<?php echo $inspection['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-clipboard fa-3x mb-3 d-block"></i>
                                            No inspection records found
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