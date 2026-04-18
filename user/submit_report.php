<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
include '../config/s3.php';

require_once '../vendor/autoload.php';
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Exception\DynamoDbException;

$reportId = uniqid('report_');
$userId = $_SESSION['userId'];
$timestamp = date('c'); 

$marshaler = new Marshaler();
// Check if user is logged in
require_login();

// Redirect admin to admin dashboard
if (is_admin()) {
    redirect('../admin/dashboard.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    }
    
    // Get and sanitize input
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $scam_type = sanitize_input($_POST['scam_type']);
    $amount_lost = !empty($_POST['amount_lost']) ? floatval($_POST['amount_lost']) : 0.00;
    $date_occurred = !empty($_POST['date_occurred']) ? $_POST['date_occurred'] : null;
    $location = sanitize_input($_POST['location']);
    $contact_info = sanitize_input($_POST['contact_info']);
    $evidence = sanitize_input($_FILES['evidence']);
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required.';
    // } elseif (strlen($title) < 10) {
    //     $errors[] = 'Title must be at least 10 characters long.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } 
    //elseif (strlen($description) < 50) {
    //     $errors[] = 'Description must be at least 50 characters long.';
    // }
    
    if (empty($scam_type)) {
        $errors[] = 'Scam type is required.';
    } elseif (!in_array($scam_type, get_scam_types())) {
        $errors[] = 'Invalid scam type selected.';
    }

    $scam_types = get_scam_types();
    
    if ($amount_lost < 0) {
        $errors[] = 'Amount lost cannot be negative.';
    }

    // If no errors, create the report
    // if (empty($errors)) {
    //     try {
    //         $stmt = $pdo->prepare("INSERT INTO scam_reports (user_id, title, description, scam_type, amount_lost, date_occurred, location, contact_info, evidence, status) 
    //                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    //         $stmt->execute([
    //             $_SESSION['user_id'],
    //             $title,
    //             $description,
    //             $scam_type,
    //             $amount_lost,
    //             $date_occurred,
    //             $location,
    //             $contact_info,
    //             $evidenceUrl ?? null
    //         ]);
            
    //         $success = 'Report submitted successfully! It will be reviewed by our team.';
            
    //         // Clear form data
    //         $_POST = [];
    //     } catch (PDOException $e) {
    //         $errors[] = 'Failed to submit report. Please try again.';
    //     }
    // }
    if (empty($errors)) {
        $evidenceUrl = '';
        if (!empty($_FILES['evidence']['name'])) {
            try {
                $evidenceUrl = upload_to_s3($_FILES['evidence']);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $item = $marshaler->marshalItem([
                'reportId' => $reportId,
                'userId' => $userId,
                'title' => $title,
                'description' => $description,
                'scam_type' => $scam_type,
                'amount_lost' => $amount_lost,
                'date_occurred' => $date_occurred,
                'location' => $location,
                'contact_info' => $contact_info,
                'evidence' => $evidenceUrl,
                'status' => 'pending',
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]);

            try {
                $dynamodb->putItem([
                    'TableName' => 'ScamReports',
                    'Item' => $item
                ]);
                $success = 'Report submitted successfully!';
                $_POST = [];
            } catch (DynamoDbException $e) {
                $errors[] = 'Failed to submit report: ' . $e->getMessage();
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
    <title>Submit Report - Global Scam Report Center</title>
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">View Public Site</a></li>
                        <li><a class="dropdown-item" href="dashboard.php">My Dashboard</a></li>
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h2>Report a Scam</h2>
                        <p class="text-muted">Help protect others by sharing your experience</p>
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
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <div class="mt-2">
                                <a href="dashboard.php" class="btn btn-success btn-sm">View My Reports</a>
                                <a href="submit_report.php" class="btn btn-outline-success btn-sm">Submit Another</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Report Title *
                                </label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       placeholder="Brief description of the scam" required>
                                <!-- <div class="form-text">Minimum 10 characters</div> -->
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="scam_type" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Scam Type *
                                </label>
                                <select class="form-select" id="scam_type" name="scam_type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach (get_scam_types() as $type): ?>
                                        <option value="<?= htmlspecialchars($type); ?>" <?= (isset($_POST['scam_type']) && $_POST['scam_type'] == $type) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Detailed Description *
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="6" 
                                      placeholder="Provide a detailed description of what happened, how the scam was conducted, and any other relevant information..." 
                                      required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <!-- <div class="form-text">Minimum 50 characters. Be as detailed as possible to help others.</div> -->
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount_lost" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Amount Lost (RM)
                                </label>
                                <input type="number" class="form-control" id="amount_lost" name="amount_lost" 
                                       value="<?php echo isset($_POST['amount_lost']) ? htmlspecialchars($_POST['amount_lost']) : ''; ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                                <div class="form-text">Leave empty if no money was lost</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_occurred" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date Occurred
                                </label>
                                <input type="date" class="form-control" id="date_occurred" name="date_occurred" 
                                       value="<?php echo isset($_POST['date_occurred']) ? htmlspecialchars($_POST['date_occurred']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Location
                            </label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                   placeholder="City, State/Country or Online">
                            <div class="form-text">Where did this scam occur? (e.g., "Online", "Kuala Lumpur, KL", etc.)</div>
                        </div>

                        <div class="mb-3">
                            <label for="contact_info" class="form-label">
                                <i class="fas fa-phone me-1"></i>Scammer Contact Information
                            </label>
                            <textarea class="form-control" id="contact_info" name="contact_info" rows="3" 
                                      placeholder="Phone numbers, email addresses, websites, or any other contact information used by the scammer..."><?php echo isset($_POST['contact_info']) ? htmlspecialchars($_POST['contact_info']) : ''; ?></textarea>
                            <div class="form-text">This helps others identify the same scammer</div>
                        </div>
                                            
                        <div class="mb-4">
                            <label for="evidence" class="form-label">
                                <i class="fas fa-file-alt me-1"></i>Upload Evidence
                            </label>
                            <input class="form-control" type="file" name="evidence" id="evidence" />
                            <div class="form-text">Upload a screenshot, document, or file as evidence (Max: 5MB)</div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Your report will be reviewed by our team before being published. 
                            Please ensure all information is accurate and does not contain any personal sensitive information.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-paper-plane me-2"></i>Submit Report
                            </button>
                        </div>
                    </form>
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
