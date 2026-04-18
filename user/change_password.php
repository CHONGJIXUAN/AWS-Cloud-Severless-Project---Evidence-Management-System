<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

if (!isset($_SESSION['userId'])) {
    die("Access denied.");
}
$userId = $_SESSION['userId'];

try {
    $result = $dynamodb->getItem([
        'TableName' => 'Users',
        'Key' => [
            'userId' => ['S' => $userId]
        ]
    ]);

    if (!isset($result['Item'])) {
        die("User not found.");
    }

    $user = [
        'userId' => $result['Item']['userId']['S'],
        'password' => $result['Item']['password']['S']
    ];
} catch (DynamoDbException $e) {
    die("Error fetching user: " . $e->getMessage());
}

$success = $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $dynamodb->updateItem([
                'TableName' => 'Users',
                'Key' => [
                    'userId' => ['S' => $userId]
                ],
                'UpdateExpression' => 'SET #pw = :newPassword',
                'ExpressionAttributeNames' => ['#pw' => 'password'],
                'ExpressionAttributeValues' => [
                    ':newPassword' => ['S' => $hashed]
                ]
            ]);

            $success = "Password changed successfully.";
        } catch (DynamoDbException $e) {
            $error = "Failed to update password: " . $e->getMessage();
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
            <h3 class="mb-4"><i class="fas fa-key me-2"></i>Change Password</h3>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-light p-4 rounded shadow-sm border">

                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Profile
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-1"></i>Change Password
                    </button>
                </div>

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