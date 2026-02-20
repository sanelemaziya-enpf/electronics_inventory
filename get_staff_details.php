<?php
require_once 'config.php';
checkLogin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get staff member details
$staff = $conn->query("SELECT s.*, u.full_name as created_by_name 
                       FROM staff s 
                       LEFT JOIN users u ON s.created_by = u.id 
                       WHERE s.id = $user_id")->fetch_assoc();

if (!$staff) {
    echo '<div class="alert alert-danger">Staff member not found.</div>';
    exit();
}

// Get active assignments
$assignments = $conn->query("SELECT aa.*, a.asset_tag, a.name as asset_name, a.model, c.name as category_name
                            FROM asset_assignments aa
                            JOIN assets a ON aa.asset_id = a.id
                            LEFT JOIN categories c ON a.category_id = c.id
                            WHERE aa.staff_id = $user_id AND aa.status = 'active'
                            ORDER BY aa.assigned_date DESC");

// Get assignment history
$history = $conn->query("SELECT aa.*, a.asset_tag, a.name as asset_name
                        FROM asset_assignments aa
                        JOIN assets a ON aa.asset_id = a.id
                        WHERE aa.staff_id = $user_id AND aa.status = 'returned'
                        ORDER BY aa.return_date DESC
                        LIMIT 5");

// Get statistics
$stats = [
    'total_assignments' => $conn->query("SELECT COUNT(*) as count FROM asset_assignments WHERE staff_id = $user_id")->fetch_assoc()['count'],
    'active_assignments' => $conn->query("SELECT COUNT(*) as count FROM asset_assignments WHERE staff_id = $user_id AND status='active'")->fetch_assoc()['count'],
    'returned_assignments' => $conn->query("SELECT COUNT(*) as count FROM asset_assignments WHERE staff_id = $user_id AND status='returned'")->fetch_assoc()['count']
];
?>

<div class="row">
    <!-- Staff Information -->
    <div class="col-md-4">
        <div class="text-center mb-4">
            <div class="mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                <i class="fas fa-user"></i>
            </div>
            <h5><?php echo htmlspecialchars($staff['full_name']); ?></h5>
            <p class="text-muted mb-2"><?php echo htmlspecialchars($staff['staff_id']); ?></p>
            <?php if ($staff['position']): ?>
            <span class="badge bg-info">
                <?php echo htmlspecialchars($staff['position']); ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <?php if (hasPermission('admin')): ?>
        <div class="d-grid gap-2 mb-3">
            <a href="edit_staff.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit Staff Member
            </a>
            <?php if (hasPermission('super_admin')): ?>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteStaffModal">
                <i class="fas fa-trash me-2"></i>Delete Staff Member
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card bg-light mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Contact Information</h6>
                <div class="mb-3">
                    <small class="text-muted d-block">Email</small>
                    <a href="mailto:<?php echo htmlspecialchars($staff['email']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($staff['email']); ?>
                    </a>
                </div>
                <?php if ($staff['phone']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Phone</small>
                    <a href="tel:<?php echo htmlspecialchars($staff['phone']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($staff['phone']); ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($staff['department']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Department</small>
                    <span class="badge bg-primary">
                        <?php echo htmlspecialchars($staff['department']); ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($staff['section']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Section</small>
                    <span class="badge bg-success">
                        <?php echo htmlspecialchars($staff['section']); ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($staff['floor']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Floor</small>
                    <?php echo htmlspecialchars($staff['floor']); ?>
                </div>
                <?php endif; ?>
                <?php if ($staff['position']): ?>
                <div class="mb-0">
                    <small class="text-muted d-block">Position</small>
                    <?php echo htmlspecialchars($staff['position']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Assignment Statistics</h6>
                <div class="row g-2 text-center">
                    <div class="col-12">
                        <div class="bg-white p-3 rounded">
                            <h4 class="mb-0 text-primary"><?php echo $stats['active_assignments']; ?></h4>
                            <small class="text-muted">Active Assets</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white p-2 rounded">
                            <h5 class="mb-0"><?php echo $stats['total_assignments']; ?></h5>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-white p-2 rounded">
                            <h5 class="mb-0"><?php echo $stats['returned_assignments']; ?></h5>
                            <small class="text-muted">Returned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments and History -->
    <div class="col-md-8">
        <!-- Active Assignments -->
        <h6 class="mb-3"><i class="fas fa-box me-2"></i>Active Asset Assignments</h6>
        <?php if ($assignments->num_rows > 0): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Asset Tag</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Assigned Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($assignment['asset_tag']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($assignment['asset_name']); ?>
                                <?php if ($assignment['model']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($assignment['model']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($assignment['category_name']); ?></td>
                            <td><?php echo formatDate($assignment['assigned_date']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>No active asset assignments
            </div>
        <?php endif; ?>

        <!-- Assignment History -->
        <h6 class="mb-3"><i class="fas fa-history me-2"></i>Recent Assignment History</h6>
        <?php if ($history->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($item = $history->fetch_assoc()): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($item['asset_tag']); ?></strong> - 
                            <?php echo htmlspecialchars($item['asset_name']); ?>
                            <div class="small text-muted mt-1">
                                <i class="fas fa-calendar me-1"></i>
                                Assigned: <?php echo formatDate($item['assigned_date']); ?> | 
                                Returned: <?php echo formatDate($item['return_date']); ?>
                            </div>
                        </div>
                        <span class="badge bg-secondary">Returned</span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary">
                <i class="fas fa-info-circle me-2"></i>No assignment history available
            </div>
        <?php endif; ?>

        <!-- Additional Information -->
        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">
                <strong>Account Created:</strong> <?php echo formatDate($staff['created_at']); ?>
                <?php if ($staff['created_by_name']): ?>
                | <strong>Created By:</strong> <?php echo htmlspecialchars($staff['created_by_name']); ?>
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if (hasPermission('super_admin')): ?>
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete <strong><?php echo htmlspecialchars($staff['full_name']); ?></strong>?</p>
                <?php if ($stats['active_assignments'] > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Cannot Delete:</strong> This staff member has <?php echo $stats['active_assignments']; ?> active asset assignment(s). Please return all assets before deleting.
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. Assignment history will be preserved but this staff member will no longer be available.
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <?php if ($stats['active_assignments'] == 0): ?>
                <a href="delete_staff.php?id=<?php echo $user_id; ?>" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete Permanently
                </a>
                <?php else: ?>
                <button type="button" class="btn btn-danger" disabled>
                    <i class="fas fa-ban me-2"></i>Cannot Delete
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>