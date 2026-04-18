<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

if ($_SESSION['role'] !== 'moderator') {
    redirect('index.php');
}

if (!isset($_GET['scamTypeId']) || trim($_GET['scamTypeId']) === '') {
    die("Missing or invalid scamTypeId.");
}

$scamTypeId = $_GET['scamTypeId'] ?? null;

// Fetch existing scam type
try {
    $result = $dynamodb->getItem([
        'TableName' => 'ScamTypes',
        'Key' => [
            'scamTypeId' => ['S' => $scamTypeId]
        ]
    ]);

    if (!isset($result['Item'])) {
        die("Scam type not found.");
    }

    $type = $result['Item'];

} catch (DynamoDbException $e) {
    die("Failed to fetch scam type: " . $e->getMessage());
}

$success = $error = null;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $error = "Scam type name cannot be empty.";
    } else {
        try {
            $dynamodb->updateItem([
                'TableName' => 'ScamTypes',
                'Key' => [
                    'scamTypeId' => ['S' => $scamTypeId]
                ],
                'UpdateExpression' => 'SET #n = :name, #d = :desc',
                'ExpressionAttributeNames' => [
                    '#n' => 'name',
                    '#d' => 'description'
                ],
                'ExpressionAttributeValues' => [
                    ':name' => ['S' => $name],
                    ':desc' => ['S' => $description]
                ]
            ]);

            $success = "Scam type updated successfully.";
            $type['name']['S'] = $name;
            $type['description']['S'] = $description;

        } catch (DynamoDbException $e) {
            $error = "Failed to update scam type: " . $e->getMessage();
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
            <!-- Main Content -->
            <h3 class="mb-4"><i class="fas fa-edit me-2"></i>Edit Scam Type</h3>

            <div class="mb-3">
                <a href="moderator_scam_types.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Scam Types
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-light p-4 rounded shadow-sm">
                <div class="mb-3">
                    <label class="form-label">Scam Type Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($type['name']['S']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($type['description']['S'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
