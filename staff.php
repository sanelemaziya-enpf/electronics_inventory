<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$section_filter = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$floor_filter = isset($_GET['floor']) ? sanitize($_GET['floor']) : '';

$where_clauses = ["s.status='active'"];
if ($search) {
    $where_clauses[] = "(s.full_name LIKE '%$search%' OR s.email LIKE '%$search%' OR s.staff_id LIKE '%$search%' OR s.employee_id LIKE '%$search%')";
}
if ($department_filter) {
    $where_clauses[] = "s.department = '$department_filter'";
}
if ($section_filter) {
    $where_clauses[] = "s.section = '$section_filter'";
}
if ($floor_filter) {
    $where_clauses[] = "s.floor = '$floor_filter'";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Get staff members
$staff_query = "SELECT s.*, 
                COUNT(DISTINCT aa.id) as active_assignments,
                u.full_name as created_by_name
                FROM staff s
                LEFT JOIN asset_assignments aa ON s.id = aa.staff_id AND aa.status = 'active'
                LEFT JOIN users u ON s.created_by = u.id
                $where_sql
                GROUP BY s.id
                ORDER BY s.full_name";
$staff = $conn->query($staff_query);

// Get filter options
$departments = $conn->query("SELECT DISTINCT department FROM staff WHERE department IS NOT NULL AND department != '' AND status='active' ORDER BY department");
$sections = $conn->query("SELECT DISTINCT section FROM staff WHERE section IS NOT NULL AND section != '' AND status='active' ORDER BY section");
$floors = $conn->query("SELECT DISTINCT floor FROM staff WHERE floor IS NOT NULL AND floor != '' AND status='active' ORDER BY floor");

// Get statistics
$stats = [];
$stats['total_staff'] = $conn->query("SELECT COUNT(*) as count FROM staff WHERE status='active'")->fetch_assoc()['count'];
$stats['with_assignments'] = $conn->query("SELECT COUNT(DISTINCT staff_id) as count FROM asset_assignments WHERE staff_id IS NOT NULL AND status='active'")->fetch_assoc()['count'];
$stats['departments'] = $conn->query("SELECT COUNT(DISTINCT department) as count FROM staff WHERE department IS NOT NULL AND department != '' AND status='active'")->fetch_assoc()['count'];
$stats['sections'] = $conn->query("SELECT COUNT(DISTINCT section) as count FROM staff WHERE section IS NOT NULL AND section != '' AND status='active'")->fetch_assoc()['count'];
$stats['floors'] = $conn->query("SELECT COUNT(DISTINCT floor) as count FROM staff WHERE floor IS NOT NULL AND floor != '' AND status='active'")->fetch_assoc()['count'];

// Define department-sections mapping (same as in add_staff.php)
$department_sections = [
    'Finance' => ['Accounts', 'IT', 'Procurement'],
    'Corporate' => ['Marketing', 'HR', ],
    'Operations' => ['Service Center', 'Compliance and Recon'],
    'CEO' => ['CEO', 'Strategy'],
    'Investments' => ['Investments', 'Property',]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Directory - <?php echo APP_NAME; ?></title>
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
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .staff-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            background: white;
            height: 100%;
        }
        .staff-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }
        .staff-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 15px;
        }
        .role-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 11px;
        }
        .info-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .assignments-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .section-badge {
            display: inline-block;
            background: #f0e6ff;
            color: #764ba2;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        .dept-section-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
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
            <a href="assets.php">
                <i class="fas fa-box"></i> Assets
            </a>
            <a href="assignments.php">
                <i class="fas fa-exchange-alt"></i> Assignments
            </a>
            <a href="staff.php" class="active">
                <i class="fas fa-users"></i> End User Directory
            </a>
            <a href="categories.php">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="suppliers.php">
                <i class="fas fa-truck"></i> Suppliers
            </a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php">
                <i class="fas fa-user-cog"></i> User Management
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
                <h5 class="mb-0">End User Directory</h5>
                <small class="text-muted">View all staff members and their asset assignments</small>
            </div>
            <div class="d-flex align-items-center">
                <?php if (hasPermission('admin')): ?>
                <a href="add_staff.php" class="btn btn-primary me-3">
                    <i class="fas fa-user-plus me-2"></i>Add Staff Member
                </a>
                <?php endif; ?>
                <span class="badge bg-primary me-3"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></span>
                <i class="fas fa-user-circle" style="font-size: 24px; color: var(--primary-color);"></i>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Staff</h6>
                                <h3 class="mb-0"><?php echo $stats['total_staff']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">With Assets</h6>
                                <h3 class="mb-0"><?php echo $stats['with_assignments']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Departments</h6>
                                <h3 class="mb-0"><?php echo $stats['departments']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Sections</h6>
                                <h3 class="mb-0"><?php echo $stats['sections']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9c27b0;">
                                <i class="fas fa-sitemap"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Floors</h6>
                                <h3 class="mb-0"><?php echo $stats['floors']; ?></h3>
                            </div>
                            <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                <i class="fas fa-layer-group"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-search me-1"></i>Search Staff</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-building me-1"></i>Department</label>
                            <select name="department" class="form-select" id="departmentSelect">
                                <option value="">All Departments</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-sitemap me-1"></i>Section</label>
                            <select name="section" class="form-select" id="sectionSelect">
                                <option value="">All Sections</option>
                                <?php while ($sect = $sections->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($sect['section']); ?>" <?php echo $section_filter == $sect['section'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sect['section']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small"><i class="fas fa-layer-group me-1"></i>Floor</label>
                            <select name="floor" class="form-select">
                                <option value="">All Floors</option>
                                <?php while ($floor = $floors->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($floor['floor']); ?>" <?php echo $floor_filter == $floor['floor'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($floor['floor']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Staff Grid -->
            <div class="row g-4">
                <?php if ($staff->num_rows > 0): ?>
                    <?php while ($member = $staff->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="staff-card">
                            <div class="staff-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="text-center mb-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($member['staff_id']); ?></small>
                                <?php if ($member['position']): ?>
                                <span class="badge bg-info text-white">
                                    <?php echo htmlspecialchars($member['position']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="info-item">
                                <small class="text-muted"><i class="fas fa-envelope me-2"></i>Email</small>
                                <div class="small"><?php echo htmlspecialchars($member['email']); ?></div>
                            </div>

                            <?php if ($member['phone']): ?>
                            <div class="info-item">
                                <small class="text-muted"><i class="fas fa-phone me-2"></i>Phone</small>
                                <div class="small"><?php echo htmlspecialchars($member['phone']); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($member['department']): ?>
                            <div class="info-item">
                                <small class="text-muted"><i class="fas fa-building me-2"></i>Department</small>
                                <div class="small"><?php echo htmlspecialchars($member['department']); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($member['section']): ?>
                            <div class="info-item">
                                <small class="text-muted"><i class="fas fa-sitemap me-2"></i>Section</small>
                                <div>
                                    <span class="section-badge">
                                        <?php echo htmlspecialchars($member['section']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($member['floor']): ?>
                            <div class="info-item">
                                <small class="text-muted"><i class="fas fa-layer-group me-2"></i>Floor</small>
                                <div class="small"><?php echo htmlspecialchars($member['floor']); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3 pt-3 border-top text-center">
                                <?php if ($member['active_assignments'] > 0): ?>
                                <span class="assignments-badge">
                                    <i class="fas fa-box me-1"></i><?php echo $member['active_assignments']; ?> Active Asset<?php echo $member['active_assignments'] > 1 ? 's' : ''; ?>
                                </span>
                                <?php else: ?>
                                <small class="text-muted">No active assignments</small>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewStaffDetails(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card card-custom text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5>No Staff Members Found</h5>
                            <p class="text-muted">Try adjusting your filters or search terms</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Staff Details Modal -->
    <div class="modal fade" id="staffDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Staff Member Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="staffDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Department to Sections mapping
        const departmentSections = <?php echo json_encode($department_sections); ?>;

        function viewStaffDetails(userId) {
            const modal = new bootstrap.Modal(document.getElementById('staffDetailsModal'));
            modal.show();
            
            // Fetch staff details via AJAX
            fetch('get_staff_details.php?id=' + userId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('staffDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('staffDetailsContent').innerHTML = 
                        '<div class="alert alert-danger">Error loading staff details. Please try again.</div>';
                });
        }

        // Optional: Update section filter based on department selection (for convenience)
        document.getElementById('departmentSelect').addEventListener('change', function() {
            const department = this.value;
            const sectionSelect = document.getElementById('sectionSelect');
            
            if (department && departmentSections[department]) {
                // Show only sections for the selected department
                const sections = departmentSections[department];
                const currentValue = sectionSelect.value;
                
                // Keep the form submission straightforward - just mark which sections are available
                console.log('Available sections for ' + department + ':', sections);
            }
        });
    </script>
</body>
</html>