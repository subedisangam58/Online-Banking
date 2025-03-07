<?php
session_start();
require_once '../connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the user ID from the session
if (!isset($_SESSION['client_id'])) {
    echo json_encode([
        'status' => 'error',
        'error' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['client_id'];

// Query the database for the updated account balance
$query = "SELECT Amount FROM Users WHERE user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $account_amount = $user_data['Amount'];
    
    // Format the output according to how it's displayed in the UI
    // Include the status field that the AJAX function is checking for
    echo json_encode([
        'status' => 'success',
        'balance' => $account_amount,
        'display' => str_repeat('*', strlen($account_amount))
    ]);
} else {
    // If there's an error or no data found
    echo json_encode([
        'status' => 'error',
        'error' => mysqli_error($connection)
    ]);
    
    // Log the error
    error_log("Error loading balance for user $user_id: " . mysqli_error($connection));
}

// Close the database connection
mysqli_stmt_close($stmt);
mysqli_close($connection);
?>