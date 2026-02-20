<?php
require_once 'config.php';
checkLogin();

$message = '';
$message_type = '';
$asset_id = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;

// Get asset details if asset_id provided
$asset = null;
if ($asset_id) {
    $stmt = $conn->prepare("SELECT a.*, c.name as category_name FROM assets a 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           WHERE a.id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset = $result->fetch_assoc();
}

// Get current quarter
$current_month = date('n');
$current_quarter = 'Q' . ceil($current_month / 3);
$current_year = date('Y');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = intval($_POST['asset_id']);
    $inspection_date = sanitize($_POST['inspection_date']);
    $inspection_quarter = sanitize($_POST['inspection_quarter']);
    $inspection_year = intval($_POST['inspection_year']);
    
    $inspector_username = sanitize($_POST['inspector_username']);
    $subject_username = sanitize($_POST['subject_username']);
    $computer_name = sanitize($_POST['computer_name']);
    $brand_model = sanitize($_POST['brand_model']);
    $date_of_purchase = !empty($_POST['date_of_purchase']) ? sanitize($_POST['date_of_purchase']) : null;
    $original_assignee = sanitize($_POST['original_assignee']);
    $inherited_from = sanitize($_POST['inherited_from']);
    
    $unit_type = sanitize($_POST['unit_type']);
    $powers_on = sanitize($_POST['powers_on']);
 //   $passwords_removed = sanitize($_POST['passwords_removed']);
 //   $company_data_removed = sanitize($_POST['company_data_removed']);
 //   $total_ram = sanitize($_POST['total_ram']);
  //  $hd_capacity = sanitize($_POST['hd_capacity']);
    
    $physically_intact = sanitize($_POST['physically_intact']);
    $case_cracks = sanitize($_POST['case_cracks']);
    $lcd_scratches = sanitize($_POST['lcd_scratches']);
    $lcd_discoloration = sanitize($_POST['lcd_discoloration']);
    $has_accessories = sanitize($_POST['has_accessories']);
    $activation_locks_removed = sanitize($_POST['activation_locks_removed']);
    
    $inspector_comments = sanitize($_POST['inspector_comments']);
    $subject_user_comments = sanitize($_POST['subject_user_comments']);
    $overall_condition = sanitize($_POST['overall_condition']);
    
    $failed_checks = 0;
    if ($powers_on === 'no') $failed_checks++;
    if ($passwords_removed === 'no') $failed_checks++;
    if ($company_data_removed === 'no') $failed_checks++;
    $status = $failed_checks > 0 ? 'failed' : 'completed';
    
    $stmt = $conn->prepare("INSERT INTO asset_inspections 
                           (asset_id, inspection_date, inspection_quarter, inspection_year,
                            inspector_id, inspector_username, subject_username, computer_name,
                            brand_model, date_of_purchase, original_assignee, inherited_from,
                            unit_type, powers_on, passwords_removed, company_data_removed,
                            total_ram, hd_capacity, physically_intact, case_cracks, lcd_scratches,
                            lcd_discoloration, has_accessories, activation_locks_removed,
                            inspector_comments, subject_user_comments, status, overall_condition, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("isssisssssssssssssssssssssssi", 
        $asset_id, $inspection_date, $inspection_quarter, $inspection_year,
        $_SESSION['user_id'], $inspector_username, $subject_username, $computer_name,
        $brand_model, $date_of_purchase, $original_assignee, $inherited_from,
        $unit_type, $powers_on, $passwords_removed, $company_data_removed,
        $total_ram, $hd_capacity, $physically_intact, $case_cracks, $lcd_scratches,
        $lcd_discoloration, $has_accessories, $activation_locks_removed,
        $inspector_comments, $subject_user_comments, $status, $overall_condition, $_SESSION['user_id']
    );
    
    if ($stmt->execute()) {
        $inspection_id = $conn->insert_id;
        
        $components = [
            'CD-ROM Drive', 'DVD', 'Hard Drive', 'Network Card', 'Keyboard',
            'Mouse', 'Power Cord', 'External disk drives', 'AC adapter',
            'Battery', 'Docking station with keys', 'VGA-Analog cable',
            'Cables', 'Toner cartridge'
        ];
        
        foreach ($components as $component) {
            $key = str_replace(' ', '_', $component);
            $missing = isset($_POST['comp_missing_' . $key]) ? sanitize($_POST['comp_missing_' . $key]) : 'n/a';
            $working = isset($_POST['comp_working_' . $key]) ? sanitize($_POST['comp_working_' . $key]) : 'n/a';
            $damaged = isset($_POST['comp_damaged_' . $key]) ? sanitize($_POST['comp_damaged_' . $key]) : 'n/a';
            
            $comp_stmt = $conn->prepare("INSERT INTO inspection_components 
                                         (inspection_id, component_name, is_missing, is_working, is_damaged)
                                         VALUES (?, ?, ?, ?, ?)");
            $comp_stmt->bind_param("issss", $inspection_id, $component, $missing, $working, $damaged);
            $comp_stmt->execute();
            $comp_stmt->close();
        }
        
        logActivity($_SESSION['user_id'], 'CREATE', 'asset_inspections', $inspection_id, "Completed inspection for asset ID: $asset_id");
        header("Location: inspection_detail.php?id=$inspection_id");
        exit();
    } else {
        $message = "Error creating inspection: " . $conn->error;
        $message_type = "danger";
    }
}

// Get all assets for dropdown
$assets_query = "SELECT a.id, a.asset_tag, a.name, c.name as category_name 
                 FROM assets a 
                 LEFT JOIN categories c ON a.category_id = c.id 
                 WHERE a.status != 'retired'
                 ORDER BY a.asset_tag";
$assets_list = $conn->query($assets_query);

$components = [
    'Hard Drive', 'Network Card', 'Keyboard',
    'Mouse', 'Power Cord','AC adapter',
    'Battery',  
    'Cables', 
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Inspection - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --sidebar-width: 260px;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #667eea, #764ba2);
            color: white; overflow-y: auto; z-index: 1000;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 25px;
            color: rgba(255,255,255,.8); text-decoration: none;
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
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .card-header { background: white !important; border-bottom: 1px solid #f0f0f0; }
        .section-label {
            font-size: .7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #667eea; margin-bottom: .75rem;
        }

        /* Compact radio pills */
        .radio-pills { display: flex; gap: 6px; flex-wrap: wrap; }
        .radio-pills input[type=radio] { display: none; }
        .radio-pills label {
            padding: 4px 14px; border-radius: 20px; border: 1px solid #dee2e6;
            font-size: .82rem; cursor: pointer; transition: all .15s;
            color: #555; background: white;
        }
        .radio-pills input[type=radio]:checked + label {
            background: var(--primary); color: white; border-color: var(--primary);
        }

        /* Check table */
        .check-table th { font-size: .75rem; background: #f8f9fa; color: #666; font-weight: 600; }
        .check-table td { vertical-align: middle; font-size: .85rem; }
        .check-table td:first-child { font-weight: 500; }

        /* Sidebar card */
        .sticky-sidebar { position: sticky; top: 20px; }
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
        <a href="inspections.php" class="active"><i class="fas fa-clipboard-check"></i>Inspections</a>
        <a href="staff.php"><i class="fas fa-users"></i>Staff Directory</a>
        <a href="categories.php"><i class="fas fa-tags"></i>Categories</a>
        <a href="suppliers.php"><i class="fas fa-truck"></i>Suppliers</a>
        <?php if (hasPermission('admin')): ?>
        <a href="users.php"><i class="fas fa-user-shield"></i>Users</a>
        <?php endif; ?>
        <a href="reports.php"><i class="fas fa-chart-bar"></i>Reports</a>
        <hr style="border-color:rgba(255,255,255,.1)">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">New PC Inspection</h5>
            <small class="text-muted">Quarterly Asset Inspection</small>
        </div>
        <a href="inspections.php" class="btn btn-outline-secondary btn-sm">
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

        <form method="POST" id="inspectionForm">
            <div class="row g-4">

                <!-- ── LEFT COLUMN ────────────────────────────── -->
                <div class="col-lg-8">

                    <!-- 1. INSPECTION DETAILS -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <strong><i class="fas fa-info-circle me-2 text-primary"></i>Inspection Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Asset <span class="text-danger">*</span></label>
                                    <select name="asset_id" id="asset_id" class="form-select" required
                                            <?php echo $asset ? 'disabled' : ''; ?>>
                                        <option value="">Select Asset</option>
                                        <?php while ($ast = $assets_list->fetch_assoc()): ?>
                                        <option value="<?php echo $ast['id']; ?>"
                                                <?php echo ($asset && $asset['id'] == $ast['id']) ? 'selected' : ''; ?>
                                                data-name="<?php echo htmlspecialchars($ast['name']); ?>">
                                            <?php echo htmlspecialchars($ast['asset_tag'] . ' – ' . $ast['name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <?php if ($asset): ?>
                                    <input type="hidden" name="asset_id" value="<?php echo $asset['id']; ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Inspection Date <span class="text-danger">*</span></label>
                                    <input type="date" name="inspection_date" class="form-control"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Quarter <span class="text-danger">*</span></label>
                                    <select name="inspection_quarter" class="form-select" required>
                                        <?php foreach (['Q1'=>'Q1 (Jan–Mar)','Q2'=>'Q2 (Apr–Jun)','Q3'=>'Q3 (Jul–Sep)','Q4'=>'Q4 (Oct–Dec)'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $current_quarter==$v?'selected':''; ?>><?php echo $l; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Year <span class="text-danger">*</span></label>
                                    <input type="number" name="inspection_year" class="form-control"
                                           value="<?php echo $current_year; ?>" min="2020" max="2050" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Unit Type <span class="text-danger">*</span></label>
                                    <select name="unit_type" id="unit_type" class="form-select" required>
                                        <option value="laptop" selected>Laptop</option>
                                        <option value="desktop">Desktop</option>
                                        <option value="mobile">Mobile / Tablet</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- 2. PEOPLE & ASSET INFO -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <strong><i class="fas fa-user me-2 text-primary"></i>People &amp; Asset Info</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">ICT Inspector</label>
                                    <input type="text" name="inspector_username" class="form-control"
                                           value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Subject Username</label>
                                    <input type="text" name="subject_username" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Computer Name</label>
                                    <input type="text" name="computer_name" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Brand &amp; Model</label>
                                    <input type="text" name="brand_model" class="form-control"
                                           value="<?php echo $asset ? htmlspecialchars($asset['name']) : ''; ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Date of Purchase</label>
                                    <input type="date" name="date_of_purchase" class="form-control"
                                           value="<?php echo $asset ? $asset['purchase_date'] : ''; ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Original Assignee</label>
                                    <input type="text" name="original_assignee" class="form-control">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Inherited From</label>
                                    <input type="text" name="inherited_from" class="form-control">
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- 3. CHECKS — unified table -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <strong><i class="fas fa-check-square me-2 text-primary"></i>Condition Checks</strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm check-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width:55%">Check</th>
                                        <th class="text-center">Yes</th>
                                        <th class="text-center">No</th>
                                        <th class="text-center">N/A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $checks = [
                                    ['name'=>'powers_on',              'label'=>'Powers on without hardware errors?',                'default'=>'yes'],
                              //    ['name'=>'passwords_removed',      'label'=>'Passwords removed?',                               'default'=>'yes'], 
                            //      ['name'=>'company_data_removed',   'label'=>'Company data removed?',                            'default'=>'yes'], 
                                    ['name'=>'physically_intact',      'label'=>'Physically intact? <small class="text-muted">(Desktop)</small>',  'default'=>'n/a'],
                                    ['name'=>'case_cracks',            'label'=>'Case cracks? <small class="text-muted">(Laptop)</small>',         'default'=>'no'],
                                    ['name'=>'scratches',          'label'=>'LCD scratches? <small class="text-muted">(Laptop)</small>',       'default'=>'no'],
                                    ['name'=>'discoloration',      'label'=>'LCD discoloration? <small class="text-muted">(Laptop)</small>',   'default'=>'no'],
                                    ['name'=>'has_accessories',        'label'=>'Has accessories?',                                 'default'=>'no'],
                              //      ['name'=>'activation_locks_removed','label'=>'Activation locks removed? <small class="text-muted">(DEP / iCloud / Find My)</small>', 'default'=>'n/a'],
                                ];
                                foreach ($checks as $c):
                                ?>
                                <tr>
                                    <td class="ps-3"><?php echo $c['label']; ?></td>
                                    <?php foreach (['yes','no','n/a'] as $val): ?>
                                    <td class="text-center">
                                        <input type="radio" name="<?php echo $c['name']; ?>"
                                               value="<?php echo $val; ?>"
                                               <?php echo $c['default']===$val ? 'checked' : ''; ?>>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- RAM / HD inline under the table -->
                        <div class="card-footer bg-white border-top px-3 py-3">
                            <div class="row g-3">
                                <!--<div class="col-md-6">
                                    <label class="form-label">Total RAM <small class="text-muted">(at boot)</small></label>
                                    <input type="text" name="total_ram" class="form-control form-control-sm" placeholder="e.g. 8 GB">
                                </div> -->
                             <!--   <div class="col-md-6">
                                    <label class="form-label">HD Capacity <small class="text-muted">(at boot)</small></label>
                                    <input type="text" name="hd_capacity" class="form-control form-control-sm" placeholder="e.g. 500 GB">
                                </div>   -->
                            </div>
                        </div>
                    </div>

                    <!-- 4. COMPONENTS -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <strong><i class="fas fa-list-check me-2 text-primary"></i>Component Status</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm check-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3" style="width:34%">Component</th>
                                            <th colspan="3" class="text-center border-start">Missing</th>
                                            <th colspan="3" class="text-center border-start">Working</th>
                                            <th colspan="3" class="text-center border-start">Damaged</th>
                                        </tr>
                                        <tr class="table-light">
                                            <th class="ps-3"></th>
                                            <th class="text-center">Y</th><th class="text-center">N</th><th class="text-center">—</th>
                                            <th class="text-center border-start">Y</th><th class="text-center">N</th><th class="text-center">—</th>
                                            <th class="text-center border-start">Y</th><th class="text-center">N</th><th class="text-center">—</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($components as $comp):
                                        $cid = str_replace(' ', '_', $comp); ?>
                                    <tr>
                                        <td class="ps-3"><?php echo $comp; ?></td>
                                        <!-- Missing -->
                                        <td class="text-center"><input type="radio" name="comp_missing_<?php echo $cid; ?>" value="yes"></td>
                                        <td class="text-center"><input type="radio" name="comp_missing_<?php echo $cid; ?>" value="no" checked></td>
                                        <td class="text-center"><input type="radio" name="comp_missing_<?php echo $cid; ?>" value="n/a"></td>
                                        <!-- Working -->
                                        <td class="text-center border-start"><input type="radio" name="comp_working_<?php echo $cid; ?>" value="yes" checked></td>
                                        <td class="text-center"><input type="radio" name="comp_working_<?php echo $cid; ?>" value="no"></td>
                                        <td class="text-center"><input type="radio" name="comp_working_<?php echo $cid; ?>" value="n/a"></td>
                                        <!-- Damaged -->
                                        <td class="text-center border-start"><input type="radio" name="comp_damaged_<?php echo $cid; ?>" value="yes"></td>
                                        <td class="text-center"><input type="radio" name="comp_damaged_<?php echo $cid; ?>" value="no" checked></td>
                                        <td class="text-center"><input type="radio" name="comp_damaged_<?php echo $cid; ?>" value="n/a"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 5. COMMENTS -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <strong><i class="fas fa-comment me-2 text-primary"></i>Comments</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Inspector Comments</label>
                                    <textarea name="inspector_comments" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subject User Comments</label>
                                    <textarea name="subject_user_comments" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /col-lg-8 -->

                <!-- ── RIGHT COLUMN (sidebar) ─────────────────── -->
                <div class="col-lg-4">
                    <div class="sticky-sidebar">

                        <div class="card mb-3">
                            <div class="card-header py-3">
                                <strong><i class="fas fa-clipboard-check me-2 text-primary"></i>Overall Assessment</strong>
                            </div>
                            <div class="card-body">
                                <label class="form-label">Overall Condition <span class="text-danger">*</span></label>
                                <select name="overall_condition" class="form-select mb-3" required>
                                    <option value="">Select…</option>
                                    <option value="excellent">Excellent</option>
                                    <option value="good" selected>Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>

                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-save me-2"></i>Complete Inspection
                                </button>
                                <a href="inspections.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>

                                <hr class="my-3">

                                <div class="small text-muted">
                                    <div class="mb-1"><i class="fas fa-user me-1"></i>
                                        <strong>Inspector:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                    </div>
                                    <div><i class="fas fa-calendar me-1"></i>
                                        <strong>Date:</strong> <?php echo date('d M Y, h:i A'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header py-3">
                                <strong><i class="fas fa-info-circle me-2 text-primary"></i>Notes</strong>
                            </div>
                            <div class="card-body small text-muted">
                                <ul class="mb-0 ps-3">
                                    <li>Conduct inspection in the presence of the assigned user</li>
                                    <li>Check all components carefully</li>
                                    <li>Note any damage or missing items</li>
                                    <li>Document all findings in the comments</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div><!-- /col-lg-4 -->

            </div><!-- /row -->
        </form>
    </div><!-- /content-area -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-fill brand/model when asset is selected
    document.getElementById('asset_id').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            document.querySelector('input[name="brand_model"]').value = opt.getAttribute('data-name') || '';
        }
    });
</script>
</body>
</html>