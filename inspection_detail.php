<?php
require_once 'config.php';
checkLogin();

$inspection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get inspection details
$inspection_query = "SELECT i.*, a.asset_tag, a.name as asset_name, a.serial_number,
                     c.name as category_name, u.full_name as inspector_full_name
                     FROM asset_inspections i
                     INNER JOIN assets a ON i.asset_id = a.id
                     LEFT JOIN categories c ON a.category_id = c.id
                     LEFT JOIN users u ON i.inspector_id = u.id
                     WHERE i.id = ?";
$stmt = $conn->prepare($inspection_query);
$stmt->bind_param("i", $inspection_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: inspections.php");
    exit();
}

$inspection = $result->fetch_assoc();

// Get component checklist
$components = $conn->query("SELECT * FROM inspection_components WHERE inspection_id = $inspection_id ORDER BY component_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Details - <?php echo APP_NAME; ?></title>
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
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 4px;
        }
        .check-result {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-block;
        }
        .check-yes { background: #d4edda; color: #155724; }
        .check-no { background: #f8d7da; color: #721c24; }
        .check-na { background: #e2e3e5; color: #383d41; }
        .component-table td {
            padding: 10px;
            vertical-align: middle;
        }
        .status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-yes { background: #28a745; }
        .status-no { background: #dc3545; }
        .status-na { background: #6c757d; }
        @media print {
            .sidebar, .top-navbar .btn, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar no-print">
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
        <div class="top-navbar no-print">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Inspection Report</h5>
                    <small class="text-muted"><?php echo htmlspecialchars($inspection['asset_tag']); ?> - <?php echo $inspection['inspection_quarter'] . ' ' . $inspection['inspection_year']; ?></small>
                </div>
                <div>
                    <a href="inspections.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="row g-4">
                <!-- Main Details -->
                <div class="col-lg-8">
                    <!-- Header Information -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Inspection Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Asset Tag</div>
                                        <div><strong><?php echo htmlspecialchars($inspection['asset_tag']); ?></strong></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Asset Name</div>
                                        <div><?php echo htmlspecialchars($inspection['asset_name']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Category</div>
                                        <div><?php echo htmlspecialchars($inspection['category_name']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Brand & Model</div>
                                        <div><?php echo htmlspecialchars($inspection['brand_model'] ?: 'N/A'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Serial Number</div>
                                        <div><?php echo htmlspecialchars($inspection['serial_number'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Inspection Date</div>
                                        <div><?php echo formatDate($inspection['inspection_date']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Quarter / Year</div>
                                        <div><span class="badge bg-info"><?php echo $inspection['inspection_quarter'] . ' ' . $inspection['inspection_year']; ?></span></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Unit Type</div>
                                        <div><?php echo ucfirst($inspection['unit_type']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Inspector</div>
                                        <div><?php echo htmlspecialchars($inspection['inspector_full_name'] ?: $inspection['inspector_username']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Subject User</div>
                                        <div><?php echo htmlspecialchars($inspection['subject_username'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($inspection['computer_name'] || $inspection['original_assignee'] || $inspection['inherited_from']): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="row">
                                    <?php if ($inspection['computer_name']): ?>
                                    <div class="col-md-4">
                                        <div class="info-label">Computer Name</div>
                                        <div><?php echo htmlspecialchars($inspection['computer_name']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($inspection['original_assignee']): ?>
                                    <div class="col-md-4">
                                        <div class="info-label">Original Assignee</div>
                                        <div><?php echo htmlspecialchars($inspection['original_assignee']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($inspection['inherited_from']): ?>
                                    <div class="col-md-4">
                                        <div class="info-label">Inherited From</div>
                                        <div><?php echo htmlspecialchars($inspection['inherited_from']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Basic Checks -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-check-square me-2"></i>Basic System Checks</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-label">Powers On Without Errors?</div>
                                    <span class="check-result check-<?php echo $inspection['powers_on']; ?>">
                                        <?php echo strtoupper($inspection['powers_on']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Passwords Removed?</div>
                                    <span class="check-result check-<?php echo $inspection['passwords_removed']; ?>">
                                        <?php echo strtoupper($inspection['passwords_removed']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Company Data Removed?</div>
                                    <span class="check-result check-<?php echo $inspection['company_data_removed']; ?>">
                                        <?php echo strtoupper($inspection['company_data_removed']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Total RAM</div>
                                    <div><?php echo htmlspecialchars($inspection['total_ram'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">HD Capacity</div>
                                    <div><?php echo htmlspecialchars($inspection['hd_capacity'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Condition -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Physical Condition</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php if ($inspection['unit_type'] == 'desktop'): ?>
                                    <div class="col-md-12">
                                        <div class="info-label">Physically Intact?</div>
                                        <span class="check-result check-<?php echo $inspection['physically_intact']; ?>">
                                            <?php echo strtoupper($inspection['physically_intact']); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="col-md-6">
                                        <div class="info-label">Case Cracks?</div>
                                        <span class="check-result check-<?php echo $inspection['case_cracks']; ?>">
                                            <?php echo strtoupper($inspection['case_cracks']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">LCD Scratches?</div>
                                        <span class="check-result check-<?php echo $inspection['lcd_scratches']; ?>">
                                            <?php echo strtoupper($inspection['lcd_scratches']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">LCD Discoloration?</div>
                                        <span class="check-result check-<?php echo $inspection['lcd_discoloration']; ?>">
                                            <?php echo strtoupper($inspection['lcd_discoloration']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Has Accessories?</div>
                                        <span class="check-result check-<?php echo $inspection['has_accessories']; ?>">
                                            <?php echo strtoupper($inspection['has_accessories']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="info-label">Activation Locks Removed?</div>
                                        <span class="check-result check-<?php echo $inspection['activation_locks_removed']; ?>">
                                            <?php echo strtoupper($inspection['activation_locks_removed']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Component Checklist -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Component Status</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm component-table mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Component</th>
                                            <th class="text-center">Missing</th>
                                            <th class="text-center">Working</th>
                                            <th class="text-center">Damaged</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($comp = $components->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($comp['component_name']); ?></strong></td>
                                            <td class="text-center">
                                                <span class="status-icon status-<?php echo $comp['is_missing']; ?>"></span>
                                                <?php echo strtoupper($comp['is_missing']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-icon status-<?php echo $comp['is_working']; ?>"></span>
                                                <?php echo strtoupper($comp['is_working']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-icon status-<?php echo $comp['is_damaged']; ?>"></span>
                                                <?php echo strtoupper($comp['is_damaged']); ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Comments -->
                    <?php if ($inspection['inspector_comments'] || $inspection['subject_user_comments']): ?>
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-comment me-2"></i>Comments</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($inspection['inspector_comments']): ?>
                            <div class="mb-3">
                                <div class="info-label">ICT Inspector Comments</div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($inspection['inspector_comments'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($inspection['subject_user_comments']): ?>
                            <div class="<?php echo $inspection['inspector_comments'] ? 'pt-3 border-top' : ''; ?>">
                                <div class="info-label">Subject User Comments</div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($inspection['subject_user_comments'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Overall Status -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Overall Assessment</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php
                            $status_class = [
                                'completed' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning'
                            ];
                            $condition_class = [
                                'excellent' => 'success',
                                'good' => 'primary',
                                'fair' => 'warning',
                                'poor' => 'danger'
                            ];
                            ?>
                            <div class="mb-3">
                                <div class="info-label">Status</div>
                                <h4><span class="badge bg-<?php echo $status_class[$inspection['status']]; ?>">
                                    <?php echo strtoupper($inspection['status']); ?>
                                </span></h4>
                            </div>
                            <div>
                                <div class="info-label">Overall Condition</div>
                                <h4><span class="badge bg-<?php echo $condition_class[$inspection['overall_condition']]; ?>">
                                    <?php echo strtoupper($inspection['overall_condition']); ?>
                                </span></h4>
                            </div>

                            <hr class="my-3">

                            <div class="text-start small text-muted">
                                <p class="mb-2"><i class="fas fa-calendar me-1"></i> <strong>Inspected:</strong><br><?php echo date('d M Y, h:i A', strtotime($inspection['created_at'])); ?></p>
                                <p class="mb-0"><i class="fas fa-building me-1"></i> <strong>Location:</strong><br>On-site inspection</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card card-custom no-print">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <a href="asset_detail.php?id=<?php echo $inspection['asset_id']; ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-box me-2"></i>View Asset Details
                            </a>
                            <a href="inspection_add.php?asset_id=<?php echo $inspection['asset_id']; ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-plus me-2"></i>New Inspection
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>