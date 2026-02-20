<?php
require_once 'config.php';
checkLogin();

// Get report type
$report_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all_assets';
$export = isset($_GET['export']) ? sanitize($_GET['export']) : '';

// Date filters
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query based on report type
$data = [];
$report_title = '';

switch ($report_type) {
    case 'all_assets':
        $report_title = 'All Assets Report';
        $query = "SELECT a.*, c.name as category_name, s.name as supplier_name,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.full_name
                      ELSE u.full_name
                  END as staff_name,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.department
                      ELSE u.department
                  END as staff_department,
                  a.section,
                  a.last_inspection_date,
                  a.inspection_count
                  FROM assets a 
                  LEFT JOIN categories c ON a.category_id = c.id 
                  LEFT JOIN suppliers s ON a.supplier_id = s.id 
                  LEFT JOIN asset_assignments aa ON a.id = aa.asset_id AND aa.status = 'active'
                  LEFT JOIN staff st ON aa.staff_id = st.id
                  LEFT JOIN users u ON aa.assigned_to = u.id
                  ORDER BY a.asset_tag";
        $data = $conn->query($query);
        break;
        
    case 'by_status':
        $report_title = 'Assets by Status Report';
        $query = "SELECT status, COUNT(*) as count, 
                  GROUP_CONCAT(asset_tag SEPARATOR ', ') as assets,
                  SUM(purchase_cost) as total_value
                  FROM assets 
                  GROUP BY status";
        $data = $conn->query($query);
        break;
        
    case 'by_category':
        $report_title = 'Assets by Category Report';
        $query = "SELECT c.name as category, COUNT(a.id) as count,
                  SUM(a.purchase_cost) as total_value
                  FROM categories c
                  LEFT JOIN assets a ON c.id = a.category_id
                  GROUP BY c.id, c.name
                  ORDER BY count DESC";
        $data = $conn->query($query);
        break;
        
    case 'assignments':
        $report_title = 'Current Assignments Report';
        $query = "SELECT a.asset_tag, a.name as asset_name, a.department, a.section,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.full_name
                      ELSE u.full_name
                  END as staff_name,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.department
                      ELSE u.department
                  END as staff_department,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.floor
                      ELSE u.floor
                  END as staff_floor,
                  aa.assigned_date, aa.notes
                  FROM asset_assignments aa
                  JOIN assets a ON aa.asset_id = a.id
                  LEFT JOIN staff st ON aa.staff_id = st.id
                  LEFT JOIN users u ON aa.assigned_to = u.id
                  WHERE aa.status = 'active'
                  ORDER BY aa.assigned_date DESC";
        $data = $conn->query($query);
        break;
        
    case 'warranty_expiring':
        $report_title = 'Warranty Expiring Soon';
        $thirty_days = date('Y-m-d', strtotime('+30 days'));
        $query = "SELECT a.asset_tag, a.name, a.warranty_expiry, a.supplier_id, a.department, a.section,
                  DATEDIFF(a.warranty_expiry, CURDATE()) as days_left,
                  CASE 
                      WHEN aa.assigned_to_type = 'staff' THEN st.full_name
                      ELSE u.full_name
                  END as staff_name
                  FROM assets a
                  LEFT JOIN asset_assignments aa ON a.id = aa.asset_id AND aa.status = 'active'
                  LEFT JOIN staff st ON aa.staff_id = st.id
                  LEFT JOIN users u ON aa.assigned_to = u.id
                  WHERE a.warranty_expiry IS NOT NULL 
                  AND a.warranty_expiry BETWEEN CURDATE() AND '$thirty_days'
                  ORDER BY a.warranty_expiry ASC";
        $data = $conn->query($query);
        break;
        
    case 'by_location':
        $report_title = 'Assets by Location Report';
        $query = "SELECT a.floor, a.location, a.department, a.section, COUNT(*) as count,
                  GROUP_CONCAT(a.asset_tag SEPARATOR ', ') as assets
                  FROM assets a
                  WHERE a.floor IS NOT NULL AND a.floor != ''
                  GROUP BY a.floor, a.location, a.department, a.section
                  ORDER BY a.floor, a.location";
        $data = $conn->query($query);
        break;
        
    case 'by_department':
        $report_title = 'Assets by Department & Section Report';
        $query = "SELECT a.department, a.section, COUNT(*) as count,
                  SUM(a.purchase_cost) as total_value,
                  GROUP_CONCAT(a.asset_tag SEPARATOR ', ') as assets
                  FROM assets a
                  WHERE a.department IS NOT NULL AND a.department != ''
                  GROUP BY a.department, a.section
                  ORDER BY a.department, a.section";
        $data = $conn->query($query);
        break;
        
    case 'all_inspections':
        $report_title = 'All Inspections Report';
        $query = "SELECT i.*, a.asset_tag, a.name as asset_name, a.department, a.section,
                  c.name as category_name, u.full_name as inspector_name
                  FROM asset_inspections i
                  INNER JOIN assets a ON i.asset_id = a.id
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  ORDER BY i.inspection_date DESC";
        $data = $conn->query($query);
        break;
        
    case 'inspections_by_quarter':
        $report_title = 'Inspections by Quarter Report';
        $query = "SELECT inspection_quarter, inspection_year, 
                  COUNT(*) as total_inspections,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                  SUM(CASE WHEN overall_condition = 'excellent' THEN 1 ELSE 0 END) as excellent,
                  SUM(CASE WHEN overall_condition = 'good' THEN 1 ELSE 0 END) as good,
                  SUM(CASE WHEN overall_condition = 'fair' THEN 1 ELSE 0 END) as fair,
                  SUM(CASE WHEN overall_condition = 'poor' THEN 1 ELSE 0 END) as poor
                  FROM asset_inspections
                  GROUP BY inspection_year, inspection_quarter
                  ORDER BY inspection_year DESC, inspection_quarter DESC";
        $data = $conn->query($query);
        break;
        
    case 'inspections_failed':
        $report_title = 'Failed Inspections Report';
        $query = "SELECT i.*, a.asset_tag, a.name as asset_name, a.department, a.section,
                  u.full_name as inspector_name
                  FROM asset_inspections i
                  INNER JOIN assets a ON i.asset_id = a.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  WHERE i.status = 'failed'
                  ORDER BY i.inspection_date DESC";
        $data = $conn->query($query);
        break;
        
    case 'inspections_overdue':
        $report_title = 'Overdue Inspections Report';
        $query = "SELECT s.*, a.asset_tag, a.name as asset_name, a.department, a.section,
                  c.name as category_name,
                  DATEDIFF(CURDATE(), s.scheduled_date) as days_overdue
                  FROM inspection_schedules s
                  INNER JOIN assets a ON s.asset_id = a.id
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE s.status = 'pending' AND s.scheduled_date < CURDATE()
                  ORDER BY days_overdue DESC";
        $data = $conn->query($query);
        break;
        
   case 'assets_never_inspected':
    $report_title = 'Assets Never Inspected';
    $query = "SELECT a.id, a.asset_tag, a.name, a.department, a.section, 
              c.name as category_name, a.purchase_date,
              DATEDIFF(CURDATE(), a.purchase_date) as days_since_purchase
              FROM assets a
              LEFT JOIN categories c ON a.category_id = c.id
              WHERE a.inspection_count = 0 OR a.inspection_count IS NULL
              AND a.status != 'retired'
              ORDER BY a.purchase_date ASC";
    $data = $conn->query($query);
    break;
        
    case 'inspection_component_issues':
    $report_title = 'Component Issues Report';
    $query = "SELECT ic.component_name,
              COUNT(*) as total_checks,
              SUM(CASE WHEN ic.is_missing = 'yes' THEN 1 ELSE 0 END) as missing_count,
              SUM(CASE WHEN ic.is_damaged = 'yes' THEN 1 ELSE 0 END) as damaged_count,
              SUM(CASE WHEN ic.is_working = 'no' THEN 1 ELSE 0 END) as not_working_count
              FROM inspection_components ic
              GROUP BY ic.component_name
              HAVING SUM(CASE WHEN ic.is_missing = 'yes' THEN 1 ELSE 0 END) > 0 
                  OR SUM(CASE WHEN ic.is_damaged = 'yes' THEN 1 ELSE 0 END) > 0 
                  OR SUM(CASE WHEN ic.is_working = 'no' THEN 1 ELSE 0 END) > 0
              ORDER BY (SUM(CASE WHEN ic.is_missing = 'yes' THEN 1 ELSE 0 END) + 
                       SUM(CASE WHEN ic.is_damaged = 'yes' THEN 1 ELSE 0 END) + 
                       SUM(CASE WHEN ic.is_working = 'no' THEN 1 ELSE 0 END)) DESC";
    $data = $conn->query($query);
    break;
}

