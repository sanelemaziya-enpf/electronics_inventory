<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $contract_name  = trim($conn->real_escape_string($_POST['contract_name']));
    $license_type   = $conn->real_escape_string($_POST['license_type']);
    $vendor         = trim($conn->real_escape_string($_POST['vendor']));
    $license_key    = trim($conn->real_escape_string($_POST['license_key']));
    $quantity       = intval($_POST['quantity'] ?? 1);
    $assigned_to    = trim($conn->real_escape_string($_POST['assigned_to']));
    $purchase_date  = !empty($_POST['purchase_date']) ? $conn->real_escape_string($_POST['purchase_date']) : NULL;
    $issue_date     = !empty($_POST['issue_date'])    ? $conn->real_escape_string($_POST['issue_date'])    : NULL;
    $due_date       = $conn->real_escape_string($_POST['due_date']);
    $renewal_date   = !empty($_POST['renewal_date'])  ? $conn->real_escape_string($_POST['renewal_date'])  : NULL;
    $amount         = !empty($_POST['amount'])        ? floatval($_POST['amount'])                         : NULL;
    $currency       = $conn->real_escape_string($_POST['currency'] ?? 'USD');
    $alert_days     = intval($_POST['alert_days'] ?? 30);
    $auto_renewal   = $conn->real_escape_string($_POST['auto_renewal'] ?? 'no');
    $notes          = trim($conn->real_escape_string($_POST['notes']));
    $created_by     = $_SESSION['user_id'];

    // Determine initial status based on due_date
    $days_left  = (strtotime($due_date) - strtotime(date('Y-m-d'))) / 86400;
    $months_left = round($days_left / 30);

    if ($days_left < 0) {
        $status = 'expired';
    } elseif ($days_left <= $alert_days) {
        $status = 'expiring_soon';
    } else {
        $status = 'active';
    }

    $purchase_val = $purchase_date ? "'$purchase_date'" : "NULL";
    $issue_val    = $issue_date    ? "'$issue_date'"    : "NULL";
    $renewal_val  = $renewal_date  ? "'$renewal_date'"  : "NULL";
    $amount_val   = $amount !== null ? $amount            : "NULL";

    $sql = "INSERT INTO licenses 
            (contract_name, license_type, vendor, license_key, quantity, assigned_to,
             purchase_date, issue_date, due_date, renewal_date, amount, currency,
             days_left, months_left, status, alert_days, auto_renewal, notes, created_by)
            VALUES 
            ('$contract_name', '$license_type', '$vendor', '$license_key', $quantity, '$assigned_to',
             $purchase_val, $issue_val, '$due_date', $renewal_val, $amount_val, '$currency',
             $days_left, $months_left, '$status', $alert_days, '$auto_renewal', '$notes', $created_by)";

    if ($conn->query($sql)) {
        $new_id = $conn->insert_id;

        // Log to license_history
        $conn->query("INSERT INTO license_history (license_id, action, new_due_date, amount, notes, created_by)
                      VALUES ($new_id, 'created', '$due_date', $amount_val, 'License created', $created_by)");

        // Log to activity_logs
        $conn->query("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                      VALUES ($created_by, 'CREATE', 'licenses', $new_id, 'Added license: $contract_name', '".$_SERVER['REMOTE_ADDR']."')");

        header("Location: licenses.php?success=License added successfully");
        exit;
    } else {
        $message = "Error adding license: " . $conn->error;
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add License - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f173bff;
            --secondary: #ad87d3ff;
            --sidebar-width: 260px;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
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

        /* Layout */
        .main-content { margin-left: var(--sidebar-width); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .content-area { padding: 30px; }

        /* Cards */
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .card-header-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; border-radius: 12px 12px 0 0 !important; padding: 18px 24px;
        }

        /* Form styling */
        .form-label { font-weight: 600; font-size: .875rem; color: #495057; }
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary); box-shadow: 0 0 0 .2rem rgba(173,135,211,.25);
        }
        .section-divider {
            font-size: .75rem; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: #6c757d;
            border-bottom: 2px solid #e9ecef; padding-bottom: 6px; margin-bottom: 16px;
        }
        .required-star { color: #dc3545; }

        /* Preview badge */
        #status-preview {
            display: inline-block; padding: 6px 14px; border-radius: 20px;
            font-size: .82rem; font-weight: 600; transition: all .3s;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Inventory System</h5>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><i class="fas fa-home"></i>Dashboard</a>
        <a href="assets.php"><i class="fas fa-box"></i>Assets</a>
        <a href="assignments.php"><i class="fas fa-exchange-alt"></i>Assignments</a>
        <a href="inspections.php"><i class="fas fa-clipboard-check"></i>Inspections</a>
        <a href="licenses.php" class="active"><i class="fas fa-certificate"></i>Licenses</a>
        <a href="staff.php"><i class="fas fa-users"></i>Staff Directory</a>
        <a href="categories.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="manage_models.php"><i class="fas fa-cogs"></i>Asset Models</a>
        <a href="suppliers.php"><i class="fas fa-truck"></i>Suppliers</a>
        <?php if (hasPermission('admin')): ?>
        <a href="users.php"><i class="fas fa-user-shield"></i>Users</a>
        <?php endif; ?>
        <a href="reports.php"><i class="fas fa-chart-bar"></i>Reports</a>
        <hr style="border-color:rgba(255,255,255,.1)">
        <a href="profile.php"><i class="fas fa-user"></i>Profile</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New License</h5>
            <small class="text-muted">
                <a href="licenses.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to License Management
                </a>
            </small>
        </div>
        <div>
            <a href="licenses.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times me-1"></i>Cancel
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" id="licenseForm">
            <div class="row g-4">

                <!-- LEFT COLUMN -->
                <div class="col-lg-8">

                    <!-- Basic Info -->
                    <div class="card mb-4">
                        <div class="card-header-primary">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>License Information</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="section-divider">Basic Details</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Contract / License Name <span class="required-star">*</span></label>
                                    <input type="text" name="contract_name" class="form-control"
                                           placeholder="e.g. Microsoft 365 Business Premium"
                                           value="<?php echo htmlspecialchars($_POST['contract_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">License Type <span class="required-star">*</span></label>
                                    <select name="license_type" class="form-select" required>
                                        <option value="software"         <?php echo ($_POST['license_type'] ?? '') == 'software'         ? 'selected' : ''; ?>>Software</option>
                                        <option value="antivirus"        <?php echo ($_POST['license_type'] ?? '') == 'antivirus'        ? 'selected' : ''; ?>>Antivirus</option>
                                        <option value="cloud_service"    <?php echo ($_POST['license_type'] ?? '') == 'cloud_service'    ? 'selected' : ''; ?>>Cloud Service</option>
                                        <option value="domain"           <?php echo ($_POST['license_type'] ?? '') == 'domain'           ? 'selected' : ''; ?>>Domain</option>
                                        <option value="ssl_certificate"  <?php echo ($_POST['license_type'] ?? '') == 'ssl_certificate'  ? 'selected' : ''; ?>>SSL Certificate</option>
                                        <option value="subscription"     <?php echo ($_POST['license_type'] ?? '') == 'subscription'     ? 'selected' : ''; ?>>Subscription</option>
                                        <option value="standards"        <?php echo ($_POST['license_type'] ?? '') == 'standards'     ? 'selected' : ''; ?>>Standards</option>
                                        <option value="other"            <?php echo ($_POST['license_type'] ?? '') == 'other'            ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor / Supplier</label>
                                    <input type="text" name="vendor" class="form-control"
                                           placeholder="e.g. Microsoft Corporation"
                                           value="<?php echo htmlspecialchars($_POST['vendor'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Assigned To</label>
                                    <input type="text" name="assigned_to" class="form-control"
                                           placeholder="Department or user name"
                                           value="<?php echo htmlspecialchars($_POST['assigned_to'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">License Key / Serial Number</label>
                                    <textarea name="license_key" class="form-control" rows="2"
                                              placeholder="Enter license key or serial number (stored securely)"><?php echo htmlspecialchars($_POST['license_key'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="section-divider">Dates</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-control"
                                           value="<?php echo $_POST['purchase_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Issue / Start Date</label>
                                    <input type="date" name="issue_date" class="form-control"
                                           value="<?php echo $_POST['issue_date'] ?? ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Due / Expiry Date <span class="required-star">*</span></label>
                                    <input type="date" name="due_date" class="form-control" id="due_date"
                                           value="<?php echo $_POST['due_date'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Renewal Date</label>
                                    <input type="date" name="renewal_date" class="form-control"
                                           value="<?php echo $_POST['renewal_date'] ?? ''; ?>">
                                    <div class="form-text">If different from expiry</div>
                                </div>
                            </div>

                            <div class="section-divider">Financial</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Contract Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="amount" class="form-control"
                                               placeholder="0.00" step="0.01" min="0"
                                               value="<?php echo $_POST['amount'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Currency</label>
                                    <select name="currency" class="form-select">
                                        <option value="USD" <?php echo ($_POST['currency'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD</option>
                                        <option value="SZL" <?php echo ($_POST['currency'] ?? '') == 'SZL' ? 'selected' : ''; ?>>SZL</option>
                                        <option value="ZAR" <?php echo ($_POST['currency'] ?? '') == 'ZAR' ? 'selected' : ''; ?>>ZAR</option>
                                        <option value="EUR" <?php echo ($_POST['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                        <option value="GBP" <?php echo ($_POST['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Quantity / Seats</label>
                                    <input type="number" name="quantity" class="form-control"
                                           min="1" value="<?php echo $_POST['quantity'] ?? 1; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <label class="form-label fw-bold"><i class="fas fa-sticky-note me-1 text-muted"></i>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Any additional information about this license..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="col-lg-4">

                    <!-- Status Preview -->
                    <div class="card mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Status Preview</h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <span id="status-preview" class="bg-secondary text-white">Select due date</span>
                            </div>
                            <div id="days-preview" class="text-muted small mb-2">—</div>
                            <div id="months-preview" class="text-muted small">—</div>
                        </div>
                    </div>

                    <!-- Alert Settings -->
                    <div class="card mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0"><i class="fas fa-bell me-2"></i>Alert Settings</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label">Alert Days Before Expiry</label>
                                <div class="input-group">
                                    <input type="number" name="alert_days" class="form-control"
                                           min="1" max="365" value="<?php echo $_POST['alert_days'] ?? 30; ?>">
                                    <span class="input-group-text">days</span>
                                </div>
                                <div class="form-text">License will show as "Expiring Soon" within this window.</div>
                            </div>
                            <div>
                                <label class="form-label">Auto-Renewal</label>
                                <select name="auto_renewal" class="form-select">
                                    <option value="no"  <?php echo ($_POST['auto_renewal'] ?? 'no') == 'no'  ? 'selected' : ''; ?>>No</option>
                                    <option value="yes" <?php echo ($_POST['auto_renewal'] ?? '') == 'yes' ? 'selected' : ''; ?>>Yes</option>
                                </select>
                                <div class="form-text">Will be flagged with a sync icon in the list.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Save License
                                </button>
                                <a href="licenses.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dueDateInput = document.getElementById('due_date');
    const statusPreview = document.getElementById('status-preview');
    const daysPreview   = document.getElementById('days-preview');
    const monthsPreview = document.getElementById('months-preview');

    function updatePreview() {
        const val = dueDateInput.value;
        if (!val) return;

        const today     = new Date(); today.setHours(0,0,0,0);
        const due       = new Date(val);
        const diffMs    = due - today;
        const daysLeft  = Math.ceil(diffMs / 86400000);
        const alertDays = parseInt(document.querySelector('[name=alert_days]').value) || 30;
        const months    = Math.round(daysLeft / 30);

        let label, classes;
        if (daysLeft < 0) {
            label = 'Expired'; classes = 'bg-danger text-white';
            daysPreview.textContent   = Math.abs(daysLeft) + ' days ago';
        } else if (daysLeft <= alertDays) {
            label = 'Expiring Soon'; classes = 'bg-warning text-dark';
            daysPreview.textContent   = daysLeft + ' days remaining';
        } else {
            label = 'Active'; classes = 'bg-success text-white';
            daysPreview.textContent   = daysLeft + ' days remaining';
        }

        statusPreview.textContent  = label;
        statusPreview.className    = classes;
        monthsPreview.textContent  = '≈ ' + Math.abs(months) + ' month' + (Math.abs(months) !== 1 ? 's' : '');
    }

    dueDateInput.addEventListener('change', updatePreview);
    document.querySelector('[name=alert_days]').addEventListener('input', updatePreview);
    updatePreview();
</script>
</body>
</html>