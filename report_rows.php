<?php
// Report table rows based on report type
if ($data && $data->num_rows > 0) {
    while ($row = $data->fetch_assoc()) {
        echo '<tr>';
        
        switch ($report_type) {
            case 'all_assets':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['staff_name'] ?: 'Unassigned') . '</td>';
                echo '<td><span class="badge bg-primary">' . ucfirst($row['status']) . '</span></td>';
                echo '<td>' . ($row['last_inspection_date'] ? formatDate($row['last_inspection_date']) : 'Never') . '</td>';
                echo '<td>' . ($row['inspection_count'] ?: '0') . '</td>';
                break;
                
            case 'by_status':
                echo '<td>' . ucfirst($row['status']) . '</td>';
                echo '<td>' . $row['count'] . '</td>';
                echo '<td>' . formatCurrency($row['total_value']) . '</td>';
                break;
                
            case 'by_category':
                echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                echo '<td>' . $row['count'] . '</td>';
                echo '<td>' . formatCurrency($row['total_value']) . '</td>';
                break;
                
            case 'assignments':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . ' / ' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['staff_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['staff_department'] ?: 'N/A') . '</td>';
                echo '<td>' . formatDate($row['assigned_date']) . '</td>';
                break;
                
            case 'warranty_expiring':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['staff_name'] ?: 'Unassigned') . '</td>';
                echo '<td>' . formatDate($row['warranty_expiry']) . '</td>';
                echo '<td><span class="badge bg-warning">' . $row['days_left'] . ' days</span></td>';
                break;
                
            case 'by_location':
                echo '<td>' . htmlspecialchars($row['floor']) . '</td>';
                echo '<td>' . htmlspecialchars($row['location']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . $row['count'] . '</td>';
                break;
                
            case 'by_department':
                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                echo '<td>' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . $row['count'] . '</td>';
                echo '<td>' . formatCurrency($row['total_value']) . '</td>';
                break;
                
            case 'all_inspections':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . ' / ' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . formatDate($row['inspection_date']) . '</td>';
                echo '<td><span class="badge bg-info">' . $row['inspection_quarter'] . ' ' . $row['inspection_year'] . '</span></td>';
                echo '<td>' . htmlspecialchars($row['inspector_name'] ?: $row['inspector_username']) . '</td>';
                echo '<td>' . ucfirst($row['unit_type']) . '</td>';
                $cond_colors = ['excellent' => 'success', 'good' => 'primary', 'fair' => 'warning', 'poor' => 'danger'];
                echo '<td><span class="badge bg-' . ($cond_colors[$row['overall_condition']] ?? 'secondary') . '">' . ucfirst($row['overall_condition']) . '</span></td>';
                $stat_colors = ['completed' => 'success', 'failed' => 'danger', 'pending' => 'warning'];
                echo '<td><span class="badge bg-' . $stat_colors[$row['status']] . '">' . ucfirst($row['status']) . '</span></td>';
                echo '<td><a href="inspection_detail.php?id=' . $row['id'] . '" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td>';
                break;
                
            case 'inspections_by_quarter':
                echo '<td>' . $row['inspection_quarter'] . '</td>';
                echo '<td>' . $row['inspection_year'] . '</td>';
                echo '<td><strong>' . $row['total_inspections'] . '</strong></td>';
                echo '<td><span class="badge bg-success">' . $row['completed'] . '</span></td>';
                echo '<td><span class="badge bg-danger">' . $row['failed'] . '</span></td>';
                echo '<td>' . $row['excellent'] . '</td>';
                echo '<td>' . $row['good'] . '</td>';
                echo '<td>' . $row['fair'] . '</td>';
                echo '<td>' . $row['poor'] . '</td>';
                break;
                
            case 'inspections_failed':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . ' / ' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . formatDate($row['inspection_date']) . '</td>';
                echo '<td>' . $row['inspection_quarter'] . ' ' . $row['inspection_year'] . '</td>';
                echo '<td>' . htmlspecialchars($row['inspector_name']) . '</td>';
                // Determine failure reason
                $reasons = [];
                if ($row['powers_on'] == 'no') $reasons[] = 'Power issues';
                if ($row['passwords_removed'] == 'no') $reasons[] = 'Passwords not removed';
                if ($row['company_data_removed'] == 'no') $reasons[] = 'Data not removed';
                echo '<td>' . implode(', ', $reasons) . '</td>';
                echo '<td><a href="inspection_detail.php?id=' . $row['id'] . '" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td>';
                break;
                
            case 'inspections_overdue':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . ' / ' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . formatDate($row['scheduled_date']) . '</td>';
                echo '<td>' . $row['scheduled_quarter'] . ' ' . $row['scheduled_year'] . '</td>';
                echo '<td><span class="badge bg-danger">' . $row['days_overdue'] . ' days</span></td>';
                echo '<td><a href="inspection_add.php?asset_id=' . $row['asset_id'] . '" class="btn btn-xs btn-primary">Inspect Now</a></td>';
                break;
                
            case 'assets_never_inspected':
                echo '<td>' . htmlspecialchars($row['asset_tag']) . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?: 'N/A') . ' / ' . htmlspecialchars($row['section'] ?: 'N/A') . '</td>';
                echo '<td>' . formatDate($row['purchase_date']) . '</td>';
                echo '<td>' . $row['days_since_purchase'] . ' days</td>';
                echo '<td><a href="inspection_add.php?asset_id=' . $row['id'] . '" class="btn btn-xs btn-primary">Inspect</a></td>';
                break;
                
            case 'inspection_component_issues':
                $total_issues = $row['missing_count'] + $row['damaged_count'] + $row['not_working_count'];
                echo '<td><strong>' . htmlspecialchars($row['component_name']) . '</strong></td>';
                echo '<td>' . $row['total_checks'] . '</td>';
                echo '<td>' . ($row['missing_count'] > 0 ? '<span class="badge bg-warning">' . $row['missing_count'] . '</span>' : '0') . '</td>';
                echo '<td>' . ($row['damaged_count'] > 0 ? '<span class="badge bg-danger">' . $row['damaged_count'] . '</span>' : '0') . '</td>';
                echo '<td>' . ($row['not_working_count'] > 0 ? '<span class="badge bg-danger">' . $row['not_working_count'] . '</span>' : '0') . '</td>';
                echo '<td><strong>' . $total_issues . '</strong></td>';
                break;
        }
        
        echo '</tr>';
    }
} else {
    // No data found
    $colspan = 10; // Adjust based on report type
    echo '<tr><td colspan="' . $colspan . '" class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
            No data found for this report
          </td></tr>';
}
?>