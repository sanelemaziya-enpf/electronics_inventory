<?php
require_once 'config.php';

checkLogin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Accept CSV and Excel files (Excel will be converted to CSV by user)
        if (in_array($file_extension, ['csv', 'xls', 'xlsx'])) {
            try {
                $imported = 0;
                $errors = [];
                
                // For Excel files, instruct user to save as CSV
                if ($file_extension !== 'csv') {
                    $message = "Please save your Excel file as CSV format before uploading.";
                    $message_type = 'warning';
                } else {
                    // Read CSV file
                    $handle = fopen($file['tmp_name'], 'r');
                    
                    if ($handle !== false) {
                        // Skip header row
                        $header = fgetcsv($handle);
                        
                        $row_number = 1;
                        while (($row = fgetcsv($handle)) !== false) {
                            $row_number++;
                            
                            // Skip empty rows
                            if (empty($row[0])) continue;
                            
                            // Expected columns: Name, Category, Model, Serial Number, Purchase Date, Purchase Cost, Location, Floor, Department, Status
                            $name = sanitize($row[0]);
                            $category_name = isset($row[1]) ? sanitize($row[1]) : '';
                            $model = isset($row[2]) ? sanitize($row[2]) : '';
                            $serial_number = isset($row[3]) ? sanitize($row[3]) : '';
                            $purchase_date = !empty($row[4]) ? date('Y-m-d', strtotime($row[4])) : null;
                            $purchase_cost = !empty($row[5]) ? floatval($row[5]) : null;
                            $location = isset($row[6]) ? sanitize($row[6]) : '';
                            $floor = isset($row[7]) ? sanitize($row[7]) : '';
                            $department = isset($row[8]) ? sanitize($row[8]) : '';
                            $status = !empty($row[9]) ? sanitize($row[9]) : 'available';
                            
                            // Validate status
                            $valid_statuses = ['available', 'assigned', 'maintenance', 'retired'];
                            if (!in_array($status, $valid_statuses)) {
                                $status = 'available';
                            }
                            
                            // Find or create category
                            $category_id = null;
                            if ($category_name) {
                                $cat_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                                $cat_stmt->bind_param("s", $category_name);
                                $cat_stmt->execute();
                                $cat_result = $cat_stmt->get_result();
                                
                                if ($cat_result->num_rows > 0) {
                                    $category_id = $cat_result->fetch_assoc()['id'];
                                } else {
                                    $insert_cat = $conn->prepare("INSERT INTO categories (name, created_by) VALUES (?, ?)");
                                    $insert_cat->bind_param("si", $category_name, $_SESSION['user_id']);
                                    $insert_cat->execute();
                                    $category_id = $conn->insert_id;
                                    $insert_cat->close();
                                }
                                $cat_stmt->close();
                            }
                            
                            // Generate asset tag
                            $asset_tag = generateAssetTag();
                            
                            // Insert asset
                            $stmt = $conn->prepare("INSERT INTO assets (asset_tag, name, category_id, model, serial_number, purchase_date, purchase_cost, location, floor, department, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("ssisssdssssi", $asset_tag, $name, $category_id, $model, $serial_number, $purchase_date, $purchase_cost, $location, $floor, $department, $status, $_SESSION['user_id']);
                            
                            if ($stmt->execute()) {
                                $imported++;
                                logActivity($_SESSION['user_id'], 'CREATE', 'assets', $conn->insert_id, "Imported asset: $name");
                            } else {
                                $errors[] = "Row $row_number: " . $conn->error;
                            }
                            $stmt->close();
                        }
                        
                        fclose($handle);
                        
                        if ($imported > 0) {
                            $message = "Successfully imported $imported assets!";
                            $message_type = 'success';
                            if (!empty($errors)) {
                                $message .= " However, " . count($errors) . " rows had errors.";
                            }
                        } else {
                            $message = "No assets were imported.";
                            if (!empty($errors)) {
                                $message .= " Errors: " . implode(", ", $errors);
                            }
                            $message_type = 'danger';
                        }
                    } else {
                        $message = "Error reading CSV file.";
                        $message_type = 'danger';
                    }
                }
                
            } catch (Exception $e) {
                $message = "Error processing file: " . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = "Invalid file format. Please upload CSV files only.";
            $message_type = 'danger';
        }
    } else {
        $message = "Error uploading file.";
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Assets - <?php echo APP_NAME; ?></title>
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
        .upload-area {
            border: 3px dashed #ddd;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: var(--primary-color);
            background: #f5f7ff;
        }
        .upload-icon {
            font-size: 64px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .instruction-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #e3f2fd;
            border-radius: 5px;
            margin: 5px 0;
            font-size: 0.9em;
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
            <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
            <a href="manage_models.php" class="active"><i class="fas fa-cogs"></i> Asset Models</a> 
            <a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
            <?php if (hasPermission('admin')): ?>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="import.php" class="active"><i class="fas fa-file-import"></i> Import Assets</a>
            <a href="inspections.php"><i class="fas fa-clipboard-check"></i> Inspections</a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h5 class="mb-0">Import Assets from CSV</h5>
            <small class="text-muted">Bulk import assets from spreadsheet</small>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-circle' : 'exclamation-triangle'); ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload CSV File</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="upload-area">
                                    <div class="upload-icon">
                                        <i class="fas fa-file-csv"></i>
                                    </div>
                                    <h5>Drop your CSV file here or click to browse</h5>
                                    <p class="text-muted">Supported format: .csv</p>
                                    <input type="file" name="excel_file" class="form-control mt-3" accept=".csv" required>
                                </div>
                                <div class="mt-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>How to convert Excel to CSV:</strong><br>
                                        1. Open your Excel file<br>
                                        2. Click <strong>File → Save As</strong><br>
                                        3. Choose <strong>CSV (Comma delimited) (*.csv)</strong> as the file type<br>
                                        4. Save and upload the CSV file here
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-upload me-2"></i>Import Assets
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>