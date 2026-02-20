<?php
require_once 'config.php';
checkLogin();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="asset_import_template_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { 
            background-color: #667eea; 
            color: white; 
            font-weight: bold; 
            padding: 10px;
            border: 1px solid #ddd;
        }
        td { 
            padding: 8px; 
            border: 1px solid #ddd;
        }
        .example { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Electronics Inventory - Asset Import Template</h2>
    <p>Instructions:</p>
    <ul>
        <li>Fill in the data starting from row 3 (below the example row)</li>
        <li>Do not modify the column headers</li>
        <li>Name column is required</li>
        <li>Date format: YYYY-MM-DD (e.g., 2024-01-15)</li>
        <li>Status options: available, assigned, maintenance, retired</li>
    </ul>
    
    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Model</th>
                <th>Serial Number</th>
                <th>Purchase Date</th>
                <th>Purchase Cost</th>
                <th>Location</th>
                <th>Floor</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr class="example">
                <td>Dell Laptop</td>
                <td>Laptops</td>
                <td>Latitude 7420</td>
                <td>SN123456789</td>
                <td>2024-01-15</td>
                <td>45000</td>
                <td>IT Office</td>
                <td>3rd Floor</td>
                <td>IT Department</td>
                <td>available</td>
            </tr>
            <tr class="example">
                <td>HP Monitor</td>
                <td>Monitors</td>
                <td>E24 G4</td>
                <td>MON987654</td>
                <td>2024-02-20</td>
                <td>8500</td>
                <td>Finance Office</td>
                <td>2nd Floor</td>
                <td>Finance</td>
                <td>available</td>
            </tr>
            <!-- Empty rows for data entry -->
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>
    
    <h3>Column Descriptions:</h3>
    <table border="1" style="margin-top: 20px;">
        <tr>
            <th>Column</th>
            <th>Description</th>
            <th>Required</th>
        </tr>
        <tr>
            <td><strong>Name</strong></td>
            <td>Asset name or description</td>
            <td>YES</td>
        </tr>
        <tr>
            <td><strong>Category</strong></td>
            <td>Asset category (will be created if doesn\'t exist)</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Model</strong></td>
            <td>Model number or name</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Serial Number</strong></td>
            <td>Unique serial number</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Purchase Date</strong></td>
            <td>Date of purchase (YYYY-MM-DD format)</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Purchase Cost</strong></td>
            <td>Cost in Emalangeni (numbers only)</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Location</strong></td>
            <td>Physical location of asset</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Floor</strong></td>
            <td>Floor location</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Department</strong></td>
            <td>Department owning the asset</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>available, assigned, maintenance, or retired</td>
            <td>No</td>
        </tr>
    </table>
    
    <p style="margin-top: 20px;">
        <strong>Note:</strong> Asset tags will be automatically generated during import.
    </p>
</body>
</html>';

exit();
?>