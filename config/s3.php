<?php 

require '../vendor/autoload.php';

use Aws\Exception\AwsException;

function upload_to_s3($file){
    // AWS S3 config
    $bucketName = $_ENV['S3_BUCKETNAME'];
    $region = $_ENV['AWS_DEFAULT_REGION'];
    $accessKey = $_ENV['AWS_ACCESS_KEY_ID'];
    $secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'];
    $sessionToken = $_ENV['AWS_SESSION_TOKEN'];

    // Initialize S3 client
    $s3 = new Aws\S3\S3Client([
        'region' => $region,
        'version' => 'latest',
        'credentials' => [
            'key' => $accessKey,
            'secret' => $secretKey,
            'token' => $sessionToken 
        ]
    ]);

    // Validate file
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Invalid file upload.");
    }

     // Upload file
    $fileTmp = $file['tmp_name'];
    $fileName = time() . '_' . basename($file['name']);
    $s3Key = 'evidence/' . $fileName;

    try {
        $s3->putObject([
            'Bucket' => $bucketName,
            'Key' => $s3Key,
            'SourceFile' => $fileTmp,
        ]);

        return $s3->getObjectUrl($bucketName, $s3Key);
    } catch (AwsException $e) {
        throw new Exception("S3 upload failed: " . $e->getMessage());
    }
}

// // Function to upload file to S3
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!empty($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
//         $fileTmp = $_FILES['evidence']['tmp_name'];
//         $fileName = time() . '_' . basename($_FILES['evidence']['name']);
//         $s3Key = 'evidence/' . $fileName;

//         try {
//             //Upload the file to S3
//             $s3->putObject([
//                 'Bucket' => $bucketName,
//                 'Key'    => $s3Key,
//                 'SourceFile' => $fileTmp,
//             ]);

//             //Get the file URL
//             $evidenceUrl = $s3->getObjectUrl($bucketName, $s3Key);

//             echo "Uploaded successfully: " . $evidenceUrl;

//         } catch (AwsException $e) {
//             echo "Upload failed: " . $e->getMessage();
//         }
//     } else {
//         echo "No file uploaded or upload error.";
//     }
// }


?>