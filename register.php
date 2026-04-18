<?php
session_start();
require 'vendor/autoload.php';
include 'config/database.php';
include 'includes/functions.php';

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

$errors = [];
$success = '';
$marshaler = new Marshaler();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }
    
    // Get and sanitize input
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            $response = $dynamodb->scan([
                'TableName' => 'Users',
                'FilterExpression' => 'username = :u OR email = :e',
                'ExpressionAttributeValues' => [
                    ':u' => ['S' => $username],
                    ':e' => ['S' => $email],
                ]
            ]);
            if ($response['Count'] > 0) {
                $errors[] = 'Username or email already exists.';
            }
        } catch (DynamoDbException $e) {
            $errors[] = 'Failed to check existing user: ' . $e->getMessage();
        }
    }
    
    // If no errors, create the user
        if (empty($errors)) {
        $userId = uniqid('user_');
        $hashed_password = hash_password($password);

        $item = [
            'userId' => ['S' => $userId],
            'username' => ['S' => $username],
            'email' => ['S' => $email],
            'full_name' => ['S' => $full_name],
            'password' => ['S' => $hashed_password],
            'role' => ['S' => 'user'],
            'status' => ['S' => 'active'],
            'created_at' => ['S' => date('c')],
            'updated_at' => ['S' => date('c')]
        ];

        try {
            $dynamodb->putItem([
                'TableName' => 'Users',
                'Item' => $item
            ]);
            $success = 'Registration successful! You can now log in.';
        } catch (DynamoDbException $e) {
            $errors[] = 'Failed to register user: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Global Scam Report Center</title>
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
                <a class="nav-link" href="login.php">Login</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-danger mb-3"></i>
                        <h2>Join Our Community</h2>
                        <p class="text-muted">Help protect others from scams</p>
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

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                            <div class="mt-2">
                                <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                            <div class="form-text">3-50 characters, letters, numbers, and underscores only</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Full Name
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Already have an account? 
                            <a href="login.php" class="text-danger text-decoration-none">Sign in here</a>
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
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
