<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = $_POST['otp'];
    $stored_otp = $_SESSION['otp'];
    $user_id = $_SESSION['client_id'];
    $amount = $_SESSION['amount'] ?? 0; // Ensure the amount is set
    $account_amount = $_SESSION['account_amount'] ?? 0; // Ensure the account amount is set
    $receiverName = $_SESSION['receiverName'] ?? ''; // Ensure receiver's name is set
    $phone = $_SESSION['phone'] ?? ''; // Ensure phone number is set

    if ($entered_otp == $stored_otp) {
        $connection = mysqli_connect('localhost', 'root', '', 'onlinebanking_db'); // Update with actual DB credentials

        if ($connection) {
            // Proceed with withdrawal process
            if ($amount <= $account_amount) {
                $current_date = date('Y-m-d');
                $Tid = "TRN" . date('md') . rand(100, 999);

                $sql = "INSERT INTO transactions (Transaction_id, Receiver_Bank_Name, Receiver_Bank_Number, Receiver_Account_Name, Phone, Amount, Date, Remarks, Tuser_id)
                    VALUES ('$Tid', '', '', '$receiverName', '$phone', $amount, '$current_date', 'Withdrawal', $user_id)";
                mysqli_query($connection, $sql);

                $update_query = "UPDATE users SET Amount = Amount - $amount WHERE user_id = $user_id";
                mysqli_query($connection, $update_query);

                $_SESSION['msg'] = 'Withdrawal successful.';
            } else {
                $_SESSION['msg'] = 'Withdrawal amount exceeds account balance.';
            }

            mysqli_close($connection); // Always close the connection
        } else {
            $_SESSION['msg'] = 'Database connection failed. Please try again later.';
        }
    } else {
        $_SESSION['msg'] = 'Invalid OTP. Please try again.';
    }
    header('location: withdrawal.php');
    exit();
}
?>
