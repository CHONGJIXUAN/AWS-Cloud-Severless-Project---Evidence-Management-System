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

$reportId = $_GET['id'] ?? '';
$userId = $_SESSION['userId'] ?? null;

if (!$userId || empty($reportId)) {
    die("Access denied.");
}

$scam_types = get_scam_types();
$selected_type = $_POST['scam_type'] ?? ($report['scam_type'] ?? '');

// Fetch the report and ensure it belongs to the user
try {
    $result = $dynamodb->getItem([
        'TableName' => 'ScamReports',
        'Key' => [
            'reportId' => ['S' => $reportId]
        ]
    ]);

    if (!isset($result['Item'])) {
        die("Report not found.");
    }

    $report = $marshaler->unmarshalItem($result['Item']);

    // Ownership and status checks
    if ($report['userId'] !== $userId) {
        die("Unauthorized access.");
    }

    if ($report['status'] !== 'pending') {
        die("You can only edit reports with status 'pending'.");
    }

} catch (Exception $e) {
    die("Failed to fetch report: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $scamType = trim($_POST['scam_type']);
    $amountLost = floatval($_POST['amount_lost']);
    $dateOccurred = $_POST['date_occurred'] ?? null;
    $location = trim($_POST['location']);
    $contactInfo = trim($_POST['contact_info']);

    // Simple validation (customize as needed)
    if (empty($title) || empty($description) || empty($scamType)) {
        $error = "Title, description, and scam type are required.";
    } else {
        // Update report in DB
        $updatedAt = date('Y-m-d H:i:s');

        $updatedReport = [
            'reportId' => $reportId,
            'userId' => $userId,
            'title' => $title,
            'description' => $description,
            'scam_type' => $scamType,
            'amount_lost' => $amountLost,
            'date_occurred' => $dateOccurred,
            'location' => $location,
            'contact_info' => $contactInfo,
            'status' => $report['status'], 
            'created_at' => $report['created_at'],
            'updated_at' => $updatedAt,
            'evidence' => $report['evidence'] ?? '',
            
        ];

        try {
            $dynamodb->putItem([
                'TableName' => 'ScamReports',
                'Item' => $marshaler->marshalItem($updatedReport)
            ]);

            header("Location: view_report.php?id=$reportId");
            exit;
        } catch (Exception $e) {
            $error = "Failed to update report: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h3>Edit Scam Report</h3>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($report['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" required><?= htmlspecialchars($report['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Scam Type</label>
                    <select class="form-select" id="scam_type" name="scam_type" required>
                        <?php foreach ($scam_types as $type): ?>
                            <option value="<?= htmlspecialchars($type); ?>"
                                <?= trim($selected_type) === trim($type) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label class="form-label">Amount Lost (RM)</label>
                    <input type="number" step="0.01" name="amount_lost" class="form-control" value="<?= htmlspecialchars($report['amount_lost']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Date Occurred</label>
                    <input type="date" name="date_occurred" class="form-control" value="<?= htmlspecialchars($report['date_occurred']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($report['location']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Info</label>
                    <textarea name="contact_info" class="form-control"><?= htmlspecialchars($report['contact_info']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update Report</button>
                <a href="view_report.php?id=<?= $reportId ?>" class="btn btn-secondary">Cancel</a>
            </form>
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