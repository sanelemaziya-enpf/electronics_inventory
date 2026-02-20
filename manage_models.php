<?php
require_once 'config.php';
checkLogin();

if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Add Model
if (isset($_POST['add_model'])) {
    $category_id = intval($_POST['category_id']);
    $model_name = sanitize($_POST['model_name']);
    $manufacturer = sanitize($_POST['manufacturer']);
    
    $stmt = $conn->prepare("INSERT INTO asset_models (category_id, model_name, manufacturer, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $category_id, $model_name, $manufacturer, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = "Model added successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'CREATE', 'asset_models', $conn->insert_id, "Added model: $model_name");
    } else {
        $message = "Error adding model: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Handle Delete Model
if (isset($_GET['delete']) && $_SESSION['role'] === 'super_admin') {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM asset_models WHERE id = $id");
    $message = "Model deleted successfully!";
    $message_type = "success";
    logActivity($_SESSION['user_id'], 'DELETE', 'asset_models', $id, "Deleted model");
}

// Get all models with category names
$models = $conn->query("SELECT am.*, c.name as category_name, u.full_name as created_by_name
                       FROM asset_models am
                       LEFT JOIN categories c ON am.category_id = c.id
                       LEFT JOIN users u ON am.created_by = u.id
                       ORDER BY c.name, am.manufacturer, am.model_name");

// Get categories for the form
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Models - <?php echo APP_NAME; ?></title>
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
            <a href="manage_models.php"><i class="fas fa-cogs"></i> Asset Models</a>
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="users.php"><i class="fas fa-user-cog"></i> User Management</a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="import.php"><i class="fas fa-file-import"></i> Import Assets</a>
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
                    <h5 class="mb-0">Manage Asset Models</h5>
                    <small class="text-muted">Manage models for different asset categories</small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModelModal">
                    <i class="fas fa-plus me-2"></i>Add Model
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
                                    <th>Category</th>
                                    <th>Manufacturer</th>
                                    <th>Model Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($models->num_rows > 0): ?>
                                    <?php while ($model = $models->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($model['category_name']); ?></span></td>
                                        <td><?php echo htmlspecialchars($model['manufacturer']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($model['model_name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $model['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($model['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                            <a href="?delete=<?php echo $model['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this model?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="fas fa-cogs fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No models yet. Add your first model!</p>
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

    <!-- Add Model Modal -->
    <div class="modal fade" id="addModelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Model</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manufacturer <span class="text-danger">*</span></label>
                            <input type="text" name="manufacturer" class="form-control" placeholder="e.g., Dell, HP, Lenovo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model Name <span class="text-danger">*</span></label>
                            <input type="text" name="model_name" class="form-control" placeholder="e.g., Latitude 7420" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_model" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Model
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>