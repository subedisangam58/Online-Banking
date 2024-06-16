<?php
session_start();
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transaction_id'])) {
    $transaction_id = $_POST['transaction_id'];
    
    // Fetch transaction details to perform rollback if necessary
    $sql = "SELECT * FROM transactions WHERE Transaction_id = '$transaction_id'";
    $result = $connection->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $amount = $row['Amount'];
        $receiver_account = $row['Receiver_Account_Name'];

        // Perform rollback action, for example:
        // Increase the account balance of the receiver by the amount of the transaction
        $update_receiver_balance_sql = "UPDATE accounts SET balance = balance + $amount WHERE account_number = '$receiver_account'";
        $update_receiver_balance_result = $connection->query($update_receiver_balance_sql);

        // Delete the transaction record
        $delete_transaction_sql = "DELETE FROM transactions WHERE Transaction_id = '$transaction_id'";
        $delete_transaction_result = $connection->query($delete_transaction_sql);

        if ($update_receiver_balance_result && $delete_transaction_result) {
            // Rollback successful
            $_SESSION['success_msg'] = "Transaction rolled back successfully.";
        } else {
            // Rollback failed
            $_SESSION['error_msg'] = "Failed to rollback transaction.";
        }
    } else {
        // Transaction not found
        $_SESSION['error_msg'] = "Transaction not found.";
    }

    // Redirect back to the transaction history page
    header("Location: transactionHistory.php");
    exit();
} else {
    // Redirect to the home page if accessed directly without proper POST request
    header("Location: index.php");
    exit();
}
?>
