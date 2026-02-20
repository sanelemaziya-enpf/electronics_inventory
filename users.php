<?php
require_once 'config.php';
checkLogin();

if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $department = sanitize($_POST['department']);
    $floor = sanitize($_POST['floor']);
    $phone = sanitize($_POST['phone']);
    
    // Admin cannot create super_admin users
    if ($role === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        $message = "You don't have permission to create Super Admin users.";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, department, floor, phone, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $username, $email, $password, $full_name, $role, $department, $floor, $phone, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = "User added successfully!";
            $message_type = "success";
            logActivity($_SESSION['user_id'], 'CREATE', 'users', $conn->insert_id, "Added user: $username");
        } else {
            $message = "Error adding user: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $id = intval($_POST['id']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $department = sanitize($_POST['department']);
    $floor = sanitize($_POST['floor']);
    $phone = sanitize($_POST['phone']);
    $status = sanitize($_POST['status']);
    
    // Get user to check if it's super_admin
    $user_check = $conn->query("SELECT role FROM users WHERE id = $id")->fetch_assoc();
    
    // Admin cannot edit super_admin users
    if ($user_check['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        $message = "You don't have permission to edit Super Admin users.";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE users SET email=?, full_name=?, role=?, department=?, floor=?, phone=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $email, $full_name, $role, $department, $floor, $phone, $status, $id);
        
        if ($stmt->execute()) {
            $message = "User updated successfully!";
            $message_type = "success";
            logActivity($_SESSION['user_id'], 'UPDATE', 'users', $id, "Updated user: $full_name");
        } else {
            $message = "Error updating user: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Reset Password
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['user_id']);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $conn->query("UPDATE users SET password='$new_password' WHERE id=$id");
    $message = "Password reset successfully!";
    $message_type = "success";
    logActivity($_SESSION['user_id'], 'UPDATE', 'users', $id, "Reset password for user");
}

// Handle Delete User
if (isset($_GET['delete']) && $_SESSION['role'] === 'super_admin') {
    $id = intval($_GET['delete']);
    
    if ($id == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "danger";
    } else {
        $conn->query("DELETE FROM users WHERE id = $id");
        $message = "User deleted successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'DELETE', 'users', $id, "Deleted user");
    }
}

// Get all users
$where_clause = $_SESSION['role'] === 'admin' ? "WHERE u.role != 'super_admin'" : "";
$users = $conn->query("SELECT u.*, u2.full_name as created_by_name 
                       FROM users u 
                       LEFT JOIN users u2 ON u.created_by = u2.id 
                       $where_clause 
                       ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f1de2bff;
            --secondary-color: #a24b4bff;
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
        .role-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 12px;
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
            <a href="assignments.php"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php" class="active"><i class="fas fa-cogs"></i> Asset Models</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="users.php" class="active"><i class="fas fa-users"></i> IT Staff Users</a>
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
                    <h5 class="mb-0">IT Staff Management</h5>
                    <small class="text-muted">Manage system users and permissions</small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-2"></i>Add User
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

            <div class="card card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>IT Staff User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Floor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $role_colors = [
                                            'super_admin' => 'danger',
                                            'admin' => 'warning',
                                            'staff' => 'info'
                                        ];
                                        ?>
                                        <span class="role-badge bg-<?php echo $role_colors[$user['role']]; ?> text-white">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['department'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['floor'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] !== 'super_admin' || $_SESSION['role'] === 'super_admin'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="showResetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($_SESSION['role'] === 'super_admin' && $user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                    <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                    <option value="super_admin">Super Admin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" id="edit_username" class="form-control" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="edit_role" class="form-select" required>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                    <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                    <option value="super_admin">Super Admin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" id="edit_department" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" id="edit_floor" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Reset password for user: <strong id="reset_username"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_status').value = user.status;
            document.getElementById('edit_department').value = user.department || '';
            document.getElementById('edit_floor').value = user.floor || '';
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function showResetPassword(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
    </script>
</body>
</html>