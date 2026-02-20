<?php
require_once 'config.php';
checkLogin();

$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get asset details
$asset_query = "SELECT a.*, c.name as category_name, s.name as supplier_name, s.contact_person, s.email, s.phone,
                u.full_name as created_by_name, u.email as created_by_email
                FROM assets a 
                LEFT JOIN categories c ON a.category_id = c.id 
                LEFT JOIN suppliers s ON a.supplier_id = s.id 
                LEFT JOIN users u ON a.created_by = u.id 
                WHERE a.id = ?";
$stmt = $conn->prepare($asset_query);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: assets.php");
    exit();
}

$asset = $result->fetch_assoc();

// Get assignment history
$assignments = $conn->query("SELECT aa.*, u.full_name as assigned_to_name, u2.full_name as assigned_by_name 
                             FROM asset_assignments aa 
                             LEFT JOIN users u ON aa.assigned_to = u.id 
                             LEFT JOIN users u2 ON aa.assigned_by = u2.id 
                             WHERE aa.asset_id = $asset_id 
                             ORDER BY aa.created_at DESC");

// Get activity logs for this asset
$logs = $conn->query("SELECT al.*, u.full_name 
                      FROM activity_logs al 
                      LEFT JOIN users u ON al.user_id = u.id 
                      WHERE al.table_name = 'assets' AND al.record_id = $asset_id 
                      ORDER BY al.created_at DESC 
                      LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Details - <?php echo APP_NAME; ?></title>
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
        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 4px;
        }
        .badge-status {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
        }
        .timeline-item {
            position: relative;
            padding-left: 35px;
            padding-bottom: 20px;
            border-left: 2px solid #e0e0e0;
        }
        .timeline-item:last-child {
            border-left: none;
        }
        .timeline-dot {
            position: absolute;
            left: -7px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
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
            <a href="assets.php" class="active"><i class="fas fa-box"></i> Assets</a>
            <a href="assignments.php"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php" class="active"><i class="fas fa-cogs"></i> Asset Models</a> 
            <a href="inspections.php"><i class="fas fa-clipboard-check"></i> Inspections</a>
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
                    <h5 class="mb-0">Asset Details</h5>
                   <!-- <small class="text-muted"><?php echo htmlspecialchars($asset['asset_tag']); ?></small>-->
                </div> 
                <div>
                    <a href="assets.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <?php if (hasPermission('admin')): ?>
                    <a href="asset_edit.php?id=<?php echo $asset_id; ?>" class="btn btn-warning text-white">
                        <i class="fas fa-edit me-2"></i>Edit Asset
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="row g-4">
                <!-- Asset Information -->
                <div class="col-lg-8">
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Asset Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                   <!-- <div class="info-row">
                                        <div class="info-label">Asset Tag</div>
                                        <div><strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong></div>
                                    </div>-->
                                    <div class="info-row">
                                        <div class="info-label">Asset Name</div>
                                        <div><?php echo htmlspecialchars($asset['name']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Category</div>
                                        <div><?php echo htmlspecialchars($asset['category_name']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Model</div>
                                        <div><?php echo htmlspecialchars($asset['model'] ?: 'N/A'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Serial Number</div>
                                        <div><?php echo htmlspecialchars($asset['serial_number'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Status</div>
                                        <div>
                                            <?php
                                            $badge_class = [
                                                'available' => 'success',
                                                'assigned' => 'warning',
                                                'maintenance' => 'danger',
                                                'retired' => 'secondary'
                                            ];
                                            ?>
                                            <span class="badge badge-status bg-<?php echo $badge_class[$asset['status']]; ?>">
                                                <?php echo ucfirst($asset['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Condition</div>
                                        <div><?php echo ucfirst($asset['condition_status']); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Location</div>
                                        <div><?php echo htmlspecialchars($asset['location'] ?: 'N/A'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Floor</div>
                                        <div><?php echo htmlspecialchars($asset['floor'] ?: 'N/A'); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Department</div>
                                        <div><?php echo htmlspecialchars($asset['department'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($asset['description']): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="info-label">Description</div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($asset['description'])); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if ($asset['specifications']): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="info-label">Specifications</div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($asset['specifications'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Purchase & Financial Information -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Purchase & Financial Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Purchase Date</div>
                                        <div><?php echo $asset['purchase_date'] ? formatDate($asset['purchase_date']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Purchase Cost</div>
                                        <div><?php echo $asset['purchase_cost'] ? formatCurrency($asset['purchase_cost']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Warranty Expiry</div>
                                        <div>
                                            <?php 
                                            if ($asset['warranty_expiry']) {
                                                echo formatDate($asset['warranty_expiry']);
                                                $days_left = ceil((strtotime($asset['warranty_expiry']) - time()) / 86400);
                                                if ($days_left > 0) {
                                                    echo ' <span class="badge bg-success">(' . $days_left . ' days left)</span>';
                                                } else {
                                                    echo ' <span class="badge bg-danger">(Expired)</span>';
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment History -->
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Assignment History</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($assignments->num_rows > 0): ?>
                                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($assignment['assigned_to_name']); ?></strong>
                                        <div class="text-muted small">
                                            Assigned: <?php echo formatDate($assignment['assigned_date']); ?>
                                            <?php if ($assignment['return_date']): ?>
                                                | Returned: <?php echo formatDate($assignment['return_date']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($assignment['notes']): ?>
                                            <div class="text-muted small mt-1"><?php echo htmlspecialchars($assignment['notes']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No assignment history</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ADD THIS SECTION TO YOUR EXISTING asset_detail.php AFTER THE ASSIGNMENT HISTORY CARD -->

<!-- Inspection History -->
<div class="card card-custom">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Inspection History</h5>
        <a href="inspection_add.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-2"></i>New Inspection
        </a>
    </div>
    <div class="card-body">
        <?php
        // Get inspection history for this asset
        $inspections = $conn->query("SELECT i.*, u.full_name as inspector_name
                                     FROM asset_inspections i
                                     LEFT JOIN users u ON i.inspector_id = u.id
                                     WHERE i.asset_id = $asset_id
                                     ORDER BY i.inspection_date DESC
                                     LIMIT 10");
        
        if ($inspections->num_rows > 0):
        ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Quarter</th>
                            <th>Inspector</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($insp = $inspections->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($insp['inspection_date']); ?></td>
                            <td><span class="badge bg-info"><?php echo $insp['inspection_quarter'] . ' ' . $insp['inspection_year']; ?></span></td>
                            <td><?php echo htmlspecialchars($insp['inspector_name'] ?: $insp['inspector_username']); ?></td>
                            <td>
                                <?php
                                $cond_colors = [
                                    'excellent' => 'success',
                                    'good' => 'primary',
                                    'fair' => 'warning',
                                    'poor' => 'danger'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $cond_colors[$insp['overall_condition']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($insp['overall_condition']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $stat_colors = [
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'pending' => 'warning'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $stat_colors[$insp['status']]; ?>">
                                    <?php echo ucfirst($insp['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="inspection_detail.php?id=<?php echo $insp['id']; ?>" class="btn btn-xs btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-clipboard fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-3">No inspection records yet</p>
                <a href="inspection_add.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-2"></i>Conduct First Inspection
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ALSO ADD THIS TO THE ASSET SIDEBAR (RIGHT COLUMN) -->

<!-- Inspection Status -->
<?php
$last_inspection = $conn->query("SELECT * FROM asset_inspections 
                                 WHERE asset_id = $asset_id 
                                 ORDER BY inspection_date DESC LIMIT 1")->fetch_assoc();
$next_inspection = $conn->query("SELECT * FROM inspection_schedules 
                                WHERE asset_id = $asset_id AND status = 'pending'
                                ORDER BY scheduled_date ASC LIMIT 1")->fetch_assoc();
?>
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Inspection Status</h6>
    </div>
    <div class="card-body">
        <?php if ($last_inspection): ?>
        <div class="info-row">
            <div class="info-label">Last Inspection</div>
            <div>
                <?php echo formatDate($last_inspection['inspection_date']); ?>
                <br>
                <small class="text-muted">
                    <?php echo $last_inspection['inspection_quarter'] . ' ' . $last_inspection['inspection_year']; ?>
                </small>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Last Condition</div>
            <div>
                <?php
                $cond_colors = [
                    'excellent' => 'success',
                    'good' => 'primary',
                    'fair' => 'warning',
                    'poor' => 'danger'
                ];
                ?>
                <span class="badge bg-<?php echo $cond_colors[$last_inspection['overall_condition']] ?? 'secondary'; ?>">
                    <?php echo ucfirst($last_inspection['overall_condition']); ?>
                </span>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info small mb-0">
            <i class="fas fa-info-circle me-1"></i>
            No inspections conducted yet
        </div>
        <?php endif; ?>

        <?php if ($next_inspection): ?>
        <div class="info-row">
            <div class="info-label">Next Inspection</div>
            <div>
                <?php echo formatDate($next_inspection['scheduled_date']); ?>
                <br>
                <small class="text-muted">
                    <?php echo $next_inspection['scheduled_quarter'] . ' ' . $next_inspection['scheduled_year']; ?>
                </small>
            </div>
        </div>
        <?php
        $days_until = ceil((strtotime($next_inspection['scheduled_date']) - time()) / 86400);
        if ($days_until < 0):
        ?>
        <div class="alert alert-danger small mt-2 mb-0">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>Overdue</strong> by <?php echo abs($days_until); ?> days
        </div>
        <?php elseif ($days_until <= 7): ?>
        <div class="alert alert-warning small mt-2 mb-0">
            <i class="fas fa-clock me-1"></i>
            <strong>Due Soon</strong> in <?php echo $days_until; ?> days
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <hr class="my-3">

        <a href="inspection_add.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-sm btn-primary w-100">
            <i class="fas fa-clipboard-check me-2"></i>Conduct Inspection
        </a>
    </div>
</div>

                <!-- Sidebar Information -->
                <div class="col-lg-4">
                    <!-- Supplier Details -->
                    <?php if ($asset['supplier_id']): ?>
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Supplier Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label">Supplier Name</div>
                                <div><?php echo htmlspecialchars($asset['name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Contact Person</div>
                                <div><?php echo htmlspecialchars($asset['contact_person'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email</div>
                                <div><?php echo htmlspecialchars($asset['email'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone</div>
                                <div><?php echo htmlspecialchars($asset['phone'] ?: 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Activity Log -->
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Recent Activity</h6>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if ($logs->num_rows > 0): ?>
                                <?php while ($log = $logs->fetch_assoc()): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="small">
                                        <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                        <div class="text-muted"><?php echo htmlspecialchars($log['description']); ?></div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted small mb-0">No activity recorded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>