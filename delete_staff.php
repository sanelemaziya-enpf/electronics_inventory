<?php
require_once 'config.php';
checkLogin();

// Only super_admin can delete staff
if (!hasPermission('super_admin')) {
    $_SESSION['error'] = "You don't have permission to delete staff members.";
    header("Location: staff.php");
    exit();
}

$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get staff member details
$staff = $conn->query("SELECT * FROM staff WHERE id = $staff_id")->fetch_assoc();

if (!$staff) {
    $_SESSION['error'] = "Staff member not found.";
    header("Location: staff.php");
    exit();
}

// Check if staff has active assignments
$active_assignments = $conn->query("SELECT COUNT(*) as count FROM asset_assignments WHERE staff_id = $staff_id AND status = 'active'")->fetch_assoc()['count'];

if ($active_assignments > 0) {
    $_SESSION['error'] = "Cannot delete staff member <strong>{$staff['full_name']}</strong>. They have {$active_assignments} active asset assignment(s). Please return all assets before deleting.";
    header("Location: edit_staff.php?id=$staff_id");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Store staff name for logging
    $staff_name = $staff['full_name'];
    $staff_code = $staff['staff_id'];
    
    // Delete the staff member
    $delete_stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $delete_stmt->bind_param("i", $staff_id);
    $delete_stmt->execute();
    
    // Log the deletion
    logActivity($_SESSION['user_id'], 'DELETE', 'staff', $staff_id, "Deleted staff member: $staff_name ($staff_code)");
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Staff member <strong>$staff_name</strong> has been successfully deleted.";
    header("Location: staff.php");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error'] = "Error deleting staff member: " . $e->getMessage();
    header("Location: edit_staff.php?id=$staff_id");
    exit();
}
?>