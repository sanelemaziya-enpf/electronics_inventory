<?php
// Report table headers based on report type
switch ($report_type) {
    case 'all_assets':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Name</th>
                <th>Category</th>
                <th>Department</th>
                <th>Section</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Last Inspection</th>
                <th>Inspection Count</th>
              </tr>';
        break;
        
    case 'by_status':
        echo '<tr>
                <th>Status</th>
                <th>Count</th>
                <th>Total Value</th>
              </tr>';
        break;
        
    case 'by_category':
        echo '<tr>
                <th>Category</th>
                <th>Asset Count</th>
                <th>Total Value</th>
              </tr>';
        break;
        
    case 'assignments':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Asset Name</th>
                <th>Asset Dept/Section</th>
                <th>Assigned To</th>
                <th>Staff Department</th>
                <th>Assigned Date</th>
              </tr>';
        break;
        
    case 'warranty_expiring':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Name</th>
                <th>Department</th>
                <th>Section</th>
                <th>Assigned To</th>
                <th>Warranty Expiry</th>
                <th>Days Left</th>
              </tr>';
        break;
        
    case 'by_location':
        echo '<tr>
                <th>Floor</th>
                <th>Location</th>
                <th>Department</th>
                <th>Section</th>
                <th>Asset Count</th>
              </tr>';
        break;
        
    case 'by_department':
        echo '<tr>
                <th>Department</th>
                <th>Section</th>
                <th>Asset Count</th>
                <th>Total Value</th>
              </tr>';
        break;
        
    case 'all_inspections':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Asset Name</th>
                <th>Dept/Section</th>
                <th>Inspection Date</th>
                <th>Quarter/Year</th>
                <th>Inspector</th>
                <th>Unit Type</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>';
        break;
        
    case 'inspections_by_quarter':
        echo '<tr>
                <th>Quarter</th>
                <th>Year</th>
                <th>Total Inspections</th>
                <th>Completed</th>
                <th>Failed</th>
                <th>Excellent</th>
                <th>Good</th>
                <th>Fair</th>
                <th>Poor</th>
              </tr>';
        break;
        
    case 'inspections_failed':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Asset Name</th>
                <th>Dept/Section</th>
                <th>Inspection Date</th>
                <th>Quarter/Year</th>
                <th>Inspector</th>
                <th>Reason</th>
                <th>Actions</th>
              </tr>';
        break;
        
    case 'inspections_overdue':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Asset Name</th>
                <th>Category</th>
                <th>Dept/Section</th>
                <th>Scheduled Date</th>
                <th>Quarter/Year</th>
                <th>Days Overdue</th>
                <th>Actions</th>
              </tr>';
        break;
        
    case 'assets_never_inspected':
        echo '<tr>
                <th>Asset Tag</th>
                <th>Name</th>
                <th>Category</th>
                <th>Dept/Section</th>
                <th>Purchase Date</th>
                <th>Days Since Purchase</th>
                <th>Actions</th>
              </tr>';
        break;
        
    case 'inspection_component_issues':
        echo '<tr>
                <th>Component</th>
                <th>Total Checks</th>
                <th>Missing Count</th>
                <th>Damaged Count</th>
                <th>Not Working Count</th>
                <th>Total Issues</th>
              </tr>';
        break;
}
?>