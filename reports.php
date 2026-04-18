<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Pagination
$reports = []; 
$search = '';  
$total_reports = 0;
$total_pages = 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$report_id = isset($_GET['reportId']) ? $_GET['reportId'] : '';

// Search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$scam_type = isset($_GET['scam_type']) ? sanitize_input($_GET['scam_type']) : '';
$filterExpression = '#status = :approved';
$expressionAttributeNames = ['#status' => 'status'];
$expressionAttributeValues = [
    ':approved' => ['S' => 'approved']
];

// Optional filters
if (!empty($search)) {
    $filterExpression .= ' AND (contains(#title, :titleSearch) OR contains(#desc, :descSearch))';
    $expressionAttributeNames['#title'] = 'title';
    $expressionAttributeNames['#desc'] = 'description';
    $expressionAttributeValues[':titleSearch'] = ['S' => $search];
    $expressionAttributeValues[':descSearch'] = ['S' => $search];
}


if (!empty($scam_type)) {
    $filterExpression .= ' AND #type = :type';
    $expressionAttributeNames['#type'] = 'scam_type';
    $expressionAttributeValues[':type'] = ['S' => $scam_type];
}

try {
    $result = $dynamodb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => $filterExpression,
        'ExpressionAttributeNames' => $expressionAttributeNames,
        'ExpressionAttributeValues' => $expressionAttributeValues,
    ]);

    $allReports = $result['Items'] ?? [];
    $total_reports = count($allReports);
    $reports = array_slice($allReports, ($page - 1) * $limit, $limit);
    $total_pages = ceil($total_reports / $limit);

} catch (DynamoDbException $e) {
    $error = "Failed to retrieve reports: " . $e->getMessage();
}


foreach ($reports as &$report) {
    $userId = $report['userId']['S'];
    $userResult = $dynamodb->getItem([
        'TableName' => 'Users',
        'Key' => ['userId' => ['S' => $userId]],
        'ProjectionExpression' => 'username'
    ]);
    $report['username'] = $userResult['Item']['username']['S'] ?? 'Unknown';
}

$scam_types = get_scam_types();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Reports - Global Scam Report Center</title>
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
                        <a class="nav-link active" href="reports.php">Browse Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if($_SESSION['role'] == 'admin'): ?>
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

    <!-- Page Header -->
    <section class="bg-danger text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1><i class="fas fa-search me-2"></i>Browse Scam Reports</h1>
                    <p class="mb-0">Search through verified scam reports to stay informed and protected</p>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="search" class="form-label">Search Reports</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by title or description...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="scam_type" class="form-label">Filter by Type</label>
                                    <select class="form-select" id="scam_type" name="scam_type">
                                        <option value="">Select Type</option>
                                        <?php foreach (get_scam_types() as $type): ?>
                                            <option value="<?= htmlspecialchars($type); ?>" <?= (isset($_GET['scam_type']) && $_GET['scam_type'] == $type) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-search me-1"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="row mb-3">
            <div class="col-12">
                <p class="text-muted">
                    Showing <?php echo count($reports); ?> of <?php echo $total_reports; ?> reports
                    <?php if (!empty($search) || !empty($scam_type)): ?>
                        <span class="ms-2">
                            <a href="reports.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Reports List -->
        <div class="row">
            <?php if (empty($reports)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4>No Reports Found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all reports.</p>
                        <?php if (!empty($search) || !empty($scam_type)): ?>
                            <a href="reports.php" class="btn btn-danger">View All Reports</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($reports)): ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($report['title']['S']); ?></h5>
                                        <span class="badge bg-danger"><?php echo htmlspecialchars($report['scam_type']['S']); ?></span>
                                    </div>

                                    <p class="card-text">
                                        <?php echo htmlspecialchars(substr($report['description']['S'], 0, 200)); ?>
                                    </p>

                                    <div class="row text-muted small mb-3">
                                        <div class="col-6">
                                            <i class="fas fa-user me-1"></i>
                                            By: <?php echo htmlspecialchars($report['username']); ?>
                                        </div>
                                        <div class="col-6 text-end">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M j, Y', strtotime($report['created_at']['S'])); ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($report['amount_lost']) && $report['amount_lost'] > 0): ?>
                                        <div class="alert alert-warning py-2">
                                            <small>
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                Amount Lost: <?php echo number_format((float)$report['amount_lost']['N'], 2); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($report['location'])): ?>
                                        <p class="small text-muted mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            Location: <?php echo htmlspecialchars($report['location']['S']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="report_details.php?reportId=<?php echo urlencode($report['reportId']['S']); ?>"
                                        class="btn btn-outline-danger btn-sm">
                                            Read Full Report <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                        <small class="text-muted">
                                            <?php echo time_elapsed_string($report['created_at']['S']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4>No Reports Found</h4>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row">
                <div class="col-12">
                    <nav aria-label="Reports pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&scam_type=<?php echo urlencode($scam_type); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&scam_type=<?php echo urlencode($scam_type); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&scam_type=<?php echo urlencode($scam_type); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
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
