<?php
session_start();
include 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Global Scam Report Center</title>
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
                        <a class="nav-link" href="reports.php">Browse Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About Us</a>
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
    <section class="bg-danger text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-info-circle me-2"></i>About Us</h1>
                    <p class="lead">Learn about our mission to protect communities from fraud</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Mission Section -->
                <div class="card mb-5">
                    <div class="card-body p-5">
                        <h2 class="text-danger mb-4">
                            <i class="fas fa-bullseye me-2"></i>Our Mission
                        </h2>
                        <p class="lead">
                            The Global Scam Report Center is dedicated to creating a safer digital world by empowering communities 
                            to share their experiences with fraud and protect others from falling victim to scams.
                        </p>
                        <p>
                            We believe that by working together and sharing information, we can build a powerful defense against 
                            fraudsters and create awareness that saves people from financial and emotional harm.
                        </p>
                    </div>
                </div>

                <!-- How It Works Section -->
                <div class="card mb-5">
                    <div class="card-body p-5">
                        <h2 class="text-danger mb-4">
                            <i class="fas fa-cogs me-2"></i>How It Works
                        </h2>
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <i class="fas fa-user-plus fa-3x text-danger mb-3"></i>
                                <h5>1. Register</h5>
                                <p class="text-muted">Create a free account to start contributing to our community database.</p>
                            </div>
                            <div class="col-md-4 text-center mb-4">
                                <i class="fas fa-file-alt fa-3x text-danger mb-3"></i>
                                <h5>2. Report</h5>
                                <p class="text-muted">Submit detailed reports about scams you've encountered or heard about.</p>
                            </div>
                            <div class="col-md-4 text-center mb-4">
                                <i class="fas fa-shield-alt fa-3x text-danger mb-3"></i>
                                <h5>3. Protect</h5>
                                <p class="text-muted">Help others stay safe by sharing verified information about fraud attempts.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="card mb-5">
                    <div class="card-body p-5">
                        <h2 class="text-danger mb-4">
                            <i class="fas fa-star me-2"></i>Key Features
                        </h2>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Verified Reports:</strong> All submissions are reviewed before publication
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Easy Search:</strong> Find specific scam types or browse by category
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Community Driven:</strong> Real experiences from real people
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Privacy Protected:</strong> Personal information is kept secure
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Free Access:</strong> Browse reports without registration
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Regular Updates:</strong> Fresh reports added daily
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="card mb-5">
                    <div class="card-body p-5">
                        <h2 class="text-danger mb-4">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </h2>
                        <p>
                            Have questions, suggestions, or need help? We're here to assist you in making the internet 
                            a safer place for everyone.
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <p>
                                    <i class="fas fa-envelope text-danger me-2"></i>
                                    <strong>Email:</strong> support@globalscamreport.com
                                </p>
                                <p>
                                    <i class="fas fa-globe text-danger me-2"></i>
                                    <strong>Website:</strong> www.globalscamreport.com
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p>
                                    <i class="fas fa-clock text-danger me-2"></i>
                                    <strong>Response Time:</strong> Within 24 hours
                                </p>
                                <p>
                                    <i class="fas fa-language text-danger me-2"></i>
                                    <strong>Languages:</strong> English (Primary)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="text-center">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <h3 class="mb-4">Ready to Join Our Community?</h3>
                        <p class="text-muted mb-4">Start protecting yourself and others from scams today.</p>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="register.php" class="btn btn-danger btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Join Now
                            </a>
                            <a href="reports.php" class="btn btn-outline-danger btn-lg">
                                <i class="fas fa-search me-2"></i>Browse Reports
                            </a>
                        </div>
                    <?php else: ?>
                        <h3 class="mb-4">Help Others Stay Safe</h3>
                        <p class="text-muted mb-4">Share your experience to protect others from scams.</p>
                        <a href="user/submit_report.php" class="btn btn-danger btn-lg">
                            <i class="fas fa-plus me-2"></i>Submit a Report
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
