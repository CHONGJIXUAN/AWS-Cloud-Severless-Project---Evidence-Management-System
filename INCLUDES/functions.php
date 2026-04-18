<?php
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

function sanitize_input($data) {
    if (!is_string($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_logged_in() {
    return isset($_SESSION['userId']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_messages() {
    $messages = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $messages;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        redirect('index.php');
    }
}

// function get_scam_types() {
//     return [
//         "Phone Scam",
//         "Email Phishing",
//         "Online Shopping",
//         "Investment Fraud",
//         "Romance Scam",
//         "Tech Support",
//         "Lottery/Prize",
//         "Identity Theft",
//         "Cryptocurrency",
//         "Other"
//     ];
// }

function get_scam_types() {
    global $dynamodb;
    $tableName = 'ScamTypes';

    try {
        $result = $dynamodb->scan([
            'TableName' => $tableName
        ]);

        $types = [];
        foreach ($result['Items'] as $item) {
            if (isset($item['name']['S'])) {
                $types[] = $item['name']['S'];
            }
        }

        return $types;
    } catch (DynamoDbException $e) {
        echo '<div style="color:red">DynamoDB Error: ' . $e->getMessage() . '</div>';
        return [];
    }
}


function format_currency($amount) {
    return 'RM ' . number_format($amount, 2);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array();
    
    if ($diff->y) $string['y'] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    if ($diff->m) $string['m'] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    if ($weeks) $string['w'] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    if ($days) $string['d'] = $days . ' day' . ($days > 1 ? 's' : '');
    if ($diff->h) $string['h'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
    if ($diff->i) $string['i'] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    if ($diff->s) $string['s'] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
