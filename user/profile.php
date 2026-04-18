<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Check if user is logged in
require_login();


// Redirect admin to admin dashboard
if (is_admin()) {
    redirect('../admin/dashboard.php');
}

// Fetch user info
try {
    $result = $dynamodb->getItem([
        'TableName' => 'Users',
        'Key' => [
            'userId' => ['S' => $_SESSION['userId']]
        ]
    ]);

    if (!isset($result['Item'])) {
        redirect('../logout.php');
    }

    $user = [
        'userId' => $result['Item']['userId']['S'],
        'username' => $result['Item']['username']['S'],
        'email' => $result['Item']['email']['S'],
        'full_name' => $result['Item']['full_name']['S'],
        'created_at' => $result['Item']['created_at']['S'],
        'status' => $result['Item']['status']['S']
    ];
} catch (DynamoDbException $e) {
    echo "Failed to load user: " . $e->getMessage();
    exit;
}

// Get user's report statistics
try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => 'userId = :uid',
        'ExpressionAttributeValues' => [
            ':uid' => ['S' => $_SESSION['userId']]
        ]
    ]);

    $total = 0;
    $pending = 0;
    $approved = 0;
    $rejected = 0;

    foreach ($result['Items'] as $item) {
        $total++;
        $status = strtolower($item['status']['S'] ?? '');
        if ($status === 'pending') $pending++;
        elseif ($status === 'approved') $approved++;
        elseif ($status === 'rejected') $rejected++;
    }

    $stats = [
        'total_reports' => $total,
        'pending_reports' => $pending,
        'approved_reports' => $approved,
        'rejected_reports' => $rejected
    ];
} catch (DynamoDbException $e) {
    echo "Failed to load stats: " . $e->getMessage();
    $stats = ['total_reports' => 0, 'pending_reports' => 0, 'approved_reports' => 0, 'rejected_reports' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Global Scam Report Center</title>
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
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">View Home Page</a></li>
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
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
                        <h1><i class="fas fa-user-circle me-2"></i>My Profile</h1>
                        <p class="text-muted">View and manage your account information</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-danger">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-8">
                        <div class="form-container">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4><i class="fas fa-user me-2"></i>Personal Information</h4>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?> fs-6">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">
                                        <i class="fas fa-user me-1"></i>Full Name
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">
                                        <i class="fas fa-at me-1"></i>Username
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">
                                        <i class="fas fa-envelope me-1"></i>Email Address
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">
                                        <i class="fas fa-calendar me-1"></i>Member Since
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>User ID
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        #<?php echo str_pad($user['userId'], 6, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Actions -->
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="text-muted mb-3">Account Actions</h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="edit_profile.php?id=<?= $user['userId']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i>Edit Profile
                                    </a>
                                    <a href="change_password.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-key me-1"></i>Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="col-lg-4">
                        <div class="form-container">
                            <h4 class="mb-4">
                                <i class="fas fa-chart-bar me-2"></i>Account Statistics
                            </h4>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-file-alt me-2"></i>Total Reports</span>
                                    <span class="badge bg-primary fs-6"><?php echo $stats['total_reports']; ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-clock me-2"></i>Pending Reports</span>
                                    <span class="badge bg-warning fs-6"><?php echo $stats['pending_reports']; ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-warning" 
                                         style="width: <?php echo $stats['total_reports'] > 0 ? ($stats['pending_reports'] / $stats['total_reports']) * 100 : 0; ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-check-circle me-2"></i>Approved Reports</span>
                                    <span class="badge bg-success fs-6"><?php echo $stats['approved_reports']; ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: <?php echo $stats['total_reports'] > 0 ? ($stats['approved_reports'] / $stats['total_reports']) * 100 : 0; ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fas fa-times-circle me-2"></i>Rejected Reports</span>
                                    <span class="badge bg-danger fs-6"><?php echo $stats['rejected_reports']; ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-danger" 
                                         style="width: <?php echo $stats['total_reports'] > 0 ? ($stats['rejected_reports'] / $stats['total_reports']) * 100 : 0; ?>%"></div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="text-center">
                                <h6 class="text-muted mb-3">Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <a href="submit_report.php" class="btn btn-danger">
                                        <i class="fas fa-plus me-2"></i>Submit New Report
                                    </a>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                                    </a>
                                </div>
                            </div>
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
