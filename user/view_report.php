<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in
require_login();

// Redirect admin to admin dashboard
if (is_admin()) {
    redirect('../admin/dashboard.php');
}

// $reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$reportId = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($reportId)) {
    die("Invalid report ID.");
}

// $stmt = $pdo->prepare("SELECT sr.*, st.name AS scam_type_name 
//                        FROM scam_reports sr 
//                        LEFT JOIN scam_types st ON sr.scam_type = st.name
//                        WHERE sr.id = ?");
// $stmt->execute([$reportId]);
// $report = $stmt->fetch();

// if (!$report) {
//     die("Scam report not found.");
// }

try {
    $response = $dynamodb->getItem([
        'TableName' => 'ScamReports',
        'Key' => [
            'reportId' => ['S' => $reportId]
        ]
    ]);

    if (!isset($response['Item'])) {
        die("Scam report not found.");
    }

    $item = $marshaler->unmarshalItem($response['Item']);

} catch (Exception $e) {
    die("Error loading report: " . $e->getMessage());
}

$date = new DateTime($item['created_at']);
$date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur')); 
$formattedDate = $date->format('d F Y, h:i A');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Scam Report</title>
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
            <h3 class="mb-4">Scam Report Details</h3>

            <div class="card p-4">
                <p><strong>Title:</strong> <?= htmlspecialchars($item['title']) ?></p>
                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                <p><strong>Scam Type:</strong> <?= htmlspecialchars($item['scam_type']) ?></p>
                <p><strong>Amount Lost:</strong> RM <?= number_format((float)$item['amount_lost'], 2) ?></p>
                <p><strong>Date Occurred:</strong> <?= htmlspecialchars($item['date_occurred']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($item['location']) ?></p>
                <p><strong>Contact Info:</strong><br><?= nl2br(htmlspecialchars($item['contact_info'])) ?></p>
                <p><strong>Status:</strong> <?= ucfirst($item['status']) ?></p>

                <?php if ($item['status'] === 'rejected'): ?>
                    <p><strong>Rejection Reason:</strong><br><?= nl2br(htmlspecialchars($item['rejection_reason'] ?? '')) ?></p>
                <?php endif; ?>

                <?php if (!empty($item['evidence'])): ?>
                <?php
                $ext = strtolower(pathinfo($item['evidence'], PATHINFO_EXTENSION));
                $image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                ?>
                
                <?php if (in_array($ext, $image_types)): ?>
                    <p><strong>Evidence Image:</strong></p>
                    <img src="<?= htmlspecialchars($item['evidence']) ?>" alt="Evidence Image" class="img-fluid rounded border" style="max-width: 100%; height: auto;">
                <?php else: ?>
                    <p><strong>Evidence File:</strong>
                        <a href="<?= htmlspecialchars($item['evidence']) ?>" target="_blank">Download/View Evidence</a>
                    </p>
                <?php endif; ?>
                <?php else: ?>
                    <p><strong>Evidence:</strong> No file uploaded.</p>
                <?php endif; ?>


                <p class="text-muted">Submitted on: <?= $formattedDate ?></p>
            </div>

            <a href="dashboard.php" class="btn btn-secondary mt-3">← Back to Reports</a>
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

</body>
</html>