<?php
session_start();
//print_r($_SESSION);
include '../config/database.php';
include '../includes/functions.php';

use Aws\DynamoDb\Exception\DynamoDbException;

// Check if user is logged in
require_login();

// Redirect admin to admin dashboard
if (is_admin()) {
    redirect('../admin/dashboard.php');
}

// Fetch user data from DynamoDB
try {
    $user = $dynamodb->getItem([
        'TableName' => 'Users',
        'Key' => [
            'userId' => ['S' => $_SESSION['userId']]
        ]
    ]);

    $createdAt = isset($user['Item']['created_at']['S']) 
        ? date('M j, Y', strtotime($user['Item']['created_at']['S']))
        : 'Unknown';

} catch (DynamoDbException $e) {
    $createdAt = 'Unavailable';
}

$errors = [];
$user_reports = [];

try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => 'userId = :uid',
        'ExpressionAttributeValues' => [
            ':uid' => ['S' => $_SESSION['userId']]
        ]
    ]);

    foreach ($result['Items'] as $item) {
        $user_reports[] = [
            'reportId' => $item['reportId']['S'],
            'amount_lost' => isset($item['amount_lost']['N']) ? (float)$item['amount_lost']['N'] : 0,
            'title' => $item['title']['S'],
            'description' => $item['description']['S'],
            'scam_type' => $item['scam_type']['S'],
            'status' => $item['status']['S'],
            'created_at' => $item['created_at']['S'],
        ];
    }

} catch (DynamoDbException $e) {
    $errors[] = 'Failed to load reports: ' . $e->getMessage();
}

// Compute statistics
$total_reports = count($user_reports);
$pending_reports = count(array_filter($user_reports, fn($r) => $r['status'] === 'pending'));
$approved_reports = count(array_filter($user_reports, fn($r) => $r['status'] === 'approved'));
$rejected_reports = count(array_filter($user_reports, fn($r) => $r['status'] === 'rejected'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Global Scam Report Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
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
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <?php echo htmlspecialchars($_SESSION['role']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">View Home Page</a></li>
                        <li><a class="dropdown-item" href="submit_report.php">Submit Report</a></li>
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</h1>
                        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                    </div>
                    <a href="submit_report.php" class="btn btn-danger btn-lg">
                        <i class="fas fa-plus me-2"></i>Submit New Report
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-file-alt"></i>
                                <h3><?php echo $total_reports; ?></h3>
                                <p class="text-muted">Total Reports</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-clock"></i>
                                <h3><?php echo $pending_reports; ?></h3>
                                <p class="text-muted">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-check-circle"></i>
                                <h3><?php echo $approved_reports; ?></h3>
                                <p class="text-muted">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="dashboard-card">
                            <div class="dashboard-stat">
                                <i class="fas fa-times-circle"></i>
                                <h3><?php echo $rejected_reports; ?></h3>
                                <p class="text-muted">Rejected</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports List -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-file-alt me-2"></i>Your Scam Reports</h5>
                        <a href="submit_report.php" class="btn btn-outline-danger">
                            <i class="fas fa-plus me-1"></i>New Report
                        </a>
                    </div>

                    <?php if (empty($user_reports)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <h4>No Reports Yet</h4>
                            <p class="text-muted mb-4">You haven't submitted any scam reports yet.</p>
                            <a href="submit_report.php" class="btn btn-danger">
                                <i class="fas fa-plus me-2"></i>Submit Your First Report
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Amount Lost</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_reports as $report): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                                <?php if ($report['status'] == 'rejected' && $report['rejection_reason']): ?>
                                                    <br><small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <?php echo htmlspecialchars($report['rejection_reason']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($report['scam_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge status-<?php echo $report['status']; ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $report['amount_lost'] > 0 ? format_currency($report['amount_lost']) : 'N/A'; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $createdAt; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="view_report.php?id=<?php echo $report['reportId']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($report['status'] == 'pending'): ?>
                                                    <a href="edit_report.php?id=<?php echo $report['reportId']; ?>" 
                                                       class="btn btn-outline-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- User Profile Section -->
                <div class="table-container mt-4">
                    <h5><i class="fas fa-user me-2"></i>Profile Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Role:</strong> <span class="badge bg-primary">Registered User</span></p>
                            <p><strong>Member since:</strong> 
                                <?php 
                                $response = $dynamodb->getItem([
                                    'TableName' => 'Users',
                                    'Key' => [
                                        'userId' => ['S' => $_SESSION['userId']]
                                    ]
                                ]);

                                if (isset($response['Item']['created_at']['S'])) {
                                    $createdAt = $response['Item']['created_at']['S'];
                                    echo date('M j, Y', strtotime($createdAt));
                                } else {
                                    echo "N/A";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
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
