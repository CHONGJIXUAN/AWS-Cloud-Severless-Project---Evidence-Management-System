<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Check if user is logged in and is admin
require_login();
require_admin();

$all_reports = [];

try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
    ]);

    $scam_reports = $result['Items'];

    // Sort by created_at descending
    usort($scam_reports, function($a, $b) {
        return strtotime($b['created_at']['S']) < strtotime($a['created_at']['S']) ? 1 : -1;
    });

    // For each report, fetch the corresponding user's username
    foreach ($scam_reports as $report) {
        $userId = $report['userId']['S'];

        // Fetch user by user_id
        try {
            $userResult = $dynamodb->getItem([
                'TableName' => 'Users',
                'Key' => [
                    'userId' => ['S' => $userId],
                ]
            ]);

            $username = $userResult['Item']['username']['S'] ?? 'Unknown';

            // Append username into the report
            $report['username'] = ['S' => $username];
            $all_reports[] = $report;

        } catch (DynamoDbException $e) {
            // Optional: log or skip user fetching error
            $report['username'] = ['S' => 'Unknown'];
            $all_reports[] = $report;
        }
    }

} catch (DynamoDbException $e) {
    echo "Failed to fetch reports: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - Global Scam Report Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light admin-dashboard">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-shield-alt me-2"></i>
                Global Scam Report Center
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">View Home Page</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">All Scam Reports</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Reporter</th>
                    <th scope="col">Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($all_reports)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No reports available</td>
                </tr>
            <?php else: ?>
                <?php foreach ($all_reports as $report): ?>
                    <tr class="text-center">
                        <td class="text-start">
                            <?= htmlspecialchars(substr($report['title']['S'], 0, 40)) ?>
                            <?= strlen($report['title']['S']) > 40 ? '<span class="text-muted">...</span>' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($report['username']['S'] ?? 'Unknown') ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($report['scam_type']['S']) ?></span></td>
                        <td>
                            <?php
                                $status = $report['status']['S'];
                                $badgeClass = match ($status) {
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    default => 'danger'
                                };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                                $date = new DateTime($report['created_at']['S']);
                                $date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
                                echo $date->format('M j, Y');
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>