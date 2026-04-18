<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
use Aws\DynamoDb\Exception\DynamoDbException;

// Check if user is logged in and is admin
require_login();
require_admin();

$success = '';
$errors = [];

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create':
                $username = sanitize_input($_POST['username']);
                $email = sanitize_input($_POST['email']);
                $full_name = sanitize_input($_POST['full_name']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                // Validation
                if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
                    $errors[] = 'All fields are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                } elseif (strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters long.';
                } else {
                    // Check if username or email already exists
                    try {
                        $result = $dynamodb->scan([
                            'TableName' => 'Users',
                            'FilterExpression' => 'username = :u OR email = :e',
                            'ExpressionAttributeValues' => [
                                ':u' => ['S' => $username],
                                ':e' => ['S' => $email],
                            ],
                            'ProjectionExpression' => 'userId'
                        ]);

                        if (!empty($result['Items'])) {
                            $errors[] = 'Username or email already exists.';
                        } else {
                            // No duplicates, proceed to insert
                            $userId = uniqid();
                            $hashed_password = hash_password($password); 

                            $dynamodb->putItem([
                                'TableName' => 'Users',
                                'Item' => [
                                    'userId' => ['S' => $userId],
                                    'username' => ['S' => $username],
                                    'email' => ['S' => $email],
                                    'password' => ['S' => $hashed_password],
                                    'full_name' => ['S' => $full_name],
                                    'role' => ['S' => $role],
                                    'status' => ['S' => $status],
                                    'created_at' => ['S' => date('c')],
                                ]
                            ]);

                            $success = 'User created successfully.';
                        }
                    } catch (DynamoDbException $e) {
                        $errors[] = 'Failed to create user: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update':
                $user_id = $_POST['user_id'];
                $username = sanitize_input($_POST['username']);
                $email = sanitize_input($_POST['email']);
                $full_name = sanitize_input($_POST['full_name']);
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                // Validation
                if (empty($username) || empty($email) || empty($full_name)) {
                    $errors[] = 'All fields are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                } else {
                    // Check if username or email already exists for other users
                    try {
                        $result = $dynamodb->scan([
                            'TableName' => 'Users',
                            'FilterExpression' => '(username = :u OR email = :e) AND userId <> :id',
                            'ExpressionAttributeValues' => [
                                ':u' => ['S' => $username],
                                ':e' => ['S' => $email],
                                ':id' => ['S' => $user_id]
                            ],
                            'ProjectionExpression' => 'userId'
                        ]);

                        if (!empty($result['Items'])) {
                            $errors[] = 'Username or email already exists.';
                        } else {
                            // Step 2: Update user
                           $dynamodb->updateItem([
                                'TableName' => 'Users',
                                'Key' => [
                                    'userId' => ['S' => $user_id]
                                ],
                                'UpdateExpression' => 'SET username = :u, email = :e, full_name = :f, #r = :r, #s = :s',
                                'ExpressionAttributeValues' => [
                                    ':u' => ['S' => $username],
                                    ':e' => ['S' => $email],
                                    ':f' => ['S' => $full_name],
                                    ':r' => ['S' => $role],
                                    ':s' => ['S' => $status]
                                ],
                                'ExpressionAttributeNames' => [
                                    '#r' => 'role',
                                    '#s' => 'status'
                                ]
                            ]);
                            $success = 'User updated successfully.';
                        }
                    } catch (DynamoDbException $e) {
                        $errors[] = 'Failed to update user: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'suspend':

                try {
                    // First, get the user's current role
                    $result = $dynamodb->getItem([
                        'TableName' => 'Users',
                        'Key' => [
                            'userId' => ['S' => $userId]
                        ]
                    ]);

                    if (!isset($result['Item'])) {
                        $errors[] = "User not found.";
                    } elseif ($result['Item']['role']['S'] === 'admin') {
                        $errors[] = "Cannot suspend an admin.";
                    } else {
                        // Proceed to update the user's status
                        $dynamodb->updateItem([
                            'TableName' => 'Users',
                            'Key' => [
                                'userId' => ['S' => $userId]
                            ],
                            'UpdateExpression' => 'SET #s = :newStatus',
                            'ExpressionAttributeNames' => [
                                '#s' => 'status'
                            ],
                            'ExpressionAttributeValues' => [
                                ':newStatus' => ['S' => 'suspended']
                            ]
                        ]);

                        $success = "User suspended successfully.";
                    }
                } catch (DynamoDbException $e) {
                    $errors[] = "Failed to suspend user: " . $e->getMessage();
                }
                break;
                
            case 'activate':
                try {
                    $user_id = $_POST['user_id'];

                    // First, get the user's current role
                    $result = $dynamodb->getItem([
                        'TableName' => 'Users',
                        'Key' => [
                            'userId' => ['S' => $user_id]
                        ]
                    ]);

                    if (!isset($result['Item'])) {
                        $errors[] = "User not found.";
                    } elseif ($result['Item']['role']['S'] === 'admin') {
                        $errors[] = "Cannot activate an admin.";
                    } else {
                        // Proceed to update the user's status
                        $dynamodb->updateItem([
                            'TableName' => 'Users',
                            'Key' => [
                                'userId' => ['S' => $user_id]
                            ],
                            'UpdateExpression' => 'SET #s = :newStatus',
                            'ExpressionAttributeNames' => [
                                '#s' => 'status'
                            ],
                            'ExpressionAttributeValues' => [
                                ':newStatus' => ['S' => 'active']
                            ]
                        ]);

                        $success = "User activated successfully.";
                    }
                } catch (DynamoDbException $e) {
                    $errors[] = "Failed to activate user: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $user_id = $_POST['user_id'];
                if (!empty($user_id)) {
                    try {
                        $dynamodb->deleteItem([
                            'TableName' => 'Users',
                            'Key' => ['userId' => ['S' => $user_id]]
                        ]);
                        $success = 'User deleted successfully.';
                    } catch (Exception $e) {
                        $errors[] = 'Error deleting user: ' . $e->getMessage();
                    }
                } else {
                    $errors[] = 'Missing user ID.';
                }
                break;
        }
    }
}

// Get all users (except current admin)
$users = [];

try {
    $result = $dynamodb->scan([
        'TableName' => 'Users',
    ]);

    $users = $result['Items'];
} catch (DynamoDbException $e) {
    echo "Error fetching users: " . $e->getMessage();
}

foreach ($users as &$user) {
    $userId = $user['userId']['S'];
    $report_count = 0;
    $last_report = null;

    try {
        $reports = $dynamodb->query([
            'TableName' => 'ScamReports',
            'IndexName' => 'userId-index',
            'KeyConditionExpression' => 'userId = :uid',
            'ExpressionAttributeValues' => [
                ':uid' => ['S' => $userId]
            ]
        ]);

        $report_count = count($reports['Items']);

        if ($report_count > 0) {
            $dates = array_map(fn($r) => strtotime($r['created_at']['S']), $reports['Items']);
            $last_report = max($dates);
        }
    } catch (DynamoDbException $e) {
        echo "Error fetching reports for user $userId: " . $e->getMessage();
    }

    $user['report_count'] = $report_count;
    $user['last_report'] = $last_report ? date('Y-m-d H:i:s', $last_report) : null;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 pe-md-4">
                <div class="list-group admin-sidebar">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ps-md-3 admin-main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-users me-2"></i>Manage Users</h1>
                        <p class="text-muted">Create, view, edit, and manage user accounts</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="fas fa-plus me-2"></i>Create New User
                        </button>
                        <span class="badge bg-success ms-2">Administrator</span>
                    </div>
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
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User Details</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Reports</th>
                                    <th>Joined</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                        $seenUserIds = [];
                                        foreach ($users as $user):
                                            $userId = $user['userId']['S'];

                                            if (in_array($userId, $seenUserIds)) {
                                                continue; 
                                            }

                                            $seenUserIds[] = $userId;
                                    ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']['S']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        @<?php echo htmlspecialchars($user['username']['S']); ?>
                                                        <br>
                                                        <?php echo htmlspecialchars($user['email']['S']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role']['S'] == 'admin' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']['S']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status']['S'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['status']['S']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo $user['report_count']; ?></strong> reports
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($user['created_at']['S'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    if ($user['last_report']) {
                                                        echo time_elapsed_string($user['last_report']);
                                                    } else {
                                                        echo 'No reports';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($user['role']['S'] === 'moderator' || $user['role']['S'] === 'user'): ?>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewUserModal" 
                                                                onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editUserModal" 
                                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                                title="Edit User">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <?php if ($user['status'] == 'active'): ?>
                                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                    onclick="confirmAction('suspend', <?php echo $user['userId']['S']; ?>, '<?php echo htmlspecialchars($user['username']['S']); ?>')"
                                                                    title="Suspend User">
                                                                <i class="fas fa-pause"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-outline-success btn-sm" 
                                                                    onclick="confirmAction('activate', '<?php echo $user['userId']['S']; ?>', '<?php echo htmlspecialchars($user['username']['S']); ?>')"
                                                                    title="Activate User">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="confirmAction('delete', '<?php echo $user['userId']['S']; ?>', '<?php echo htmlspecialchars($user['username']['S']); ?>')"
                                                                title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Protected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="create_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="create_username" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="create_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="create_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="create_password" name="password" required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="create_role" class="form-label">Role</label>
                                <select class="form-select" id="create_role" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="moderator">Moderator</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_status" class="form-label">Status</label>
                                <select class="form-select" id="create_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-plus me-1"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_role" class="form-label">Role</label>
                                <select class="form-select" id="edit_role" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="moderator">Moderator</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Password cannot be changed here. User must reset their password through the login page.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>User Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails">
                    <!-- User details will be populated here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Form (hidden) -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="action" id="actionType">
        <input type="hidden" name="user_id" id="actionUserId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUser(user) {
            const details = `
                <div class="row mb-3">
                    <div class="col-4"><strong>Full Name:</strong></div>
                    <div class="col-8">${user.full_name.S}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Username:</strong></div>
                    <div class="col-8">@${user.username.S}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Email:</strong></div>
                    <div class="col-8">${user.email.S}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Role:</strong></div>
                    <div class="col-8"><span class="badge bg-${user.role.S === 'admin' ? 'success' : 'primary'}">${user.role.S.charAt(0).toUpperCase() + user.role.S.slice(1)}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8"><span class="badge bg-${user.status.S === 'active' ? 'success' : 'danger'}">${user.status.S.charAt(0).toUpperCase() + user.status.S.slice(1)}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Reports:</strong></div>
                    <div class="col-8">${user.report_count} reports submitted</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Joined:</strong></div>
                    <div class="col-8">${new Date(user.created_at.S).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}</div>
                </div>
                ${user.last_report ? `
                <div class="row mb-3">
                    <div class="col-4"><strong>Last Report:</strong></div>
                    <div class="col-8">${new Date(user.last_report).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}</div>
                </div>
                ` : ''}
            `;
            document.getElementById('userDetails').innerHTML = details;
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.userId.S;
            document.getElementById('edit_username').value = user.username.S;
            document.getElementById('edit_email').value = user.email.S;
            document.getElementById('edit_full_name').value = user.full_name.S;
            document.getElementById('edit_role').value = user.role.S;
            document.getElementById('edit_status').value = user.status.S;
        }

        function confirmAction(action, userId, username) {
            console.log("JS Triggered for", action, userId);
            let message = '';
            let actionText = '';
            
            switch(action) {
                case 'suspend':
                    message = `Are you sure you want to suspend user "${username}"?\n\nThis will prevent them from logging in and accessing the system.`;
                    actionText = 'Suspend';
                    break;
                case 'activate':
                    message = `Are you sure you want to activate user "${username}"?\n\nThis will allow them to log in and access the system.`;
                    actionText = 'Activate';
                    break;
                case 'delete':
                    message = `WARNING: Are you sure you want to permanently delete user "${username}"?\n\nThis action will:\n• Delete the user account permanently\n• Remove all associated data\n• Cannot be undone\n\nType "DELETE" to confirm this action.`;
                    
                    const confirmText = prompt(message);
                    if (confirmText !== 'DELETE') {
                        alert('User deletion cancelled. You must type "DELETE" to confirm.');
                        return;
                    }
                    console.log("User confirmed delete.");
                    break;
            }
            
            if (action !== 'delete' && !confirm(message)) {
                return;
            }
             console.log("Form submit triggered with:", action, userId);
            document.getElementById('actionType').value = action;
            document.getElementById('actionUserId').value = userId;
            document.getElementById('actionForm').submit();
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Create form validation
            const createForm = document.querySelector('#createUserModal form');
            if (createForm) {
                createForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('create_password').value;
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
