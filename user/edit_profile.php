<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Check if the user is logged in
if (!isset($_SESSION['userId'])) {
    die("Access denied. Please log in.");
}

$userId = $_SESSION['userId'];
// Redirect admin to admin dashboard
if (is_admin()) {
    redirect('../admin/dashboard.php');
}

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
        'full_name' => $result['Item']['full_name']['S'] ?? '',
        'username' => $result['Item']['username']['S'] ?? '',
        'email' => $result['Item']['email']['S'] ?? ''
    ];
} catch (DynamoDbException $e) {
    die("Error fetching user: " . $e->getMessage());
}

$success = $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if (empty($full_name) || empty($username) || empty($email)) {
        $error = "Full name, username, and email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
       try {
            $dynamodb->updateItem([
                'TableName' => 'Users',
                'Key' => [
                    'userId' => ['S' => $userId]
                ],
                'UpdateExpression' => 'SET #fn = :fullName, #un = :username, #em = :email',
                'ExpressionAttributeNames' => [
                    '#fn' => 'full_name',
                    '#un' => 'username',
                    '#em' => 'email'
                ],
                'ExpressionAttributeValues' => [
                    ':fullName' => ['S' => $full_name],
                    ':username' => ['S' => $username],
                    ':email' => ['S' => $email]
                ]
            ]);

            header("Location: edit_profile.php?id=$userId&success=1");
        } catch (DynamoDbException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Profile updated successfully.</div>
        <?php endif; ?>
        <div class="row">
            <h3>Edit Your Profile</h3>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <!-- <hr>
                <h5>Change Password (optional)</h5>

                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div> -->

                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="profile.php" class="btn btn-secondary">Back</a>
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