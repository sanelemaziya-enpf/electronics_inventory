<?php
require_once 'config.php';
checkLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header("Location: licenses.php"); exit; }

// Auto-update status on every page load
$conn->query("UPDATE licenses SET status = 'expired' WHERE renewal_date < CURDATE() AND status != 'cancelled'");
$conn->query("UPDATE licenses SET status = 'expiring_soon' WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL alert_days DAY) AND status = 'active'");

// Fetch license
$lic = $conn->query("SELECT l.*, u.full_name as created_by_name,
                     DATEDIFF(l.due_date, CURDATE()) as days_until_expiry
                     FROM licenses l
                     LEFT JOIN users u ON l.created_by = u.id
                     WHERE l.id = $id")->fetch_assoc();

if (!$lic) { header("Location: licenses.php?error=License+not+found"); exit; }

// Fetch history
$history = $conn->query("SELECT lh.*, u.full_name as actor_name
                         FROM license_history lh
                         LEFT JOIN users u ON lh.created_by = u.id
                         WHERE lh.license_id = $id
                         ORDER BY lh.created_at DESC");

$days     = $lic['days_until_expiry'];
$months   = round($days / 30);
$urgency  = $days < 0 ? 'expired' : ($days <= 7 ? 'critical' : ($days <= 30 ? 'warning' : 'good'));

$status_colors = [
    'active'        => ['bg' => '#d4edda', 'text' => '#155724', 'badge' => 'success'],
    'expiring_soon' => ['bg' => '#fff3cd', 'text' => '#856404', 'badge' => 'warning'],
    'expired'       => ['bg' => '#f8d7da', 'text' => '#721c24', 'badge' => 'danger'],
    'cancelled'     => ['bg' => '#e2e3e5', 'text' => '#383d41', 'badge' => 'secondary'],
];
$sc = $status_colors[$lic['status']] ?? $status_colors['active'];

$days_color  = $days < 0 ? '#dc3545' : ($days <= 30 ? '#fd7e14' : '#198754');
$pct_elapsed = 0;
if ($lic['purchase_date'] && $lic['due_date']) {
    $total_span  = strtotime($lic['due_date']) - strtotime($lic['purchase_date']);
    $elapsed     = time() - strtotime($lic['purchase_date']);
    $pct_elapsed = $total_span > 0 ? min(100, max(0, round(($elapsed / $total_span) * 100))) : 100;
}

$action_labels = [
    'created'  => ['icon' => 'fa-plus-circle',    'color' => 'success'],
    'renewed'  => ['icon' => 'fa-sync-alt',        'color' => 'primary'],
    'updated'  => ['icon' => 'fa-edit',            'color' => 'info'],
    'expired'  => ['icon' => 'fa-times-circle',    'color' => 'danger'],
    'cancelled'=> ['icon' => 'fa-ban',             'color' => 'secondary'],
    'imported' => ['icon' => 'fa-file-import',     'color' => 'warning'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lic['contract_name']); ?> - <?php echo APP_NAME; ?></title>
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

        /* Hero banner */
        .license-hero {
            background: linear-gradient(135deg, var(--primary) 0%, #1a2a6c 50%, var(--secondary) 100%);
            color: white; border-radius: 12px; padding: 30px;
            margin-bottom: 24px; position: relative; overflow: hidden;
        }
        .license-hero::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,.05);
        }
        .license-hero::after {
            content: ''; position: absolute; bottom: -60px; right: 60px;
            width: 300px; height: 300px; border-radius: 50%;
            background: rgba(255,255,255,.03);
        }

        /* Countdown ring */
        .countdown-ring { position: relative; display: inline-flex; }
        .countdown-ring svg { transform: rotate(-90deg); }
        .ring-text {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }

        /* Detail rows */
        .detail-label { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; }
        .detail-value { font-size: .95rem; color: #212529; }

        /* Timeline */
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before {
            content: ''; position: absolute; left: 12px; top: 0; bottom: 0;
            width: 2px; background: #e9ecef;
        }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-icon {
            position: absolute; left: -30px; top: 2px;
            width: 24px; height: 24px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; z-index: 1;
        }
        .timeline-body {
            background: white; border-radius: 8px; padding: 12px 16px;
            border: 1px solid #f0f0f0;
        }
        .timeline-body:hover { border-color: #dee2e6; box-shadow: 0 2px 8px rgba(0,0,0,.05); }

        /* Status badge */
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600; }
        .license-key-box {
            font-family: monospace; background: #f8f9fa; border: 1px solid #dee2e6;
            border-radius: 8px; padding: 10px 14px; font-size: .85rem;
            word-break: break-all; position: relative;
        }
        .progress { border-radius: 10px; height: 8px; }
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
            <h5 class="mb-0">
                <i class="fas fa-certificate me-2"></i>
                <?php echo htmlspecialchars($lic['contract_name']); ?>
            </h5>
            <small class="text-muted">
                <a href="licenses.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Licenses
                </a>
            </small>
        </div>
        <div>
            <a href="license_edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm me-2">
                <i class="fas fa-edit me-1"></i>Edit License
            </a>
            <a href="licenses.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list me-1"></i>All Licenses
            </a>
        </div>
    </div>

    <div class="content-area">

        <!-- Hero Banner -->
        <div class="license-hero">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge bg-white text-dark px-3 py-2" style="font-size:.8rem;">
                            <?php echo ucwords(str_replace('_', ' ', $lic['license_type'])); ?>
                        </span>
                        <span class="status-badge" style="background:<?php echo $sc['bg']; ?>; color:<?php echo $sc['text']; ?>;">
                            <?php echo ucwords(str_replace('_', ' ', $lic['status'])); ?>
                        </span>
                        <?php if ($lic['auto_renewal'] == 'yes'): ?>
                        <span class="badge bg-info"><i class="fas fa-sync-alt me-1"></i>Auto-Renewal</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($lic['contract_name']); ?></h3>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($lic['vendor'] ?: '—'); ?></p>
                    <?php if ($lic['assigned_to']): ?>
                    <p class="mb-0 mt-1 opacity-75 small">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($lic['assigned_to']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-5 text-md-end mt-4 mt-md-0">
                    <!-- Countdown Ring -->
                    <div class="countdown-ring d-inline-flex">
                        <?php
                        $ring_pct   = max(0, min(100, $days > 0 ? ($days / max(1, $lic['alert_days'] > 0 ? 365 : 365)) * 100 : 0));
                        $circ       = 2 * 3.14159 * 52;
                        $dash_offset = $circ * (1 - min(1, max(0, $days / 365)));
                        ?>
                        <svg width="130" height="130" viewBox="0 0 130 130">
                            <circle cx="65" cy="65" r="52" fill="rgba(255,255,255,.1)" stroke="rgba(255,255,255,.15)" stroke-width="8"/>
                            <circle cx="65" cy="65" r="52" fill="none"
                                    stroke="<?php echo $days < 0 ? '#ff6b6b' : ($days <= 30 ? '#ffd43b' : '#69db7c'); ?>"
                                    stroke-width="8"
                                    stroke-dasharray="<?php echo $circ; ?>"
                                    stroke-dashoffset="<?php echo $days < 0 ? $circ : max(0, $circ - ($circ * min($days,365) / 365)); ?>"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="ring-text">
                            <?php if ($days < 0): ?>
                                <span style="font-size:1.4rem; font-weight:900; color:#ff6b6b;"><?php echo abs($days); ?></span>
                                <span style="font-size:.65rem; opacity:.8;">DAYS OVERDUE</span>
                            <?php else: ?>
                                <span style="font-size:1.6rem; font-weight:900;"><?php echo $days; ?></span>
                                <span style="font-size:.65rem; opacity:.8;">DAYS LEFT</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-2 text-white-50 small">
                        Due <?php echo date('d M Y', strtotime($lic['due_date'])); ?>
                    </div>
                </div>
            </div>

            <!-- Elapsed Progress Bar -->
            <?php if ($pct_elapsed > 0): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between small opacity-75 mb-1">
                    <span><?php echo $lic['purchase_date'] ? date('d M Y', strtotime($lic['purchase_date'])) : 'Start'; ?></span>
                    <span>License period <?php echo $pct_elapsed; ?>% elapsed</span>
                    <span><?php echo date('d M Y', strtotime($lic['due_date'])); ?></span>
                </div>
                <div class="progress" style="background:rgba(255,255,255,.15);">
                    <div class="progress-bar" role="progressbar"
                         style="width:<?php echo $pct_elapsed; ?>%; background:<?php echo $pct_elapsed >= 90 ? '#ff6b6b' : ($pct_elapsed >= 70 ? '#ffd43b' : '#69db7c'); ?>;">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">

            <!-- LEFT: Details -->
            <div class="col-lg-8">

                <!-- Core Details -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>License Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Contract Name</div>
                                <div class="detail-value fw-semibold"><?php echo htmlspecialchars($lic['contract_name']); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Vendor</div>
                                <div class="detail-value"><?php echo htmlspecialchars($lic['vendor'] ?: '—'); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Type</div>
                                <div class="detail-value">
                                    <span class="badge bg-secondary">
                                        <?php echo ucwords(str_replace('_', ' ', $lic['license_type'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Quantity / Seats</div>
                                <div class="detail-value"><?php echo number_format($lic['quantity'] ?? 1); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Assigned To</div>
                                <div class="detail-value"><?php echo htmlspecialchars($lic['assigned_to'] ?: '—'); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Auto-Renewal</div>
                                <div class="detail-value">
                                    <?php if ($lic['auto_renewal'] == 'yes'): ?>
                                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>Yes</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-times-circle me-1"></i>No</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label mb-1">Purchase Date</div>
                                <div class="detail-value">
                                    <?php echo $lic['purchase_date'] ? date('d M Y', strtotime($lic['purchase_date'])) : '—'; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label mb-1">Issue / Start Date</div>
                                <div class="detail-value">
                                    <?php echo $lic['issue_date'] ? date('d M Y', strtotime($lic['issue_date'])) : '—'; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label mb-1">Due / Expiry Date</div>
                                <div class="detail-value fw-semibold" style="color:<?php echo $days_color; ?>;">
                                    <?php echo date('d M Y', strtotime($lic['due_date'])); ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-label mb-1">Renewal Date</div>
                                <div class="detail-value">
                                    <?php echo $lic['renewal_date'] ? date('d M Y', strtotime($lic['renewal_date'])) : '—'; ?>
                                </div>
                            </div>
                            <?php if ($lic['amount']): ?>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Contract Amount</div>
                                <div class="detail-value fw-semibold text-primary" style="font-size:1.1rem;">
                                    <?php echo $lic['currency'] ?? 'USD'; ?> <?php echo number_format($lic['amount'], 2); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Alert Days</div>
                                <div class="detail-value"><?php echo $lic['alert_days']; ?> days before expiry</div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label mb-1">Added By</div>
                                <div class="detail-value"><?php echo htmlspecialchars($lic['created_by_name'] ?? '—'); ?></div>
                            </div>
                        </div>

                        <?php if ($lic['license_key']): ?>
                        <hr class="my-4">
                        <div class="detail-label mb-2">License Key / Serial Number</div>
                        <div class="license-key-box">
                            <span id="keyContent" style="filter:blur(4px); transition:.3s;">
                                <?php echo htmlspecialchars($lic['license_key']); ?>
                            </span>
                            <button class="btn btn-sm btn-outline-secondary float-end" onclick="toggleKey()" id="keyBtn">
                                <i class="fas fa-eye me-1"></i>Show
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if ($lic['notes']): ?>
                        <hr class="my-4">
                        <div class="detail-label mb-2">Notes</div>
                        <div class="text-muted" style="white-space:pre-line;"><?php echo htmlspecialchars($lic['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- History Timeline -->
                <div class="card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>License History</h6>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($history && $history->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($h = $history->fetch_assoc()):
                                $ac = $action_labels[$h['action']] ?? ['icon' => 'fa-circle', 'color' => 'secondary'];
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-icon bg-<?php echo $ac['color']; ?> text-white">
                                    <i class="fas <?php echo $ac['icon']; ?>"></i>
                                </div>
                                <div class="timeline-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="fw-semibold text-capitalize">
                                                <?php echo ucfirst($h['action']); ?>
                                            </span>
                                            <?php if ($h['old_due_date'] && $h['new_due_date']): ?>
                                            <span class="text-muted small ms-2">
                                                <?php echo date('d M Y', strtotime($h['old_due_date'])); ?>
                                                <i class="fas fa-arrow-right mx-1"></i>
                                                <?php echo date('d M Y', strtotime($h['new_due_date'])); ?>
                                            </span>
                                            <?php elseif ($h['new_due_date']): ?>
                                            <span class="text-muted small ms-2">
                                                Expiry: <?php echo date('d M Y', strtotime($h['new_due_date'])); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($h['amount']): ?>
                                            <span class="badge bg-light text-dark ms-2">
                                                <?php echo number_format($h['amount'], 2); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted ms-3 text-nowrap">
                                            <?php echo date('d M Y, H:i', strtotime($h['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php if ($h['notes']): ?>
                                    <div class="text-muted small mt-1"><?php echo htmlspecialchars($h['notes']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($h['actor_name']): ?>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($h['actor_name']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-history fa-2x mb-2 d-block opacity-25"></i>
                            No history recorded yet.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Summary Cards -->
            <div class="col-lg-4">

                <!-- Days Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Expiry Summary</h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-3" style="font-size:3rem; font-weight:900; color:<?php echo $days_color; ?>; line-height:1;">
                            <?php echo abs($days); ?>
                        </div>
                        <div class="fw-semibold mb-1" style="color:<?php echo $days_color; ?>;">
                            <?php echo $days < 0 ? 'Days Overdue' : 'Days Remaining'; ?>
                        </div>
                        <div class="text-muted small mb-3">
                            ≈ <?php echo abs($months); ?> month<?php echo abs($months) != 1 ? 's' : ''; ?>
                        </div>
                        <hr>
                        <div class="row text-center g-2">
                            <div class="col-6">
                                <div class="small text-muted">Due Date</div>
                                <div class="fw-semibold small"><?php echo date('d M Y', strtotime($lic['due_date'])); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Alert At</div>
                                <div class="fw-semibold small"><?php echo $lic['alert_days']; ?> days</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Info</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php
                        $rows = [
                            ['label' => 'Status',    'value' => '<span class="status-badge" style="background:'.$sc['bg'].';color:'.$sc['text'].';">'.ucwords(str_replace('_',' ',$lic['status'])).'</span>'],
                            ['label' => 'Type',      'value' => '<span class="badge bg-secondary">'.ucwords(str_replace('_',' ',$lic['license_type'])).'</span>'],
                            ['label' => 'Quantity',  'value' => number_format($lic['quantity'] ?? 1) . ' seat(s)'],
                            ['label' => 'Amount',    'value' => $lic['amount'] ? ($lic['currency'].' '.number_format($lic['amount'],2)) : '—'],
                            ['label' => 'Created',   'value' => date('d M Y', strtotime($lic['created_at']))],
                            ['label' => 'Updated',   'value' => date('d M Y', strtotime($lic['updated_at']))],
                        ];
                        foreach ($rows as $r): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small"><?php echo $r['label']; ?></span>
                            <span class="small"><?php echo $r['value']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body p-3 d-grid gap-2">
                        <a href="license_edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>Edit License
                        </a>
                        <a href="license_add.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus me-2"></i>Add New License
                        </a>
                        <a href="licenses.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list me-2"></i>All Licenses
                        </a>
                        <?php if (hasPermission('admin')): ?>
                        <hr>
                        <button class="btn btn-outline-danger btn-sm"
                                onclick="confirmDelete(<?php echo $id; ?>, '<?php echo addslashes($lic['contract_name']); ?>')">
                            <i class="fas fa-trash me-2"></i>Delete License
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <p class="mb-1">Are you sure you want to delete</p>
                <strong id="deleteItemName" class="d-block mb-3"></strong>
                <p class="text-muted small mb-0">This will also remove all license history. This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleKey() {
    const el  = document.getElementById('keyContent');
    const btn = document.getElementById('keyBtn');
    const hidden = el.style.filter === 'blur(4px)';
    el.style.filter  = hidden ? 'none' : 'blur(4px)';
    btn.innerHTML    = hidden ? '<i class="fas fa-eye-slash me-1"></i>Hide' : '<i class="fas fa-eye me-1"></i>Show';
}

function confirmDelete(id, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = 'license_delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>