// Handle Excel Export
if ($export === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $report_title) . '_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='15' style='font-size:16px; font-weight:bold;'>" . $report_title . "</th></tr>";
    echo "<tr><th colspan='15'>Generated: " . date('d M Y H:i:s') . " | Generated by: " . $_SESSION['full_name'] . "</th></tr>";
    echo "<tr><th></th></tr>";
    
    // Headers based on report type
    if ($report_type === 'all_assets') {
        echo "<tr>
                <th>Asset Tag</th>
                <th>Name</th>
                <th>Category</th>
                <th>Model</th>
                <th>Serial Number</th>
                <th>Department</th>
                <th>Section</th>
                <th>Staff Name</th>
                <th>Status</th>
                <th>Purchase Date</th>
                <th>Purchase Cost</th>
                <th>Location</th>
                <th>Floor</th>
                <th>Last Inspection</th>
                <th>Inspection Count</th>
              </tr>";
        $data->data_seek(0);
        while ($row = $data->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['asset_tag']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['category_name']}</td>
                    <td>{$row['model']}</td>
                    <td>{$row['serial_number']}</td>
                    <td>{$row['department']}</td>
                    <td>{$row['section']}</td>
                    <td>" . ($row['staff_name'] ?: 'Unassigned') . "</td>
                    <td>{$row['status']}</td>
                    <td>{$row['purchase_date']}</td>
                    <td>{$row['purchase_cost']}</td>
                    <td>{$row['location']}</td>
                    <td>{$row['floor']}</td>
                    <td>" . ($row['last_inspection_date'] ?: 'Never') . "</td>
                    <td>" . ($row['inspection_count'] ?: '0') . "</td>
                  </tr>";
        }
    } elseif ($report_type === 'all_inspections') {
        echo "<tr>
                <th>Asset Tag</th>
                <th>Asset Name</th>
                <th>Department</th>
                <th>Section</th>
                <th>Inspection Date</th>
                <th>Quarter</th>
                <th>Year</th>
                <th>Inspector</th>
                <th>Unit Type</th>
                <th>Status</th>
                <th>Overall Condition</th>
                <th>Powers On</th>
                <th>Passwords Removed</th>
                <th>Data Removed</th>
              </tr>";
        $data->data_seek(0);
        while ($row = $data->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['asset_tag']}</td>
                    <td>{$row['asset_name']}</td>
                    <td>{$row['department']}</td>
                    <td>{$row['section']}</td>
                    <td>{$row['inspection_date']}</td>
                    <td>{$row['inspection_quarter']}</td>
                    <td>{$row['inspection_year']}</td>
                    <td>{$row['inspector_name']}</td>
                    <td>{$row['unit_type']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['overall_condition']}</td>
                    <td>{$row['powers_on']}</td>
                    <td>{$row['passwords_removed']}</td>
                    <td>{$row['company_data_removed']}</td>
                  </tr>";
        }
    }
    
    echo "</table>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
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
        .sidebar-menu {
            padding: 20px 0;
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
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .report-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .report-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            text-decoration: none;
            color: inherit;
        }
        .report-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .section-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0"><i class="fas fa-laptop me-2"></i><?php echo APP_NAME; ?></h4>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="assets.php"><i class="fas fa-box"></i> Assets</a>
            <a href="assignments.php"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="inspections.php"><i class="fas fa-clipboard-check"></i> Inspections</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h5 class="mb-0">Reports & Analytics</h5>
            <small class="text-muted">Generate and export comprehensive reports</small>
        </div>

        <div class="content-area">
            <?php if (!isset($_GET['type'])): ?>
                <!-- Asset Reports Section -->
                <div class="section-header">
                    <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Asset Reports</h6>
                </div>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <a href="?type=all_assets" class="report-card">
                            <div class="report-icon"><i class="fas fa-boxes"></i></div>
                            <h5>All Assets Report</h5>
                            <p class="text-muted small mb-0">Complete list of all assets in inventory</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=by_status" class="report-card">
                            <div class="report-icon"><i class="fas fa-chart-pie"></i></div>
                            <h5>Assets by Status</h5>
                            <p class="text-muted small mb-0">Breakdown of assets by their current status</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=by_category" class="report-card">
                            <div class="report-icon"><i class="fas fa-tags"></i></div>
                            <h5>Assets by Category</h5>
                            <p class="text-muted small mb-0">Assets grouped by categories with values</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=assignments" class="report-card">
                            <div class="report-icon"><i class="fas fa-user-check"></i></div>
                            <h5>Current Assignments</h5>
                            <p class="text-muted small mb-0">Assets currently assigned to staff members</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=warranty_expiring" class="report-card">
                            <div class="report-icon"><i class="fas fa-clock"></i></div>
                            <h5>Warranty Expiring</h5>
                            <p class="text-muted small mb-0">Assets with warranties expiring in 30 days</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=by_location" class="report-card">
                            <div class="report-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <h5>Assets by Location</h5>
                            <p class="text-muted small mb-0">Asset distribution across floors and locations</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=by_department" class="report-card">
                            <div class="report-icon"><i class="fas fa-building"></i></div>
                            <h5>Assets by Department</h5>
                            <p class="text-muted small mb-0">Assets grouped by department and section</p>
                        </a>
                    </div>
                </div>

                <!-- Inspection Reports Section -->
                <div class="section-header">
                    <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Inspection Reports</h6>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <a href="?type=all_inspections" class="report-card">
                            <div class="report-icon"><i class="fas fa-clipboard-list"></i></div>
                            <h5>All Inspections</h5>
                            <p class="text-muted small mb-0">Complete list of all inspection records</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=inspections_by_quarter" class="report-card">
                            <div class="report-icon"><i class="fas fa-calendar-alt"></i></div>
                            <h5>Inspections by Quarter</h5>
                            <p class="text-muted small mb-0">Quarterly inspection statistics and trends</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=inspections_failed" class="report-card">
                            <div class="report-icon"><i class="fas fa-times-circle"></i></div>
                            <h5>Failed Inspections</h5>
                            <p class="text-muted small mb-0">Assets that failed inspection checks</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=inspections_overdue" class="report-card">
                            <div class="report-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <h5>Overdue Inspections</h5>
                            <p class="text-muted small mb-0">Scheduled inspections that are overdue</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=assets_never_inspected" class="report-card">
                            <div class="report-icon"><i class="fas fa-question-circle"></i></div>
                            <h5>Never Inspected</h5>
                            <p class="text-muted small mb-0">Assets that have never been inspected</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?type=inspection_component_issues" class="report-card">
                            <div class="report-icon"><i class="fas fa-wrench"></i></div>
                            <h5>Component Issues</h5>
                            <p class="text-muted small mb-0">Summary of component problems found</p>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Report Display -->
                <div class="card card-custom">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo $report_title; ?></h5>
                            <div>
                                <a href="?type=<?php echo $report_type; ?>&export=excel" class="btn btn-success me-2">
                                    <i class="fas fa-file-excel me-2"></i>Export Excel
                                </a>
                                <button onclick="window.print()" class="btn btn-primary me-2">
                                    <i class="fas fa-print me-2"></i>Print
                                </button>
                                <a href="reports.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <?php
                                    // Include table headers based on report type
                                    include 'report_headers.php';
                                    ?>
                                </thead>
                                <tbody>
                                    <?php
                                    // Include table rows based on report type  
                                    include 'report_rows.php';
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>