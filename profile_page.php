<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $floor = sanitize($_POST['floor']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, department=?, floor=? WHERE id=?");
    $stmt->bind_param("sssssi", $full_name, $email, $phone, $department, $floor, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $message = "Profile updated successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'UPDATE', 'users', $_SESSION['user_id'], "Updated profile");
    } else {
        $message = "Error updating profile: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password from database
    $user = $conn->query("SELECT password FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
        $message = "Current password is incorrect!";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match!";
        $message_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters!";
        $message_type = "danger";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed_password' WHERE id={$_SESSION['user_id']}");
        $message = "Password changed successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'UPDATE', 'users', $_SESSION['user_id'], "Changed password");
    }
}

// Get user data
$user = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();

// Get user statistics
$user_stats = [
    'assets_created' => $conn->query("SELECT COUNT(*) as count FROM assets WHERE created_by = {$_SESSION['user_id']}")->fetch_assoc()['count'],
    'assignments' => $conn->query("SELECT COUNT(*) as count FROM asset_assignments WHERE assigned_to = {$_SESSION['user_id']} AND status='active'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
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
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin-bottom: 15px;
        }
        .stat-box {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
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
             <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php" class="active"><i class="fas fa-cogs"></i> Asset Models</a> 
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="import.php"><i class="fas fa-file-import"></i> Import Assets</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h5 class="mb-0">My Profile</h5>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-4">
                    <div class="card card-custom">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p class="mb-0">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <span class="badge bg-light text-dark mt-2">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Information</h6>
                            <div class="mb-3">
                                <small class="text-muted">Email</small>
                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Phone</small>
                                <div><?php echo htmlspecialchars($user['phone'] ?: 'Not set'); ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Department</small>
                                <div><?php echo htmlspecialchars($user['department'] ?: 'Not set'); ?></div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Floor</small>
                                <div><?php echo htmlspecialchars($user['floor'] ?: 'Not set'); ?></div>
                            </div>
                            <div class="mb-0">
                                <small class="text-muted">Member Since</small>
                                <div><?php echo formatDate($user['created_at']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card card-custom mt-4">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>My Statistics</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-box">
                                        <h3 class="mb-0"><?php echo $user_stats['assets_created']; ?></h3>
                                        <small class="text-muted">Assets Created</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <h3 class="mb-0"><?php echo $user_stats['assignments']; ?></h3>
                                        <small class="text-muted">Active Assignments</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Forms -->
                <div class="col-lg-8">
                    <!-- Edit Profile -->
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($user['department']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Floor</label>
                                        <input type="text" name="floor" class="form-control" value="<?php echo htmlspecialchars($user['floor']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="change_password" class="btn btn-warning">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>