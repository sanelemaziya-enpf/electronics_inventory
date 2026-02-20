<?php
//use PhpOffice\PhpSpreadsheet\IOFactory;
require_once 'config.php';
checkLogin();

// Require admin permission
if (!hasPermission('admin')) {
    die('Access denied. Admin permission required.');
}

$message = '';
$message_type = '';
$imported_count = 0;
$skipped_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    //require_once 'vendor/autoload.php'; // Assuming PhpSpreadsheet is installed
    
    $file = $_FILES['excel_file']['tmp_name'];
    
    try {
        // Load the spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getSheet(0); // 'Licences' sheet
        $rows = $worksheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row[1])) continue; // Contract Name column
            
            $license_id = $row[0]; // ID
            $contract_name = trim($row[1]); // Contract Name
            $due_date_raw = $row[2]; // Due date
            $amount = !empty($row[4]) ? floatval($row[4]) : null;
            $days_left = !empty($row[5]) ? intval($row[5]) : null;
            $months_left = !empty($row[6]) ? intval($row[6]) : null;
            
            // Convert Excel date to PHP date
            if (is_numeric($due_date_raw)) {
                // Excel serial date
                $unix_date = ($due_date_raw - 25569) * 86400;
                $due_date = date('Y-m-d', $unix_date);
            } else if ($due_date_raw instanceof DateTime) {
                $due_date = $due_date_raw->format('Y-m-d');
            } else {
                // Try to parse as string
                $due_date = date('Y-m-d', strtotime($due_date_raw));
            }
            
            // Skip invalid dates
            if ($due_date === '1970-01-01' || empty($due_date)) {
                $skipped_count++;
                continue;
            }
            
            // Determine status based on days left
            if ($days_left < 0) {
                $status = 'expired';
            } else if ($days_left <= 30) {
                $status = 'expiring_soon';
            } else {
                $status = 'active';
            }
            
            // Map contract names to license types
            $license_type = 'software'; // Default
            $contract_lower = strtolower($contract_name);
            
            if (strpos($contract_lower, 'kaspersky') !== false || 
                strpos($contract_lower, 'backup') !== false) {
                $license_type = 'antivirus';
            } else if (strpos($contract_lower, 'domain') !== false || 
                       strpos($contract_lower, 'host') !== false) {
                $license_type = 'domain';
            } else if (strpos($contract_lower, 'ssl') !== false) {
                $license_type = 'ssl_certificate';
            } else if (strpos($contract_lower, 'o365') !== false || 
                       strpos($contract_lower, 'appstore') !== false ||
                       strpos($contract_lower, 'plural') !== false) {
                $license_type = 'subscription';
            }
            
            // Check if already exists
            $check_stmt = $conn->prepare("SELECT id FROM licenses WHERE license_id = ? OR (contract_name = ? AND due_date = ?)");
            $check_stmt->bind_param("sss", $license_id, $contract_name, $due_date);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing
                $update_stmt = $conn->prepare("UPDATE licenses SET 
                                              days_left = ?, months_left = ?, amount = ?, status = ?
                                              WHERE id = ?");
                $update_stmt->bind_param("iidsi", $days_left, $months_left, $amount, $status, $existing['id']);
                $update_stmt->execute();
                $imported_count++;
            } else {
                // Insert new
                $insert_stmt = $conn->prepare("INSERT INTO licenses 
                                              (license_id, contract_name, license_type, due_date, 
                                               amount, days_left, months_left, status, created_by)
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("ssssdissi", 
                    $license_id, $contract_name, $license_type, $due_date,
                    $amount, $days_left, $months_left, $status, $_SESSION['user_id']
                );
                
                if ($insert_stmt->execute()) {
                    $new_id = $conn->insert_id;
                    
                    // Log in history
                    $hist_stmt = $conn->prepare("INSERT INTO license_history 
                                                (license_id, action, new_due_date, amount, created_by) 
                                                VALUES (?, 'imported', ?, ?, ?)");
                    $hist_stmt->bind_param("isdi", $new_id, $due_date, $amount, $_SESSION['user_id']);
                    $hist_stmt->execute();
                    
                    $imported_count++;
                }
            }
        }
        
        $message = "Import completed! Imported/Updated: $imported_count licenses. Skipped: $skipped_count invalid entries.";
        $message_type = "success";
        
        logActivity($_SESSION['user_id'], 'CREATE', 'licenses', null, "Imported $imported_count licenses from Excel");
        
    } catch (Exception $e) {
        $message = "Error importing file: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Licenses - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f173bff;
            --secondary: #ad87d3ff;
            --sidebar-width: 260px;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white; overflow-y: auto; z-index: 1000;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 25px;
            color: rgba(255,255,255,.8); text-decoration: none; transition: all .3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,.1); color: white; border-left: 4px solid white;
        }
        .sidebar-menu i { width: 25px; margin-right: 12px; }
        .main-content { margin-left: var(--sidebar-width); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .content-area { padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .upload-zone {
            border: 2px dashed #ddd; border-radius: 12px; padding: 40px;
            text-align: center; cursor: pointer; transition: all .3s;
        }
        .upload-zone:hover { border-color: var(--primary); background: #f8f9fa; }
        .upload-zone.dragover { border-color: var(--primary); background: #e3f2fd; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h5>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><i class="fas fa-home"></i>Dashboard</a>
        <a href="licenses.php" class="active"><i class="fas fa-certificate"></i>Licenses</a>
        <?php if (hasPermission('admin')): ?>
        <a href="users.php"><i class="fas fa-user-shield"></i>Users</a>
        <?php endif; ?>
        <hr style="border-color:rgba(255,255,255,.1)">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Import Licenses from Excel</h5>
            <small class="text-muted">Upload your license tracker spreadsheet</small>
        </div>
        <a href="licenses.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="content-area">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="fas fa-file-excel me-2"></i>Upload License Tracker</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-zone" id="uploadZone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Drag & Drop Excel File Here</h5>
                                <p class="text-muted">or click to browse</p>
                                <input type="file" name="excel_file" id="fileInput" accept=".xlsx,.xls" 
                                       class="d-none" required>
                                <div id="fileName" class="mt-3 text-primary"></div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Import Licenses
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Import Instructions</h6>
                    </div>
                    <div class="card-body">
                        <ol class="small ps-3 mb-0">
                            <li class="mb-2">Upload your <strong>License_Tracker_New.xlsx</strong> file</li>
                            <li class="mb-2">The system will read the "Licences" sheet</li>
                            <li class="mb-2">Expected columns: ID, Contract Name, Due date, Amount, Days left, Months Left</li>
                            <li class="mb-2">Existing licenses will be updated; new ones will be added</li>
                            <li>Invalid or expired dates will be skipped</li>
                        </ol>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0 ps-3">
                            <li class="mb-2">Backup your data before importing</li>
                            <li class="mb-2">Ensure date formats are valid</li>
                            <li class="mb-2">Remove any completely empty rows</li>
                            <li>Check the results after import</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const fileName = document.getElementById('fileName');

uploadZone.addEventListener('click', () => fileInput.click());

uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    displayFileName();
});

fileInput.addEventListener('change', displayFileName);

function displayFileName() {
    if (fileInput.files.length > 0) {
        fileName.innerHTML = `<i class="fas fa-file-excel me-2"></i>${fileInput.files[0].name}`;
    }
}
</script>
</body>
</html>