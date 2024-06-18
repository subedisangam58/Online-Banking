<?php
session_start();
require_once '../connection.php';

if(isset($_POST['submit'])) {
    $account_id = $_POST['account_id'];
    $account_type = $_POST['account_type'];
    $rate = $_POST['rate'];

    $update_query = "UPDATE Account SET 
                    Account_type = '$account_type',
                    Rate = '$rate'
                    WHERE Account_id = $account_id";
    
    if ($connection->query($update_query) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $connection->error;
    }
} else {
    $account_id = $_GET['id'];

    $sql = "SELECT * FROM Account WHERE Account_id = $account_id";
    $result = $connection->query($sql);
    $row = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account</title>
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>
        <div class="transactions">
            <h1>Update Account</h1>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="account_id" value="<?php echo $row['Account_id']; ?>">
                <label for="account_type">Account Type:</label>
                <input type="text" name="account_type" value="<?php echo $row['Account_type']; ?>"><br>
                <label for="rate">Rate:</label>
                <input type="text" name="rate" value="<?php echo $row['Rate']; ?>"><br>
                <input type="submit" name="submit" value="Update">
            </form>
        </div>
    </div>
</body>
</html>
