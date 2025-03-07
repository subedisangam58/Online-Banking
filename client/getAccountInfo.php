<?php
session_start();
require_once '../connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Check if an account was selected
if (!isset($_POST['account']) || empty($_POST['account'])) {
    echo json_encode(['error' => 'No account selected']);
    exit;
}

$selectedAccount = mysqli_real_escape_string($connection, $_POST['account']);

// Debug: Log the selected account
error_log("Selected account: " . $selectedAccount);

// Since your BankAccounts table doesn't have Name and Phone fields,
// we need to get this information from the Users table using the account number
$query = "SELECT Name, Phone FROM Users WHERE Account_Number = '$selectedAccount'";
$result = mysqli_query($connection, $query);

// Check for query errors
if (!$result) {
    error_log("Query error: " . mysqli_error($connection));
    echo json_encode(['error' => 'Database error: ' . mysqli_error($connection)]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    $accountInfo = mysqli_fetch_assoc($result);
    echo json_encode([
        'name' => $accountInfo['Name'], 
        'phone' => $accountInfo['Phone']
    ]);
} else {
    // If the account exists in BankAccounts but not in Users,
    // we can return a placeholder or default values
    echo json_encode([
        'name' => 'Account Holder',
        'phone' => '9800000000'
    ]);
    
    error_log("Account details not found in Users table: " . $selectedAccount);
}
?>