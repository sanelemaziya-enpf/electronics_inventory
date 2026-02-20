<?php
require_once 'config.php';
checkLogin();

if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Add Supplier
if (isset($_POST['add_supplier'])) {
    $name = sanitize($_POST['name']);
    $contact_person = sanitize($_POST['contact_person']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $website = sanitize($_POST['website']);
    $notes = sanitize($_POST['notes']);
    
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, website, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $name, $contact_person, $email, $phone, $address, $website, $notes, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = "Supplier added successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'CREATE', 'suppliers', $conn->insert_id, "Added supplier: $name");
    } else {
        $message = "Error adding supplier: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Handle Edit Supplier
if (isset($_POST['edit_supplier'])) {
    $id = intval($_POST['id']);
    $name = sanitize($_POST['name']);
    $contact_person = sanitize($_POST['contact_person']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $website = sanitize($_POST['website']);
    $notes = sanitize($_POST['notes']);
    $status = sanitize($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, website=?, notes=?, status=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $name, $contact_person, $email, $phone, $address, $website, $notes, $status, $id);
    
    if ($stmt->execute()) {
        $message = "Supplier updated successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'UPDATE', 'suppliers', $id, "Updated supplier: $name");
    } else {
        $message = "Error updating supplier: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Handle Delete Supplier
if (isset($_GET['delete']) && $_SESSION['role'] === 'super_admin') {
    $id = intval($_GET['delete']);
    
    // Check if supplier has assets
    $check = $conn->query("SELECT COUNT(*) as count FROM assets WHERE supplier_id = $id");
    $count = $check->fetch_assoc()['count'];
    
    if ($count > 0) {
        $message = "Cannot delete supplier. It has $count associated assets.";
        $message_type = "danger";
    } else {
        $conn->query("DELETE FROM suppliers WHERE id = $id");
        $message = "Supplier deleted successfully!";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'DELETE', 'suppliers', $id, "Deleted supplier");
    }
}

// Get all suppliers
$suppliers = $conn->query("SELECT s.*, COUNT(a.id) as asset_count, u.full_name as created_by_name
                          FROM suppliers s
                          LEFT JOIN assets a ON s.id = a.supplier_id
                          LEFT JOIN users u ON s.created_by = u.id
                          GROUP BY s.id
                          ORDER BY s.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - <?php echo APP_NAME; ?></title>
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
            <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h4>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="assets.php"><i class="fas fa-box"></i> Assets</a>
            <a href="assignments.php"><i class="fas fa-exchange-alt"></i> Assignments</a>
            <a href="staff.php"><i class="fas fa-users"></i> Staff Directory</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php" class="active"><i class="fas fa-cogs"></i> Asset Models</a>
            <a href="suppliers.php" class="active"><i class="fas fa-truck"></i> Suppliers</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
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
                    <h5 class="mb-0">Suppliers Management</h5>
                    <small class="text-muted">Manage your asset suppliers</small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus me-2"></i>Add Supplier
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
                                    <th>Supplier Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Assets</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($suppliers->num_rows > 0): ?>
                                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                            <?php if ($supplier['website']): ?>
                                            <br><small class="text-muted">
                                                <a href="<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank" class="text-decoration-none">
                                                    <i class="fas fa-globe me-1"></i><?php echo htmlspecialchars($supplier['website']); ?>
                                                </a>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($supplier['email']): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($supplier['email']); ?>
                                            </a>
                                            <?php else: ?>
                                            N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($supplier['phone'] ?: 'N/A'); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $supplier['asset_count']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $supplier['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($supplier['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick='editSupplier(<?php echo json_encode($supplier); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($_SESSION['role'] === 'super_admin' && $supplier['asset_count'] == 0): ?>
                                            <a href="?delete=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No suppliers yet. Add your first supplier!</p>
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

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Supplier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" placeholder="https://">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_supplier" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Supplier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" id="edit_website" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_supplier" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSupplier(supplier) {
            document.getElementById('edit_id').value = supplier.id;
            document.getElementById('edit_name').value = supplier.name;
            document.getElementById('edit_contact_person').value = supplier.contact_person || '';
            document.getElementById('edit_email').value = supplier.email || '';
            document.getElementById('edit_phone').value = supplier.phone || '';
            document.getElementById('edit_website').value = supplier.website || '';
            document.getElementById('edit_address').value = supplier.address || '';
            document.getElementById('edit_notes').value = supplier.notes || '';
            document.getElementById('edit_status').value = supplier.status;
            new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
        }
    </script>
</body>
</html>