<?php
session_start();
//print_r($_SESSION);
include 'config/database.php';
include 'includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    }elseif($_SESSION['role'] == 'moderator') {
        redirect('moderator/moderator_dashboard.php');{
    }        
    }else {
        redirect('user/dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }
    
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username)) {
        $errors[] = 'Username or email is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    if (empty($errors)) {
        try {
            $result = $dynamodb->scan([
                'TableName' => 'Users',
                'FilterExpression' => 'username = :u OR email = :e AND #status = :s',
                'ExpressionAttributeNames' => [
                    '#status' => 'status'
                ],
                'ExpressionAttributeValues' => [
                    ':u' => ['S' => $username],
                    ':e' => ['S' => $username],
                    ':s' => ['S' => 'active']
                ]
            ]);

            if ($result['Count'] > 0) {
                $user = $result['Items'][0]; // Get the first match
                $stored_hash = $user['password']['S'];

                if (verify_password($password, $stored_hash)) {
                    $_SESSION['userId'] = $user['userId']['S'];
                    $_SESSION['username'] = $user['username']['S'];
                    $_SESSION['email'] = $user['email']['S'];
                    $_SESSION['full_name'] = $user['full_name']['S'];
                    $_SESSION['role'] = $user['role']['S'];

                    if ($_SESSION['role'] === 'admin') {
                        redirect('admin/dashboard.php');
                    } elseif ($_SESSION['role'] === 'moderator') {
                        redirect('moderator/moderator_dashboard.php');
                    } else {
                        redirect('user/dashboard.php');
                    }
                } else {
                    $errors[] = 'Invalid username/email or password.';
                }
            } else {
                $errors[] = 'Invalid username/email or password.';
            }

        } catch (DynamoDbException $e) {
            $errors[] = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Global Scam Report Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt me-2"></i>
                Global Scam Report Center
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="register.php">Register</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-3x text-danger mb-3"></i>
                        <h2>Welcome Back</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username or Email
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Don't have an account? 
                            <a href="register.php" class="text-danger text-decoration-none">Create one here</a>
                        </p>
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
