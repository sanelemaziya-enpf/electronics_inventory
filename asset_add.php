<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag = generateAssetTag();
    $asset_number = sanitize($_POST['asset_number']);
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
    $section = sanitize($_POST['section']);
    $description = sanitize($_POST['description']);
    $specifications = sanitize($_POST['specifications']);
    $notes = sanitize($_POST['notes']);
    
    $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_number, name, category_id, model, serial_number, supplier_id, 
                           purchase_date, purchase_cost, warranty_expiry, status, condition_status,
                           location, floor, department, section, description, specifications, notes, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssississdsssssssssi", $asset_tag, $asset_number, $name, $category_id, $model, $serial_number, $supplier_id,
                 $purchase_date, $purchase_cost, $warranty_expiry, $status, $condition_status,
                 $location, $floor, $department, $section, $description, $specifications, $notes, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $asset_id = $conn->insert_id;
        $message = "Asset added successfully! Asset Tag: <strong>$asset_tag</strong>";
        $message_type = "success";
        logActivity($_SESSION['user_id'], 'CREATE', 'assets', $asset_id, "Added new asset: $name");
        
        // Redirect to asset detail page
        header("Location: asset_detail.php?id=$asset_id");
        exit();
    } else {
        $message = "Error adding asset: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Get categories and suppliers
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$suppliers = $conn->query("SELECT * FROM suppliers WHERE status='active' ORDER BY name");

// Get floors
$floors = $conn->query("SELECT DISTINCT floor FROM assets WHERE floor IS NOT NULL AND floor != '' ORDER BY floor");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Asset - <?php echo APP_NAME; ?></title>
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
                    <h5 class="mb-0">Add New Asset</h5>
                    <small class="text-muted">Create a new asset record</small>
                </div>
                <a href="assets.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Assets
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
                <!-- Hidden fields - Auto-generated by system -->
                <input type="hidden" name="asset_tag" id="asset_tag">

                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                   <!-- <div class="col-md-6">
                                        <label class="form-label">Asset Number <span class="text-danger">*</span></label>
                                        <input type="text" name="asset_number" class="form-control" placeholder="e.g., AST-001" required>
                                        <small class="text-muted">Your internal asset number</small>
                                    </div>-->
                                    <div class="col-md-6">
                                        <label class="form-label">Asset ID</label>
                                        <input type="text" name="name" class="form-control" placeholder="e.g., Dell Laptop Core i7">
                                        <small class="text-muted">Asset identification/description</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select name="category_id" id="category_id" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Model <span class="text-danger">*</span></label>
                                        <select name="model" id="model" class="form-select" required disabled>
                                            <option value="">Select category first</option>
                                        </select>
                                        <small class="text-muted">Select category to load models</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Serial Number</label>
                                        <input type="text" name="serial_number" class="form-control" placeholder="e.g., SN123456789">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Information -->
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-shopping-cart me-2"></i>Purchase Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <select name="supplier_id" class="form-select">
                                            <option value="">Select Supplier</option>
                                            <?php while ($sup = $suppliers->fetch_assoc()): ?>
                                            <option value="<?php echo $sup['id']; ?>">
                                                <?php echo htmlspecialchars($sup['name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Cost (E)</label>
                                        <input type="number" step="0.01" name="purchase_cost" class="form-control" placeholder="0.00">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Warranty Expiry Date</label>
                                        <input type="date" name="warranty_expiry" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location & Status -->
                        <div class="card card-custom mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-map-marker-alt me-2"></i>Location & Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="available" selected>Available</option>
                                            <option value="assigned">Assigned</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="retired">Retired</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Condition <span class="text-danger">*</span></label>
                                        <select name="condition_status" class="form-select" required>
                                            <option value="excellent">Excellent</option>
                                            <option value="good" selected>Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="poor">Poor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Location <span class="text-danger">*</span></label>
                                        <select name="location" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <option value="Pool">Pool</option>
                                            <option value="Out of Service">Out of Service</option>
                                            <option value="Faulty">Faulty</option>
                                             <option value="Faulty"></option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Floor <span class="text-danger">*</span></label>
                                        <select name="floor" class="form-select" required>
                                            <option value="">Select Floor</option>
                                            <option value="Ground Floor">Ground Floor</option>
                                            <option value="1st Floor">1st Floor</option>
                                            <option value="2nd Floor">2nd Floor</option>
                                            <option value="3rd Floor">3rd Floor</option>
                                            <option value="4th Floor">4th Floor</option>
                                            <option value="5th Floor">5th Floor</option>
                                            <?php while ($floor = $floors->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($floor['floor']); ?>">
                                                <?php echo htmlspecialchars($floor['floor']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Department <span class="text-danger">*</span></label>
                                        <select name="department" id="department" class="form-select" required>
                                            <option value="">Select Department</option>
                                            <option value="Finance">Finance</option>
                                            <option value="Corporate">Corporate</option>
                                           <!-- <option value="IT Department">IT Department</option> -->
                                            <option value="Operations">Operations</option>
                                            <option value="CEO">CEO</option>
                                            <option value="Investments">Investments</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Section <span class="text-danger">*</span></label>
                                        <select name="section" id="section" class="form-select" required disabled>
                                            <option value="">Select department first</option>
                                        </select>
                                        <small class="text-muted">Select department to load sections</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="card card-custom">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0 section-title"><i class="fas fa-clipboard me-2"></i>Additional Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the asset..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Technical Specifications</label>
                                        <textarea name="specifications" class="form-control" rows="3" placeholder="CPU, RAM, Storage, etc..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Sidebar -->
                    <div class="col-lg-4">
                        <div class="card card-custom sticky-top" style="top: 20px;">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><i class="fas fa-save me-2"></i>Save Asset</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info small mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Auto-generated:</strong> Asset tag will be automatically created upon saving.
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-plus me-2"></i>Add Asset
                                </button>
                                <a href="assets.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>

                                <hr class="my-3">
                                
                                <div class="small text-muted">
                                    <p class="mb-2"><i class="fas fa-user me-1"></i> <strong>Created by:</strong><br><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                    <p class="mb-0"><i class="fas fa-calendar me-1"></i> <strong>Date:</strong><br><?php echo date('d M Y, h:i A'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Tips -->
                        <div class="card card-custom mt-3">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Quick Tips</h6>
                            </div>
                            <div class="card-body">
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-2">Fill in all required fields marked with <span class="text-danger">*</span></li>
                                    <li class="mb-2">Serial numbers help in warranty claims</li>
                                    <li class="mb-2">Set warranty expiry to get reminders</li>
                                    <li class="mb-0">Detailed specifications help in asset tracking</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Department-Section mapping
        const departmentSections = {
            'Finance': ['Accounts', 'IT', 'Procurement'],
            'Corporate': ['HR', 'Marketing'],
            
            'Operations': ['Service centre', 'Compliance & Recon' ],
            'CEO': ['CEO'],
            'Investments': ['Investments'],

        };

        // Load sections based on department
        document.getElementById('department').addEventListener('change', function() {
            const department = this.value;
            const sectionSelect = document.getElementById('section');
            
            // Clear existing options
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (department && departmentSections[department]) {
                sectionSelect.disabled = false;
                
                departmentSections[department].forEach(function(section) {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionSelect.appendChild(option);
                });
            } else {
                sectionSelect.disabled = true;
            }
        });

        // Load models based on category using AJAX
        document.getElementById('category_id').addEventListener('change', function() {
            const categoryId = this.value;
            const modelSelect = document.getElementById('model');
            
            // Clear existing options
            modelSelect.innerHTML = '<option value="">Loading models...</option>';
            modelSelect.disabled = true;
            
            if (categoryId) {
                // Fetch models via AJAX
                fetch('get_models.php?category_id=' + categoryId)
                    .then(response => response.json())
                    .then(data => {
                        modelSelect.innerHTML = '<option value="">Select Model</option>';
                        
                        if (data.models && data.models.length > 0) {
                            data.models.forEach(function(model) {
                                const option = document.createElement('option');
                                option.value = model.model_name;
                                option.textContent = model.model_name;
                                modelSelect.appendChild(option);
                            });
                            modelSelect.disabled = false;
                        } else {
                            modelSelect.innerHTML = '<option value="">No models available</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                        modelSelect.innerHTML = '<option value="">Error loading models</option>';
                    });
            } else {
                modelSelect.innerHTML = '<option value="">Select category first</option>';
            }
        });
    </script>
</body>
</html>