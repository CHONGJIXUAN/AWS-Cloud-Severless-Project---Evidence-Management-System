<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

if ($_SESSION['role'] !== 'moderator') {
    redirect('index.php');
}

$tableName = 'ScamReports';
$reportId = $_GET['id'] ?? '';

if (empty($_GET['id'])) {
    die("Missing or invalid report ID.");
}

$success = $error = null;
$report = null;

// Fetch the report
try {
    $result = $dynamodb->getItem([
        'TableName' => $tableName,
        'Key' => [
            'reportId' => ['S' => $reportId]
        ]
    ]);

    if (!isset($result['Item']) || $result['Item']['status']['S'] !== 'pending') {
        die("Invalid or already reviewed report.");
    }

    $report = $result['Item'];

} catch (DynamoDbException $e) {
    die("Failed to fetch report: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        try {
            $dynamodb->updateItem([
                'TableName' => $tableName,
                'Key' => ['reportId' => ['S' => $reportId]],
                'UpdateExpression' => 'SET #s = :s',
                'ExpressionAttributeNames' => ['#s' => 'status'],
                'ExpressionAttributeValues' => [':s' => ['S' => 'approved']]
            ]);
            header("Location: moderator_dashboard.php?msg=approved");
            exit;
        } catch (DynamoDbException $e) {
            $error = "Failed to approve report: " . $e->getMessage();
        }
    }

    if (isset($_POST['reject'])) {
        $reason = trim($_POST['rejection_reason']);
        if (empty($reason)) {
            $error = "Rejection reason is required.";
        } else {
            try {
                $dynamodb->updateItem([
                    'TableName' => $tableName,
                    'Key' => ['reportId' => ['S' => $reportId]],
                    'UpdateExpression' => 'SET #s = :s, #r = :r',
                    'ExpressionAttributeNames' => [
                        '#s' => 'status',
                        '#r' => 'rejection_reason'
                    ],
                    'ExpressionAttributeValues' => [
                        ':s' => ['S' => 'rejected'],
                        ':r' => ['S' => $reason]
                    ]
                ]);
                header("Location: moderator_dashboard.php?msg=rejected");
                exit;
            } catch (DynamoDbException $e) {
                $error = "Failed to reject report: " . $e->getMessage();
            }
        }
    }
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

             <h3 class="mb-4"><i class="fas fa-shield-alt me-2"></i>Moderate Scam Report</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="card p-4 mb-4">
                <p><strong>Title:</strong> <?= htmlspecialchars($report['title']['S'] ?? '') ?></p>
                <p><strong>Scam Type:</strong> <?= htmlspecialchars($report['scam_type']['S'] ?? '') ?></p>
                <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($report['description']['S'] ?? '')) ?></p>
                <p><strong>Amount Lost:</strong> RM <?= number_format($report['amount_lost']['N'] ?? 0, 2) ?></p>
                <p><strong>Date Occurred:</strong> <?= htmlspecialchars($report['date_occurred']['S'] ?? '') ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($report['location']['S'] ?? '') ?></p>
                <p><strong>Contact Info:</strong><br><?= nl2br(htmlspecialchars($report['contact_info']['S'] ?? '')) ?></p>
                <?php if (!empty($report['evidence']['S'])): ?>
                    <?php
                        $evidenceUrl = $report['evidence']['S'];
                        $extension = strtolower(pathinfo($evidenceUrl, PATHINFO_EXTENSION));
                        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    ?>
                    
                    <p><strong>Evidence:</strong></p>

                    <?php if (in_array($extension, $imageTypes)): ?>
                        <img src="<?= htmlspecialchars($evidenceUrl) ?>" alt="Evidence Image" class="img-fluid rounded shadow" style="max-width: 400px;">
                    <?php elseif ($extension === 'pdf'): ?>
                        <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" class="btn btn-sm btn-outline-danger">View PDF</a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($evidenceUrl) ?>" target="_blank" class="btn btn-sm btn-outline-info">Download/View Evidence</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php
                    if (!empty($report['created_at']['S'])) {
                        $date = new DateTime($report['created_at']['S']);
                        $date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur')); 
                        $formattedDate = $date->format('d F Y, h:i A');
                        echo '<p><strong>Submitted On:</strong> ' . htmlspecialchars($formattedDate) . '</p>';
                    }
                ?>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason (only if rejecting)</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this report is being rejected..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="moderator_dashboard.php" class="btn btn-secondary">← Back</a>
                    <div>
                        <button type="submit" name="approve" class="btn btn-success me-2">
                            <i class="fas fa-check-circle me-1"></i>Approve
                        </button>
                        <button type="submit" name="reject" class="btn btn-danger">
                            <i class="fas fa-times-circle me-1"></i>Reject
                        </button>
                    </div>
                </div>
            </form>

        </div>    
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
