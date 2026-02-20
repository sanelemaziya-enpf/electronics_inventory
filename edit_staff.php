<?php
require_once 'config.php';
checkLogin();

// Only admin and super_admin can edit staff
if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// Get staff member details
$staff = $conn->query("SELECT * FROM staff WHERE id = $staff_id")->fetch_assoc();

if (!$staff) {
    header("Location: staff.php");
    exit();
}

// Handle Update Staff Member
if (isset($_POST['update_staff'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $employee_id = sanitize($_POST['employee_id']);
    $position = sanitize($_POST['position']);
    $department = sanitize($_POST['department']);
    $floor = sanitize($_POST['floor']);
    $date_joined = !empty($_POST['date_joined']) ? sanitize($_POST['date_joined']) : date('Y-m-d');
    $notes = sanitize($_POST['notes']);
    
    // Check if email already exists (excluding current staff)
    $check_email = $conn->query("SELECT id FROM staff WHERE email = '$email' AND id != $staff_id");
    if ($check_email->num_rows > 0) {
        $message = "Email already exists! Please use a different email.";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE staff SET full_name=?, email=?, phone=?, employee_id=?, position=?, department=?, floor=?, date_joined=?, notes=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $full_name, $email, $phone, $employee_id, $position, $department, $floor, $date_joined, $notes, $staff_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'UPDATE', 'staff', $staff_id, "Updated staff member: $full_name");
            
            // Redirect with success
            header("Location: edit_staff.php?id=$staff_id&success=1");
            exit();
        } else {
            $message = "Error updating staff member: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Handle success redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Staff member <strong>{$staff['full_name']}</strong> updated successfully!";
    $message_type = "success";
    // Refresh staff data
    $staff = $conn->query("SELECT * FROM staff WHERE id = $staff_id")->fetch_assoc();
}

// Get existing departments and floors for suggestions
$departments = $conn->query("SELECT DISTINCT department FROM staff WHERE department IS NOT NULL AND department != '' ORDER BY department");
$floors = $conn->query("SELECT DISTINCT floor FROM staff WHERE floor IS NOT NULL AND floor != '' ORDER BY floor");

$department_options = ['Finance', 'Corporate', 'Operations', 'CEO', 'Investments'];
$floor_options = ['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor', '5th Floor'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff Member - <?php echo APP_NAME; ?></title>
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
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
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
            <a href="staff.php" class="active"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="users.php"><i class="fas fa-user-cog"></i> User Management</a>
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
                    <h5 class="mb-0">Edit Staff Member</h5>
                    <small class="text-muted">Update staff member information</small>
                </div>
                <a href="staff.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Staff Directory
                </a>
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

            <form method="POST" action="">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Staff Information -->
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-info-circle me-2"></i>Staff Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info small mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Staff ID:</strong> <?php echo htmlspecialchars($staff['staff_id']); ?> (Cannot be changed)
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($staff['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($staff['phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date Joined</label>
                                        <input type="date" name="date_joined" class="form-control" value="<?php echo $staff['date_joined']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Details -->
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-id-card me-2"></i>Employment Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($staff['employee_id']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position/Title</label>
                                        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($staff['position']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Department & Location -->
                        <div class="card card-custom">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-building me-2"></i>Department & Location</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Department <span class="text-danger">*</span></label>
                                        <select name="department" class="form-select" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($department_options as $dept): ?>
                                            <option value="<?php echo $dept; ?>" <?php echo $staff['department'] == $dept ? 'selected' : ''; ?>>
                                                <?php echo $dept; ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <?php 
                                            $departments->data_seek(0);
                                            while ($dept = $departments->fetch_assoc()): 
                                                $dept_name = $dept['department'];
                                                if (!in_array($dept_name, $department_options)): 
                                            ?>
                                            <option value="<?php echo htmlspecialchars($dept_name); ?>" <?php echo $staff['department'] == $dept_name ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept_name); ?>
                                            </option>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Floor <span class="text-danger">*</span></label>
                                        <select name="floor" class="form-select" required>
                                            <option value="">Select Floor</option>
                                            <?php foreach ($floor_options as $floor_opt): ?>
                                            <option value="<?php echo $floor_opt; ?>" <?php echo $staff['floor'] == $floor_opt ? 'selected' : ''; ?>>
                                                <?php echo $floor_opt; ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <?php 
                                            $floors->data_seek(0);
                                            while ($floor = $floors->fetch_assoc()): 
                                                $floor_name = $floor['floor'];
                                                if (!in_array($floor_name, $floor_options)): 
                                            ?>
                                            <option value="<?php echo htmlspecialchars($floor_name); ?>" <?php echo $staff['floor'] == $floor_name ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($floor_name); ?>
                                            </option>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Additional Notes</label>
                                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($staff['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Sidebar -->
                    <div class="col-lg-4">
                        <div class="card card-custom sticky-top" style="top: 20px;">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><i class="fas fa-save me-2"></i>Update Staff Member</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-box mb-3">
                                    <h6><i class="fas fa-info-circle me-2"></i>Information</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>All fields marked with <span class="text-danger">*</span> are required</li>
                                        <li>Staff ID cannot be modified</li>
                                        <li>Email must be unique</li>
                                        <li>Changes are logged for audit purposes</li>
                                    </ul>
                                </div>
                                
                                <button type="submit" name="update_staff" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-save me-2"></i>Update Staff Member
                                </button>
                                <a href="staff.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                
                                <?php if (hasPermission('super_admin')): ?>
                                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-2"></i>Delete Staff Member
                                </button>
                                <?php endif; ?>

                                <hr class="my-3">
                                
                                <div class="small text-muted">
                                    <p class="mb-2"><i class="fas fa-user me-1"></i> <strong>Editing as:</strong><br><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                    <p class="mb-0"><i class="fas fa-calendar me-1"></i> <strong>Created:</strong><br><?php echo date('d M Y', strtotime($staff['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Are you sure you want to delete <strong><?php echo htmlspecialchars($staff['full_name']); ?></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All assignment history will be preserved but this staff member will no longer be available for new assignments.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="delete_staff.php?id=<?php echo $staff_id; ?>" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Permanently
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>