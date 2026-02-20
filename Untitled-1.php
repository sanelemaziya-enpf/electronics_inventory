<!-- Assign Asset Modal -->
    <div class="modal fade" id="assignAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Assign Asset to Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Select Asset <span class="text-danger">*</span></label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">Choose available asset...</option>
                                    <?php while ($asset = $available_assets->fetch_assoc()): ?>
                                    <option value="<?php echo $asset['id']; ?>">
                                        <?php echo htmlspecialchars($asset['asset_tag']); ?> - 
                                        <?php echo htmlspecialchars($asset['name']); ?>
                                        <?php echo $asset['model'] ? ' (' . htmlspecialchars($asset['model']) . ')' : ''; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Assign To <span class="text-danger">*</span></label>
                                <select name="assigned_to_type" id="assignToType" class="form-select mb-2" required>
                                    <option value="">Choose type...</option>
                                    <option value="staff">Staff Member (Company Employee)</option>
                                    <option value="user">System User (Admin/Staff with login)</option>
                                </select>
                                
                                <div id="staffSelect" style="display:none;">
                                    <select name="assigned_to_id" class="form-select">
                                        <option value="">Choose staff member...</option>
                                        <?php while ($staff = $active_staff->fetch_assoc()): ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                            (<?php echo htmlspecialchars($staff['staff_id']); ?>)
                                            <?php if ($staff['position']): ?>
                                            - <?php echo htmlspecialchars($staff['position']); ?>
                                            <?php endif; ?>
                                            <?php if ($staff['department']): ?>
                                            - <?php echo htmlspecialchars($staff['department']); ?>
                                            <?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div id="userSelect" style="display:none;">
                                    <select name="assigned_to_id" class="form-select">
                                        <option value="">Choose system user...</option>
                                        <?php while ($user = $active_users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                            <?php if ($user['department']): ?>
                                            - <?php echo htmlspecialchars($user['department']); ?>
                                            <?php endif; ?>
                                            <?php if ($user['floor']): ?>
                                            (<?php echo htmlspecialchars($user['floor']); ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                <input type="date" name="assigned_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes about this assignment..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_asset" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Assign Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide assignment type dropdowns
        document.getElementById('assignToType').addEventListener('change', function() {
            const staffSelect = document.getElementById('staffSelect');
            const userSelect = document.getElementById('userSelect');
            
            staffSelect.style.display = 'none';
            userSelect.style.display = 'none';
            
            // Disable all selects first
            staffSelect.querySelector('select').disabled = true;
            userSelect.querySelector('select').disabled = true;
            
            if (this.value === 'staff') {
                staffSelect.style.display = 'block';
                staffSelect.querySelector('select').disabled = false;
            } else if (this.value === 'user') {
                userSelect.style.display = 'block';
                userSelect.querySelector('select').disabled = false;
            }
        });
        
        function filterAssignments(status) {
            const rows = document.querySelectorAll('#assignmentsTable tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else if (row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>





// Handle New Assignment
if (isset($_POST['assign_asset'])) {
    $asset_id = intval($_POST['asset_id']);
    $assigned_to_type = sanitize($_POST['assigned_to_type']); // 'user' or 'staff'
    $assigned_to_id = intval($_POST['assigned_to_id']);
    $assigned_date = sanitize($_POST['assigned_date']);
    $notes = sanitize($_POST['notes']);
    
    // Check if asset is available
    $asset = $conn->query("SELECT status, name FROM assets WHERE id = $asset_id")->fetch_assoc();
    
    if ($asset['status'] !== 'available') {
        $message = "Asset is not available for assignment.";
        $message_type = "danger";
    } else {
        // Create assignment
        if ($assigned_to_type === 'staff') {
            $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, assigned_to_type, staff_id, assigned_by, assigned_date, notes) VALUES (?, 'staff', ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $asset_id, $assigned_to_id, $_SESSION['user_id'], $assigned_date, $notes);
        } else {
            $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, assigned_to_type, assigned_to, assigned_by, assigned_date, notes) VALUES (?, 'user', ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $asset_id, $assigned_to_id, $_SESSION['user_id'], $assigned_date, $notes);
        }
        
        if ($stmt->execute()) {
            // Update asset status
            $conn->query("UPDATE assets SET status='assigned' WHERE id=$asset_id");
            
            $message = "Asset assigned successfully!";
            $message_type = "success";
            logActivity($_SESSION['user_id'], 'CREATE', 'asset_assignments', $conn->insert_id, "Assigned asset: {$asset['name']}");
        } else {
            $message = "Error assigning asset: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}
