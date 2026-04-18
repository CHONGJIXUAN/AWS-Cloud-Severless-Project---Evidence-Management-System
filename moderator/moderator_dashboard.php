<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

if ($_SESSION['role'] !== 'moderator') {
    redirect('index.php');
}

$stats = [];
$pendingReports = [];


// Scan pending reports
try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => '#s = :pending',
        'ExpressionAttributeNames' => ['#s' => 'status'],
        'ExpressionAttributeValues' => [
            ':pending' => ['S' => 'pending']
        ]
    ]);

    $pendingReports = $result['Items'];
    usort($pendingReports, function ($a, $b) {
        return strtotime($b['created_at']['S']) <=> strtotime($a['created_at']['S']);
    });

    $stats['pending_reports'] = count($pendingReports);
} catch (DynamoDbException $e) {
    echo "Error fetching pending reports: " . $e->getMessage();
}

// Count all users with role = user
try {
    $result = $dynamodb->scan([
        'TableName' => 'Users',
        'FilterExpression' => '#r = :user',
        'ExpressionAttributeNames' => ['#r' => 'role'],
        'ExpressionAttributeValues' => [
            ':user' => ['S' => 'user']
        ]
    ]);
    $stats['total_users'] = count($result['Items']);
} catch (DynamoDbException $e) {
    echo "Error counting users: " . $e->getMessage();
}

// Count total reports
try {
    $result = $dynamodb->scan(['TableName' => 'ScamReports']);
    $stats['total_reports'] = count($result['Items']);
} catch (DynamoDbException $e) {
    echo "Error counting total reports: " . $e->getMessage();
}

// Count approved reports
try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => '#s = :approved',
        'ExpressionAttributeNames' => ['#s' => 'status'],
        'ExpressionAttributeValues' => [
            ':approved' => ['S' => 'approved']
        ]
    ]);
    $stats['approved_reports'] = count($result['Items']);
} catch (DynamoDbException $e) {
    echo "Error counting approved reports: " . $e->getMessage();
}

// Count rejected reports
try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => '#s = :rejected',
        'ExpressionAttributeNames' => ['#s' => 'status'],
        'ExpressionAttributeValues' => [
            ':rejected' => ['S' => 'rejected']
        ]
    ]);
    $stats['rejected_reports'] = count($result['Items']);
} catch (DynamoDbException $e) {
    echo "Error counting rejected reports: " . $e->getMessage();
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

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 pe-md-4">
                <div class="list-group admin-sidebar">
                    <a href="moderator_dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="moderator_scam_types.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'moderator_scam_types' ? 'active' : '' ?>">
                        <i class="fas fa-edit me-2"></i> Scam Type
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ps-md-3 admin-main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Moderator Dashboard</h1>
                    <span class="badge bg-success">Moderator</span>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-file-alt"></i>
                                <h3><?php echo $stats['total_reports']; ?></h3>
                                <p class="text-muted">Total Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-clock"></i>
                                <h3><?php echo $stats['pending_reports']; ?></h3>
                                <p class="text-muted">Pending Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-check-circle"></i>
                                <h3><?php echo $stats['approved_reports']; ?></h3>
                                <p class="text-muted">Approved Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card  text-white">
                            <div class="dashboard-stat">
                                <i class="fas fa-trash-alt"></i>
                                <h3><?php echo $stats['rejected_reports']; ?></h3>
                                <p class="text-muted">Rejected Reports</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Reports -->
                    <div class="col-lg-12">
                        <div class="table-container">
                            <h3>Pending Scam Reports</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Scam Type</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pendingReports as $report): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($report['title']['S'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($report['scam_type']['S'] ?? '') ?></td>
                                        <td>
                                            <?php $status = $report['status']['S'] ?? 'unknown'; ?>
                                            <span class="badge bg-<?= 
                                                $status === 'approved' ? 'success' : 
                                                ($status === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                         <td>
                                            <?php
                                            $createdAt = $report['created_at']['S'] ?? '';
                                            $formattedDate = $createdAt ? date('M d, Y', strtotime($createdAt)) : 'N/A';
                                            ?>
                                            <?= $formattedDate ?>
                                        </td>
                                        <td>
                                        <a href="moderate_report.php?id=<?= urlencode($report['reportId']['S'] ?? '') ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Review
                                        </a>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
