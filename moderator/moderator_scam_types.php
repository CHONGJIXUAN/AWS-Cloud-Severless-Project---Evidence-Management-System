<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

if ($_SESSION['role'] !== 'moderator') {
    redirect('index.php');
}

$tableName = 'ScamTypes';

if (isset($_POST['add_type'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $scamTypeId = uniqid('scam_', true);
    
    if (!empty($name)) {
        try {
            $dynamodb->putItem([
                'TableName' => $tableName,
                'Item' => [
                    'scamTypeId' => ['S' => $scamTypeId],
                    'name' => ['S' => $name],
                    'description' => ['S' => $description],
                    'created_at' => ['S' => date('c')],
                ]
            ]);
            $success = "Scam type added successfully.";
        } catch (DynamoDbException $e) {
            $error = "Failed to add scam type: " . $e->getMessage();
        }
    } else {
        $error = "Scam type name is required.";
    }
}

if (isset($_GET['delete'])) {
    $typeId = $_GET['delete'];
    try {
        $dynamodb->deleteItem([
            'TableName' => $tableName,
            'Key' => [
                'scamTypeId' => ['S' => $typeId],
            ]
        ]);
        $success = "Scam type deleted.";
    } catch (DynamoDbException $e) {
        $error = "Failed to delete: " . $e->getMessage();
    }
}

try {
    $result = $dynamodb->scan([
        'TableName' => $tableName,
    ]);
    $types = $result['Items'];
} catch (DynamoDbException $e) {
    $error = "Failed to fetch scam types: " . $e->getMessage();
    $types = [];
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="fas fa-tags me-2"></i>Manage Scam Types</h3>
                <a href="moderator_dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <!-- Add New Scam Type -->
            <form method="POST" class="border p-4 rounded bg-light mb-4">
                <h5 class="mb-3">Add New Scam Type</h5>
                <div class="mb-3">
                    <label class="form-label">Scam Type Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" name="add_type" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add Type
                </button>
            </form>

            <!-- Existing Types -->
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                            <form method="POST">
                                <input type="hidden" name="type_id" value="<?= htmlspecialchars($type['scamTypeId']['S']) ?>">

                                <tr>
                                    <td>
                                        <input type="text" name="edit_name" class="form-control"
                                            value="<?= htmlspecialchars($type['name']['S']) ?>" disabled>
                                    </td>
                                    <td>
                                        <textarea name="edit_description" class="form-control" rows="1" disabled><?= htmlspecialchars($type['description']['S'] ?? '') ?></textarea>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="moderator_edit_type.php?scamTypeId=<?= urlencode($type['scamTypeId']['S']) ?>" class="btn btn-sm btn-success d-flex align-items-center">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= urlencode($type['scamTypeId']['S']) ?>"class="btn btn-sm btn-danger d-flex align-items-center" onclick="return confirm('Delete this scam type?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </form>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
