<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Check if user is logged in and is admin
require_login();
require_admin();

// Table names
$usersTable = 'Users';
$reportsTable = 'ScamReports';

// Get statistics
$stats = [];

// Total users
try {
    $result = $dynamodb->scan([
        'TableName' => $usersTable,
        'FilterExpression' => '#r = :roleVal',
        'ExpressionAttributeNames' => [
            '#r' => 'role',
        ],
        'ExpressionAttributeValues' => [
            ':roleVal' => ['S' => 'user']
        ],
        'Select' => 'COUNT'
    ]);
    $stats['total_users'] = $result['Count'];
} catch (DynamoDbException $e) {
    $stats['total_users'] = 0; // Or log the real error
    error_log("Failed to count users: " . $e->getMessage());
}

// Total reports
try {
    $result = $dynamodb->scan([
        'TableName' => $reportsTable,
        'Select' => 'COUNT'
    ]);
    $stats['total_reports'] = $result['Count'];
} catch (DynamoDbException $e) {
    $stats['total_reports'] = 0;
}

// Pending reports
try {
    $result = $dynamodb->scan([
        'TableName' => $reportsTable,
        'FilterExpression' => '#status = :pendingVal',
        'ExpressionAttributeNames' => ['#status' => 'status'],
        'ExpressionAttributeValues' => [
            ':pendingVal' => ['S' => 'pending']
        ],
        'Select' => 'COUNT'
    ]);
    $stats['pending_reports'] = $result['Count'];
} catch (DynamoDbException $e) {
    $stats['pending_reports'] = 0;
}

// Approved reports
try {
    $result = $dynamodb->scan([
        'TableName' => $reportsTable,
        'FilterExpression' => '#status = :approvedVal',
        'ExpressionAttributeNames' => ['#status' => 'status'],
        'ExpressionAttributeValues' => [
            ':approvedVal' => ['S' => 'approved']
        ],
        'Select' => 'COUNT'
    ]);
    $stats['approved_reports'] = $result['Count'];
} catch (DynamoDbException $e) {
    $stats['approved_reports'] = 0;
}

// Recent users
$recent_users = [];

try {
    $result = $dynamodb->scan([
        'TableName' => $usersTable,
        'FilterExpression' => '#r = :roleVal',
        'ExpressionAttributeNames' => [
            '#r' => 'role',
        ],
        'ExpressionAttributeValues' => [
            ':roleVal' => ['S' => 'user']
        ],
    ]);
    // Sort manually by created_at descending
    usort($result['Items'], function($a, $b) {
        return strtotime($b['created_at']['S']) - strtotime($a['created_at']['S']);
    });

    $recent_users = array_slice($result['Items'], 0, 5);
} catch (DynamoDbException $e) {
    $recent_users = [];
}

// Recent reports
$recent_reports = [];

try {
    $result = $dynamodb->scan([
        'TableName' => $reportsTable
    ]);
    // Sort by created_at
    usort($result['Items'], function($a, $b) {
        return strtotime($b['created_at']['S']) - strtotime($a['created_at']['S']);
    });

    $topReports = array_slice($result['Items'], 0, 5);

    foreach ($topReports as $report) {
        $userId = $report['userId']['S'];

        // Fetch user
        $userResult = $dynamodb->getItem([
            'TableName' => $usersTable,
            'Key' => [
                'userId' => ['S' => $userId]
            ]
        ]);

        $username = $userResult['Item']['username']['S'] ?? 'Unknown';

        $recent_reports[] = [
            'title' => $report['title']['S'] ?? '',
            'scam_type' => $report['scam_type']['S'] ?? '',
            'status' => $report['status']['S'] ?? '',
            'created_at' => $report['created_at']['S'] ?? '',
            'username' => $username
        ];
    }
} catch (DynamoDbException $e) {
    $recent_reports = [];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Global Scam Report Center</title>
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ps-md-3 admin-main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
                    <span class="badge bg-success">Administrator</span>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-users"></i>
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <p class="text-muted">Total Users</p>
                            </div>
                        </div>
                    </div>
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
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-lg-6">
                        <div class="table-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-users me-2"></i>Recent Users</h5>
                                <a href="users.php" class="btn btn-outline-danger btn-sm">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_users)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No users found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['username']['S'] ?? ''); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['email']['S'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['status']['S'] == 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($user['status']['S'] ?? ''); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php
                                                                if (isset($user['created_at']['S'])) {
                                                                    $date = new DateTime($user['created_at']['S']);
                                                                    $date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
                                                                    echo $date->format('M j, Y');
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                            ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reports -->
                    <div class="col-lg-6">
                        <div class="table-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-file-alt me-2"></i>Recent Reports</h5>
                                <a href="reports.php" class="btn btn-outline-danger btn-sm">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Reporter</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_reports)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No reports found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_reports as $report): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars(substr($report['title'], 0, 30)) . (strlen($report['title']) > 30 ? '...' : ''); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($report['username']); ?></td>
                                                    <td>
                                                        <span class="badge status-<?php echo $report['status']; ?>">
                                                            <?php echo ucfirst($report['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
