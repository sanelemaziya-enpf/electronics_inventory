<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

// Handle New Assignment
if (isset($_POST['assign_asset'])) {
    $asset_id = intval($_POST['asset_id']);
    $assigned_to_type = sanitize($_POST['assigned_to_type']); // 'user' or 'staff'
    
    // Get the correct ID based on type
    if ($assigned_to_type === 'staff') {
        $assigned_to_id = intval($_POST['staff_assigned_to_id']);
    } else {
        $assigned_to_id = intval($_POST['user_assigned_to_id']);
    }
    
    $assigned_date = sanitize($_POST['assigned_date']);
    $notes = sanitize($_POST['notes']);
    
    // Check if asset is available
    $asset = $conn->query("SELECT status, name FROM assets WHERE id = $asset_id")->fetch_assoc();
    
    if ($asset['status'] !== 'available') {
        $message = "Asset is not available for assignment.";
        $message_type = "danger";
    } else {
        // Create assignment
        if ($assigned_to_type === 'staff') {
            $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, assigned_to_type, staff_id, assigned_by, assigned_date, notes) VALUES (?, 'staff', ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $asset_id, $assigned_to_id, $_SESSION['user_id'], $assigned_date, $notes);
        } else {
            $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, assigned_to_type, assigned_to, assigned_by, assigned_date, notes) VALUES (?, 'user', ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $asset_id, $assigned_to_id, $_SESSION['user_id'], $assigned_date, $notes);
        }
        
        if ($stmt->execute()) {
            // Update asset status
            $conn->query("UPDATE assets SET status='assigned' WHERE id=$asset_id");
            
            $message = "Asset assigned successfully!";
            $message_type = "success";
            logActivity($_SESSION['user_id'], 'CREATE', 'asset_assignments', $conn->insert_id, "Assigned asset: {$asset['name']}");
        } else {
            $message = "Error assigning asset: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Return Asset
if (isset($_POST['return_asset'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $asset_id = intval($_POST['asset_id']);
    $return_date = date('Y-m-d');
    
    // Update assignment
    $conn->query("UPDATE asset_assignments SET status='returned', return_date='$return_date' WHERE id=$assignment_id");
    
    // Update asset status to available
    $conn->query("UPDATE assets SET status='available' WHERE id=$asset_id");
    
    $message = "Asset returned successfully!";
    $message_type = "success";
    logActivity($_SESSION['user_id'], 'UPDATE', 'asset_assignments', $assignment_id, "Returned asset");
}

// Get all assignments
$assignments = $conn->query("SELECT aa.*, a.asset_tag, a.name as asset_name, a.model,
                            CASE 
                                WHEN aa.assigned_to_type = 'staff' THEN s.full_name
                                ELSE u1.full_name
                            END as assigned_to_name,
                            CASE 
                                WHEN aa.assigned_to_type = 'staff' THEN s.department
                                ELSE u1.department
                            END as department,
                            CASE 
                                WHEN aa.assigned_to_type = 'staff' THEN s.floor
                                ELSE u1.floor
                            END as floor,
                            u2.full_name as assigned_by_name
                            FROM asset_assignments aa
                            JOIN assets a ON aa.asset_id = a.id
                            LEFT JOIN users u1 ON aa.assigned_to = u1.id
                            LEFT JOIN staff s ON aa.staff_id = s.id
                            LEFT JOIN users u2 ON aa.assigned_by = u2.id
                            ORDER BY aa.created_at DESC");

// Get available assets for assignment (including serial numbers)
$available_assets = $conn->query("SELECT id, asset_tag, serial_number, name, model FROM assets WHERE status='available' ORDER BY name");

// Get active system users
$active_users = $conn->query("SELECT id, full_name, department, floor FROM users WHERE status='active' ORDER BY full_name");

// Get active staff members
$active_staff = $conn->query("SELECT id, staff_id, full_name, department, floor, position FROM staff WHERE status='active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - <?php echo APP_NAME; ?></title>
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
        .toggle-view-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .toggle-view-btn:hover {
            background: #e9ecef;
        }
        .toggle-view-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .toggle-view-btn i {
            font-size: 14px;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-left: 40px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .no-results-message {
            display: none;
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-results-message i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="assets.php"><i class="fas fa-box"></i> Assets</a>
            <a href="assignments.php" class="active"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php"><i class="fas fa-cogs"></i> Asset Models</a>  
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="import.php"><i class="fas fa-file-import"></i> Import Assets</a>
            <a href="inspections.php"><i class="fas fa-clipboard-check"></i> Inspections</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Asset Assignments</h5>
                    <small class="text-muted">Manage asset assignments to staff</small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignAssetModal">
                    <i class="fas fa-plus me-2"></i>New Assignment
                </button>
            </div>
        </div>

        <div class="content-area">

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="staffSearchInput" class="form-control" 
                                       placeholder="Search by staff name...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" onchange="filterAssignments(this.value)">
                                <option value="all">All Assignments</option>
                                <option value="active">Active</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="assignmentsTable">
                            <thead>
                                <tr>
                                  <!--  <th>Asset Tag</th>   --> 
                                    <th>Asset Name</th>                                  
                                    <th>Staff Name</th>
                                    <th>Assigned To</th>
                                    <th>Department</th>
                                    <th>Floor</th>
                                    <th>Assigned Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                                <tr data-status="<?php echo $assignment['status']; ?>" 
                                    data-staff-name="<?php echo strtolower(htmlspecialchars($assignment['assigned_to_name'])); ?>">
                             <!--       <td><?php echo htmlspecialchars($assignment['asset_tag']); ?></td> -->
                                    <td><?php echo htmlspecialchars($assignment['asset_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['assigned_to_name']); ?></td>
                                    <td>
                                        <?php if ($assignment['assigned_to_type'] === 'staff'): ?>
                                            <span class="badge bg-info">Staff</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">System User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['department'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['floor'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($assignment['assigned_date'])); ?></td>
                                    <td>
                                        <?php if ($assignment['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($assignment['status'] === 'active'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm asset return?');">
                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                            <input type="hidden" name="asset_id" value="<?php echo $assignment['asset_id']; ?>">
                                            <button type="submit" name="return_asset" class="btn btn-sm btn-warning">
                                                <i class="fas fa-undo me-1"></i>Return
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <div class="no-results-message" id="noResultsMessage">
                            <i class="fas fa-search"></i>
                            <h5>No assignments found</h5>
                            <p>Try adjusting your search criteria</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Asset Modal -->
    <div class="modal fade" id="assignAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Assign Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Select Asset <span class="text-danger">*</span></label>
                                    <button type="button" class="toggle-view-btn" id="toggleViewBtn" onclick="toggleAssetView()">
                                        <i class="fas fa-exchange-alt"></i>
                                        <span id="toggleText">Show by Serial Number</span>
                                    </button>
                                </div>
                                <select name="asset_id" id="assetSelect" class="form-select" required>
                                    <option value="">Choose an asset...</option>
                                    <?php while ($asset = $available_assets->fetch_assoc()): ?>
                                    <option value="<?php echo $asset['id']; ?>"
                                            data-asset-tag="<?php echo htmlspecialchars($asset['asset_tag']); ?>"
                                            data-serial="<?php echo htmlspecialchars($asset['serial_number']); ?>"
                                            data-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                            data-model="<?php echo htmlspecialchars($asset['model']); ?>">
                                        <?php echo htmlspecialchars($asset['asset_tag']); ?> - 
                                        <?php echo htmlspecialchars($asset['name']); ?>
                                        <?php echo $asset['model'] ? ' (' . htmlspecialchars($asset['model']) . ')' : ''; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Assign To <span class="text-danger">*</span></label>
                                <select name="assigned_to_type" id="assignToType" class="form-select mb-2" required>
                                    <option value="">Choose type...</option>
                                    <option value="staff">Staff Member (Company Employee)</option>
                                    <option value="user">System User (Admin/Staff with login)</option>
                                </select>
                                
                                <div id="staffSelect" style="display:none;">
                                    <select name="staff_assigned_to_id" id="staffSelectBox" class="form-select" disabled>
                                        <option value="">Choose staff member...</option>
                                        <?php 
                                        $active_staff->data_seek(0);
                                        while ($staff = $active_staff->fetch_assoc()): 
                                        ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                            (<?php echo htmlspecialchars($staff['staff_id']); ?>)
                                            <?php if ($staff['position']): ?>
                                            - <?php echo htmlspecialchars($staff['position']); ?>
                                            <?php endif; ?>
                                            <?php if ($staff['department']): ?>
                                            - <?php echo htmlspecialchars($staff['department']); ?>
                                            <?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div id="userSelect" style="display:none;">
                                    <select name="user_assigned_to_id" id="userSelectBox" class="form-select" disabled>
                                        <option value="">Choose system user...</option>
                                        <?php 
                                        $active_users->data_seek(0);
                                        while ($user = $active_users->fetch_assoc()): 
                                        ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                            <?php if ($user['department']): ?>
                                            - <?php echo htmlspecialchars($user['department']); ?>
                                            <?php endif; ?>
                                            <?php if ($user['floor']): ?>
                                            (<?php echo htmlspecialchars($user['floor']); ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                <input type="date" name="assigned_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes about this assignment..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_asset" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Assign Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let showingSerialNumber = false;
        
        // Toggle between Asset Tag and Serial Number view
        function toggleAssetView() {
            const select = document.getElementById('assetSelect');
            const toggleBtn = document.getElementById('toggleViewBtn');
            const toggleText = document.getElementById('toggleText');
            const currentValue = select.value; // Remember selected value
            
            showingSerialNumber = !showingSerialNumber;
            
            // Update all options
            Array.from(select.options).forEach(option => {
                if (option.value === "") return; // Skip placeholder
                
                const assetTag = option.dataset.assetTag;
                const serial = option.dataset.serial;
                const name = option.dataset.name;
                const model = option.dataset.model;
                
                if (showingSerialNumber) {
                    // Show by Serial Number
                    if (serial) {
                        option.textContent = `${serial} - ${name}${model ? ' (' + model + ')' : ''}`;
                    } else {
                        option.textContent = `No Serial - ${name}${model ? ' (' + model + ')' : ''}`;
                    }
                } else {
                    // Show by Asset Tag (default)
                    option.textContent = `${assetTag} - ${name}${model ? ' (' + model + ')' : ''}`;
                }
            });
            
            // Restore selected value
            select.value = currentValue;
            
            // Update button text and style
            if (showingSerialNumber) {
                toggleText.textContent = 'Show by Asset Tag';
                toggleBtn.classList.add('active');
            } else {
                toggleText.textContent = 'Show by Serial Number';
                toggleBtn.classList.remove('active');
            }
        }
        
        // Show/hide assignment type dropdowns
        document.getElementById('assignToType').addEventListener('change', function() {
            const staffSelect = document.getElementById('staffSelect');
            const userSelect = document.getElementById('userSelect');
            const staffSelectBox = document.getElementById('staffSelectBox');
            const userSelectBox = document.getElementById('userSelectBox');
            
            // Hide both and disable
            staffSelect.style.display = 'none';
            userSelect.style.display = 'none';
            staffSelectBox.disabled = true;
            userSelectBox.disabled = true;
            
            // Clear required attribute from both
            staffSelectBox.removeAttribute('required');
            userSelectBox.removeAttribute('required');
            
            if (this.value === 'staff') {
                staffSelect.style.display = 'block';
                staffSelectBox.disabled = false;
                staffSelectBox.setAttribute('required', 'required');
            } else if (this.value === 'user') {
                userSelect.style.display = 'block';
                userSelectBox.disabled = false;
                userSelectBox.setAttribute('required', 'required');
            }
        });
        
        // Staff name search functionality
        document.getElementById('staffSearchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#assignmentsTable tbody tr');
            const noResultsMessage = document.getElementById('noResultsMessage');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const staffName = row.dataset.staffName;
                
                if (staffName.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        });
        
        // Filter assignments by status
        function filterAssignments(status) {
            const rows = document.querySelectorAll('#assignmentsTable tbody tr');
            const searchTerm = document.getElementById('staffSearchInput').value.toLowerCase().trim();
            const noResultsMessage = document.getElementById('noResultsMessage');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowStatus = row.dataset.status;
                const staffName = row.dataset.staffName;
                let showRow = false;
                
                // Check status filter
                if (status === 'all' || rowStatus === status) {
                    // Check search filter
                    if (searchTerm === '' || staffName.includes(searchTerm)) {
                        showRow = true;
                    }
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }
    </script>
</body>
</html>