<?php
require_once '../connection.php';

// Set the header for JSON response
header('Content-Type: application/json');

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

$user_id = $data['user_id'];

// Prepare the SQL statement
$sql = "UPDATE users SET status = 1 WHERE user_id = ?";
$stmt = $connection->prepare($sql);

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
}
?>
