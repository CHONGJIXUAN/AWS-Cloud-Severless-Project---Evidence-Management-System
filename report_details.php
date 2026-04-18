<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Get report ID from URL
$report_id = isset($_GET['reportId']) ? $_GET['reportId'] : null;

if (!$report_id) {
    die("Missing reportId");
}

// Get the report details (only approved reports for public view)
try {
    $result = $dynamodb->getItem([
        'TableName' => 'ScamReports',
        'Key' => [
            'reportId' => ['S' => $report_id]
        ]
    ]);

    $report = $result['Item'] ?? null;

    if ($report) {
        // Fetch the username by userId
        $userId = $report['userId']['S'] ?? null;

        if ($userId) {
            $userResult = $dynamodb->getItem([
                'TableName' => 'Users',
                'Key' => [
                    'userId' => ['S' => $userId]
                ],
                'ProjectionExpression' => 'username'
            ]);

            $report['username'] = $userResult['Item']['username']['S'] ?? 'Unknown';
        } else {
            $report['username'] = 'Unknown';
        }
    }

} catch (DynamoDbException $e) {
    die("Error fetching report: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['title']['S']); ?> - Global Scam Report Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt me-2"></i>
                Global Scam Report Center
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Browse Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['userId']['S'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']['S']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if($_SESSION['role']['S'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="user/dashboard.php">My Dashboard</a></li>
                                    <li><a class="dropdown-item" href="user/submit_report.php">Submit Report</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <!-- Back Button -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active">Report Details</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Main Report Content -->
                <div class="card report-card">
                    <div class="card-body p-4">
                        <!-- Report Header -->
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h1 class="card-title mb-2"><?php echo htmlspecialchars($report['title']['S']); ?></h1>
                                <div class="d-flex align-items-center gap-3 text-muted">
                                    <span>
                                        <i class="fas fa-user me-1"></i>
                                        Reported by: <strong><?php echo htmlspecialchars($report['username']); ?></strong>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('F j, Y', strtotime($report['created_at']['S'])); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="badge bg-danger fs-6"><?php echo htmlspecialchars($report['scam_type']['S']); ?></span>
                        </div>

                        <!-- Key Information -->
                        <div class="row mb-4">
                            <?php if ($report['amount_lost'] > 0): ?>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-dollar-sign me-1"></i>Financial Loss</h6>
                                        <strong><?php echo format_currency($report['amount_lost']['N']); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['location'])): ?>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-map-marker-alt me-1"></i>Location</h6>
                                        <strong><?php echo htmlspecialchars($report['location']['S']); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['date_occurred'])): ?>
                                <div class="col-md-6">
                                    <div class="alert alert-secondary">
                                        <h6><i class="fas fa-calendar-alt me-1"></i>Date Occurred</h6>
                                        <strong><?php echo date('F j, Y', strtotime($report['date_occurred']['S'])); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <h4><i class="fas fa-align-left me-2"></i>What Happened</h4>
                            <div class="border-start border-danger border-3 ps-3">
                                <p class="lead"><?php echo nl2br(htmlspecialchars($report['description']['S'])); ?></p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <?php if (!empty($report['contact_info']['S'])): ?>
                            <div class="mb-4">
                                <h4><i class="fas fa-phone me-2"></i>Scammer Contact Information</h4>
                                <div class="alert alert-danger">
                                    <p class="mb-0">
                                        <strong>Warning:</strong> The following contact information was used by the scammer. 
                                        Do not contact these numbers/addresses.
                                    </p>
                                </div>
                                <div class="border-start border-danger border-3 ps-3">
                                    <p><?php echo nl2br(htmlspecialchars($report['contact_info']['S'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Evidence -->
                        <?php if (!empty($report['evidence']['S'])): ?>
                        <?php
                        $ext = strtolower(pathinfo($report['evidence']['S'], PATHINFO_EXTENSION));
                        $image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        ?>
                        
                        <?php if (in_array($ext, $image_types)): ?>
                            <p><strong>Evidence Image:</strong></p>
                            <img src="<?= htmlspecialchars($report['evidence']['S']) ?>" alt="Evidence Image" class="img-fluid rounded border" style="max-width: 100%; height: auto;">
                        <?php else: ?>
                            <p><strong>Evidence File:</strong>
                                <a href="<?= htmlspecialchars($report['evidence']['S']) ?>" target="_blank">Download/View Evidence</a>
                            </p>
                        <?php endif; ?>
                        <?php else: ?>
                            <p><strong>Evidence:</strong> No file uploaded.</p>
                        <?php endif; ?>

                        <!-- Report Meta -->
                        <div class="border-top pt-3 mt-4">
                            <small class="text-muted">
                                Report ID: #<?php echo $report['reportId']['S']; ?> | 
                                Submitted: <?php echo time_elapsed_string($report['created_at']['S']); ?> | 
                                Status: <span class="badge bg-success">Verified</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Warning Box -->
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Stay Protected</h5>
                        <p class="mb-0">
                            If you encounter a similar scam, do not engage with the scammers. 
                            Report it to local authorities and consider sharing your experience here.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6>Help Others</h6>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="user/submit_report.php" class="btn btn-danger btn-sm w-100 mb-2">
                                <i class="fas fa-plus me-1"></i>Report Similar Scam
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-danger btn-sm w-100 mb-2">
                                <i class="fas fa-user-plus me-1"></i>Join to Report Scams
                            </a>
                        <?php endif; ?>
                        <a href="reports.php" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fas fa-search me-1"></i>Browse More Reports
                        </a>
                    </div>
                </div>

                <!-- Related Reports -->
                <?php
                // Get related reports of the same type
                $related_reports = [];

                try {
                    $result = $dynamodb->scan([
                        'TableName' => 'ScamReports',
                        'FilterExpression' => '#type = :type AND reportId <> :currentId AND #status = :status',
                        'ExpressionAttributeNames' => [
                            '#type' => 'scam_type',
                            '#status' => 'status'
                        ],
                        'ExpressionAttributeValues' => [
                            ':type' => ['S' => $report['scam_type']['S']],
                            ':currentId' => ['S' => $report['reportId']['S']],
                            ':status' => ['S' => 'approved']
                        ],
                        'Limit' => 5
                    ]);

                    $related_reports = $result['Items'] ?? [];

                    // Optional: sort by created_at descending (manually if needed)
                    usort($related_reports, function ($a, $b) {
                        return strtotime($b['created_at']['S']) <=> strtotime($a['created_at']['S']);
                    });

                } catch (DynamoDbException $e) {
                    error_log("Error fetching related reports: " . $e->getMessage());
                }
                ?>
                
                <?php if (!empty($related_reports)): ?>
                    <div class="card">
                        <div class="card-body">
                            <h6>Related <?php echo htmlspecialchars($report['scam_type']['S']); ?> Reports</h6>
                            <div class="list-group list-group-flush">
                                <?php foreach ($related_reports as $related): ?>
                                    <a href="report_details.php?id=<?php echo $related['reportId']['S']; ?>" 
                                       class="list-group-item list-group-item-action px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <p class="mb-1"><?php echo htmlspecialchars(substr($related['title']['S'], 0, 50)) . (strlen($related['title']['S']) > 50 ? '...' : ''); ?></p>
                                            <small><?php echo date('M j', strtotime($related['created_at']['S'])); ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Global Scam Report Center</h5>
                    <p class="text-muted">Protecting communities from fraud, one report at a time.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; 2025 Global Scam Report Center. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
