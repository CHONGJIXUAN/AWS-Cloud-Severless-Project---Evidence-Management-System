<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Exception\DynamoDbException;

// Initialize AWS DynamoDB client
$dynamoDb = new DynamoDbClient([
    'region' => 'us-east-1',
    'version' => 'latest'
]);

$marshaler = new Marshaler();

// Get count of approved scam reports
$reportCount = 0;
try {
    $result = $dynamoDb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => 'status = :approved',
        'ExpressionAttributeValues' => [
            ':approved' => ['S' => 'approved']
        ],
        'Select' => 'COUNT'
    ]);
    $reportCount = $result['Count'];
} catch (Exception $e) {
    $reportCount = 0;
}

// Get count of users
$userCount = 0;
try {
    $userResult = $dynamoDb->scan([
        'TableName' => 'Users',
        'FilterExpression' => 'role = :user',
        'ExpressionAttributeValues' => [
            ':user' => ['S' => 'user']
        ],
        'Select' => 'COUNT'
    ]);
    $userCount = $userResult['Count'];
} catch (Exception $e) {
    $userCount = 0;
}

// Get recent scam reports
$reports = [];
try {
    $recentResult = $dynamoDb->scan([
        'TableName' => 'ScamReports',
        'FilterExpression' => 'status = :approved',
        'ExpressionAttributeValues' => [
            ':approved' => ['S' => 'approved']
        ],
        'Limit' => 10
    ]);
    foreach ($recentResult['Items'] as $item) {
        $reports[] = $marshaler->unmarshalItem($item);
    }
} catch (Exception $e) {
    $reports = [];
}

// // Get approved reports for public viewing
// $stmt = $pdo->prepare("SELECT sr.*, u.username FROM scam_reports sr 
//                        JOIN users u ON sr.user_id = u.id 
//                        WHERE sr.status = 'approved' 
//                        ORDER BY sr.created_at DESC LIMIT 10");
// $stmt->execute();
// $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Scam Report Center</title>
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Browse Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id']['S'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']['S']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (isset($_SESSION['user_id']['S'])): ?>
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

    <!-- Hero Section -->
    <section class="hero-section bg-gradient-danger text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Protect Yourself from Scams</h1>
                    <p class="lead mb-4">
                        Join our community in fighting fraud. Report scams, stay informed, and help others avoid becoming victims.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="reports.php" class="btn btn-light btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Reports
                        </a>
                        <?php if(!isset($_SESSION['user_id']['S'])): ?>
                            <a href="register.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Join Us
                            </a>
                        <?php else: ?>
                            <a href="user/submit_report.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-plus me-2"></i>Report Scam
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-stats">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3 class="fw-bold">
                                        <?php
                                            try {
                                                $result = $dynamodb->scan([
                                                    'TableName' => 'ScamReports',
                                                    'FilterExpression' => '#s = :approved',
                                                    'ExpressionAttributeNames' => [
                                                        '#s' => 'status'
                                                    ],
                                                    'ExpressionAttributeValues' => [
                                                        ':approved' => ['S' => 'approved']
                                                    ],
                                                    'Select' => 'COUNT'
                                                ]);

                                                echo $result['Count'];
                                            } catch (DynamoDbException $e) {
                                                echo "Error: " . $e->getMessage();
                                            }
                                        ?>
                                    </h3>
                                    <p>Verified Reports</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3 class="fw-bold">
                                        <?php
                                            try {
                                                $result = $dynamodb->scan([
                                                    'TableName' => 'ScamReports',
                                                    'FilterExpression' => '#s = :approved',
                                                    'ExpressionAttributeNames' => [
                                                        '#s' => 'status'
                                                    ],
                                                    'ExpressionAttributeValues' => [
                                                        ':approved' => ['S' => 'approved']
                                                    ],
                                                    'Select' => 'COUNT'
                                                ]);

                                                echo $result['Count'];
                                            } catch (DynamoDbException $e) {
                                                echo "Error: " . $e->getMessage();
                                            }
                                        ?>
                                    </h3>
                                    <p>Community Members</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Reports Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center mb-5">Recent Scam Reports</h2>
                </div>
            </div>
            <div class="row">
                <?php if(empty($reports)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No reports available yet. Be the first to report a scam!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($reports as $report): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($report['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($report['description'], 0, 150)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                                        </small>
                                        <span class="badge bg-danger"><?php echo htmlspecialchars($report['scam_type']); ?></span>
                                    </div>
                                    <div class="mt-3">
                                        <a href="report_details.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-danger btn-sm">
                                            Read More <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="row">
                <div class="col-12 text-center mt-4">
                    <a href="reports.php" class="btn btn-danger btn-lg">
                        View All Reports <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2>How We Protect You</h2>
                    <p class="text-muted">Our comprehensive approach to scam prevention</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt fa-3x text-danger mb-3"></i>
                    </div>
                    <h4>Verified Reports</h4>
                    <p class="text-muted">All reports are carefully reviewed and verified by our team before publication.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-users fa-3x text-danger mb-3"></i>
                    </div>
                    <h4>Community Driven</h4>
                    <p class="text-muted">Built by the community, for the community. Share your experiences to help others.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-search fa-3x text-danger mb-3"></i>
                    </div>
                    <h4>Easy Search</h4>
                    <p class="text-muted">Quickly find reports by scam type, date, or keywords to stay informed.</p>
                </div>
            </div>
        </div>
    </section>

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
