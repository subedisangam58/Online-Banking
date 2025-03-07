<?php
session_start();
require_once '../connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    echo '<option value="">Please login</option>';
    exit;
}

// Check if a bank was selected
if (!isset($_POST['bank']) || empty($_POST['bank'])) {
    echo '<option value="">Select a bank first</option>';
    exit;
}

$selectedBank = mysqli_real_escape_string($connection, $_POST['bank']);

// Debug: Log the selected bank
error_log("Selected bank: " . $selectedBank);

// Get all account numbers for the selected bank
// Modified query to match your table structure
$query = "SELECT account_number FROM BankAccounts WHERE Bank_name = '$selectedBank'";
$result = mysqli_query($connection, $query);

// Check for query errors
if (!$result) {
    error_log("Query error: " . mysqli_error($connection));
    echo '<option value="">Database error: ' . mysqli_error($connection) . '</option>';
    exit;
}

// Return option tags for each account
echo '<option value="">---Select Account Number---</option>';
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . htmlspecialchars($row['account_number']) . '">' . 
             htmlspecialchars($row['account_number']) . '</option>';
    }
} else {
    // Debug: Log that no accounts were found
    error_log("No accounts found for bank: " . $selectedBank);
    echo '<option value="">No accounts found for this bank</option>';
}
?>