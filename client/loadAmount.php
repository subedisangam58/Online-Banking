<?php
session_start();
include '../connection.php';
$user_id = $_SESSION['client_id'];
$query = "SELECT * FROM users WHERE user_id = $user_id";
$result = mysqli_query($connection, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $name = $user_data['Name'];
    $account_amount = $user_data['Amount'];
}
?>