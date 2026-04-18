<?php
// $host = 'scamwebsite-instance-1.cpmid54tj6m5.us-east-1.rds.amazonaws.com';
// $dbname = 'scam_report_center';
// $username = 'admin';
// $password = 'lab-password'; 

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// } catch(PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }

require __DIR__ . '/../vendor/autoload.php';

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
?>

