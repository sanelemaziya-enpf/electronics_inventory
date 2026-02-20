<?php
/**
 * Schedule Quarterly Inspections
 * This script can be run manually or via cron job to schedule inspections for all active assets
 * 
 * Usage: 
 * - Manual: Visit this page in browser (admin only)
 * - Cron: php schedule_inspections.php
 */

require_once 'config.php';

// If running via web, check admin permission
if (php_sapi_name() !== 'cli') {
    checkLogin();
    if (!hasPermission('admin')) {
        die("Access denied. Admin only.");
    }
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$scheduled_count = 0;
$skipped_count = 0;
$messages = [];

// Get all active assets
$assets = $conn->query("SELECT id, asset_tag, name FROM assets WHERE status != 'retired'");

// Define quarter dates (you can adjust these)
$quarter_dates = [
    'Q1' => $year . '-03-31',  // End of March
    'Q2' => $year . '-06-30',  // End of June
    'Q3' => $year . '-09-30',  // End of September
    'Q4' => $year . '-12-31',  // End of December
];

while ($asset = $assets->fetch_assoc()) {
    foreach ($quarter_dates as $quarter => $date) {
        // Check if schedule already exists
        $check = $conn->query("SELECT id FROM inspection_schedules 
                              WHERE asset_id = {$asset['id']} 
                              AND scheduled_quarter = '$quarter' 
                              AND scheduled_year = $year");
        
        if ($check->num_rows == 0) {
            // Create schedule
            $stmt = $conn->prepare("INSERT INTO inspection_schedules 
                                   (asset_id, scheduled_quarter, scheduled_year, scheduled_date, status)
                                   VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isis", $asset['id'], $quarter, $year, $date);
            
            if ($stmt->execute()) {
                $scheduled_count++;
                $messages[] = "Scheduled {$asset['asset_tag']} for $quarter $year";
            }
            $stmt->close();
        } else {
            $skipped_count++;
        }
    }
}

// Output results
if (php_sapi_name() === 'cli') {
    echo "Inspection Scheduling Complete\n";
    echo "========================\n";
    echo "Year: $year\n";
    echo "Scheduled: $scheduled_count\n";
    echo "Skipped (already exists): $skipped_count\n";
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Schedule Inspections - <?php echo APP_NAME; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Schedule Quarterly Inspections</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Scheduling Complete</h6>
                                <hr>
                                <p class="mb-2"><strong>Year:</strong> <?php echo $year; ?></p>
                                <p class="mb-2"><strong>Schedules Created:</strong> <?php echo $scheduled_count; ?></p>
                                <p class="mb-0"><strong>Skipped (already exist):</strong> <?php echo $skipped_count; ?></p>
                            </div>

                            <div class="mt-4">
                                <h6>Schedule for Different Year</h6>
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-auto">
                                        <label class="form-label">Year:</label>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" name="year" class="form-control" value="<?php echo $year + 1; ?>" min="2020" max="2050">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">Schedule</button>
                                    </div>
                                </form>
                            </div>

                            <hr class="my-4">

                            <div class="d-grid gap-2">
                                <a href="inspections.php" class="btn btn-outline-primary">
                                    <i class="fas fa-clipboard-list me-2"></i>View Inspections
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Quarterly Inspections</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">This system automatically schedules inspections for all active assets on a quarterly basis:</p>
                            <ul class="mb-0">
                                <li><strong>Q1:</strong> Scheduled for March 31</li>
                                <li><strong>Q2:</strong> Scheduled for June 30</li>
                                <li><strong>Q3:</strong> Scheduled for September 30</li>
                                <li><strong>Q4:</strong> Scheduled for December 31</li>
                            </ul>
                            <hr>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong>Tip:</strong> Run this script at the beginning of each year to schedule all inspections. 
                                You can also set up a cron job to run it automatically.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>