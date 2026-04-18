<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;


$dynamodb = new DynamoDbClient([
    'region' => $_ENV['AWS_DEFAULT_REGION'],
    'version' => 'latest',
    'credentials' => [
        'key' => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        'token' => $_ENV['AWS_SESSION_TOKEN'],
    ]
]);

$marshaler = new Marshaler();
use Aws\DynamoDb\Exception\DynamoDbException;



$tableName = 'Users';

$users = [
    [
        'userId' => 'user_001',
        'username' => 'moderator',
        'email' => 'moderator@example.com',
        'full_name' => 'Moderator One',
        'password' => password_hash('ModPass123!', PASSWORD_DEFAULT),
        'role' => 'moderator',
        'status' => 'active',
        'created_at' => date('c')
    ],
    [
        'userId' => 'user_002',
        'username' => 'admin',
        'email' => 'admin@example.com',
        'full_name' => 'Admin One',
        'password' => password_hash('AdminPass123!', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('c')
    ]
];

foreach ($users as $user) {
    try {
        $dynamodb->putItem([
            'TableName' => $tableName,
            'Item' => [
                'userId' => ['S' => $user['userId']],
                'username' => ['S' => $user['username']],
                'email' => ['S' => $user['email']],
                'full_name' => ['S' => $user['full_name']],
                'password' => ['S' => $user['password']],
                'role' => ['S' => $user['role']],
                'status' => ['S' => $user['status']],
                'created_at' => ['S' => $user['created_at']],
            ]
        ]);
        echo "Inserted: {$user['username']} (" . ucfirst($user['role']) . ")\n";
    } catch (DynamoDbException $e) {
        echo "Failed to insert {$user['username']}: " . $e->getMessage() . "\n";
    }
}

?>