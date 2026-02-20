<?php
require_once 'config.php';
checkLogin();

if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

// Handle Delete (Super Admin Only)
if (isset($_POST['delete']) && $_SESSION['role'] === 'super_admin') {
    $conn->query("DELETE FROM asset_assignments WHERE asset_id = $asset_id");
    $conn->query("DELETE FROM assets WHERE id = $asset_id");
    logActivity($_SESSION['user_id'], 'DELETE', 'assets', $asset_id, "Deleted asset");
    header("Location: assets.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $name = sanitize($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $model = sanitize($_POST['model']);
    $serial_number = sanitize($_POST['serial_number']);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $purchase_date = !empty($_POST['purchase_date']) ? sanitize($_POST['purchase_date']) : null;
    $purchase_cost = !empty($_POST['purchase_cost']) ? floatval($_POST['purchase_cost']) : null;
    $warranty_expiry = !empty($_POST['warranty_expiry']) ? sanitize($_POST['warranty_expiry']) : null;
    $status = sanitize($_POST['status']);
    $condition_status = sanitize($_POST['condition_status']);
    $location = sanitize($_POST['location']);
    $floor = sanitize($_POST['floor']);
    $department = sanitize($_POST['department']);
    $description = sanitize($_POST['description']);
    $specifications = sanitize($_POST['specifications']);
    $notes = sanitize($_POST['notes']);
    
    $stmt = $conn->prepare("UPDATE assets SET name=?, category_id=?, model=?, serial_number=?, supplier_id=?, 
                           purchase_date=?, purchase_cost=?, warranty_expiry=?, status=?, condition_status=?,
                           location=?, floor=?, department=?, description=?, specifications=?, notes=?
                           WHERE id=?");
    $stmt->bind_param("sississsssssssssi", $name, $category_id, $model, $serial_number, $supplier_id,
                     $purchase_date, $purchase_cost, $warranty_expiry, $status, $condition_status,
                     $location, $floor, $department, $description, $specifications, $notes, $asset_id);
    
    if ($stmt->execute()) {
        $message = "Asset updated successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'UPDATE', 'assets', $asset_id, "Updated asset: $name");
    } else {
        $message = "Error updating asset: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Get asset data
$asset_query = "SELECT * FROM assets WHERE id = ?";
$stmt = $conn->prepare($asset_query);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: assets.php");
    exit();
}

$asset = $result->fetch_assoc();

// Get categories and suppliers
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$suppliers = $conn->query("SELECT * FROM suppliers WHERE status='active' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Asset - <?php echo APP_NAME; ?></title>
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
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
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
                    <h5 class="mb-0">Edit Asset</h5>
                    <small class="text-muted"><?php echo htmlspecialchars($asset['asset_tag']); ?></small>
                </div>
                <a href="asset_detail.php?id=<?php echo $asset_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Details
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
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Asset Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Asset Tag</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($asset['asset_tag']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Asset Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($asset['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category *</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $asset['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Model</label>
                                        <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($asset['model']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Serial Number</label>
                                        <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($asset['serial_number']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <select name="supplier_id" class="form-select">
                                            <option value="">Select Supplier</option>
                                            <?php while ($sup = $suppliers->fetch_assoc()): ?>
                                            <option value="<?php echo $sup['id']; ?>" <?php echo $asset['supplier_id'] == $sup['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sup['name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-control" value="<?php echo $asset['purchase_date']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Cost (E)</label>
                                        <input type="number" step="0.01" name="purchase_cost" class="form-control" value="<?php echo $asset['purchase_cost']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Warranty Expiry</label>
                                        <input type="date" name="warranty_expiry" class="form-control" value="<?php echo $asset['warranty_expiry']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status *</label>
                                        <select name="status" class="form-select" required>
                                            <option value="available" <?php echo $asset['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="assigned" <?php echo $asset['status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                            <option value="maintenance" <?php echo $asset['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="retired" <?php echo $asset['status'] == 'retired' ? 'selected' : ''; ?>>Retired</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Condition *</label>
                                        <select name="condition_status" class="form-select" required>
                                            <option value="excellent" <?php echo $asset['condition_status'] == 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                                            <option value="good" <?php echo $asset['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                                            <option value="fair" <?php echo $asset['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                            <option value="poor" <?php echo $asset['condition_status'] == 'poor' ? 'selected' : ''; ?>>Poor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($asset['location']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Floor</label>
                                        <input type="text" name="floor" class="form-control" value="<?php echo htmlspecialchars($asset['floor']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($asset['department']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($asset['description']); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Specifications</label>
                                        <textarea name="specifications" class="form-control" rows="3"><?php echo htmlspecialchars($asset['specifications']); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($asset['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card card-custom">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Actions</h6>
                            </div>
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-save me-2"></i>Update Asset
                                </button>
                                <a href="asset_detail.php?id=<?php echo $asset_id; ?>" class="btn btn-outline-secondary w-100 mb-3">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                
                                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                <hr>
                                <div class="alert alert-danger small mb-0">
                                    <strong><i class="fas fa-exclamation-triangle me-1"></i>Danger Zone</strong>
                                    <p class="mb-2 mt-2">Deleting this asset is permanent and cannot be undone.</p>
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm w-100" 
                                            onclick="return confirm('Are you sure you want to delete this asset? This action cannot be undone!');">
                                        <i class="fas fa-trash me-2"></i>Delete Asset
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>