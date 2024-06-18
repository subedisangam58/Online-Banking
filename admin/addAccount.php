<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header('location:login.php?err=1');
    exit;
}
require_once '../connection.php';
$err = [];
$accNumber = $bankName = $msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accNumber']) && !empty($_POST['accNumber']) && trim($_POST['accNumber'])) {
        $accNumber = trim($_POST['accNumber']);
        if (!preg_match("/^\d{16}$/", $accNumber)){
            $err['accNumber'] = 'Enter valid account number';
        }
    } else {
        $err['accNumber'] = 'Enter account Number';
    }

    if (isset($_POST['bankName']) && !empty($_POST['bankName']) && trim($_POST['bankName'])) {
        $bankName = trim($_POST['bankName']);
    } else {
        $err['bankName'] = 'Enter bank name';
    }
    
    if (count($err) == 0) {
        try {
            $sql = "INSERT INTO BankAccounts (Account_Number, Bank_Name) VALUES ($accNumber, $bankName)";
            mysqli_query($connection,$sql);
            $msg = "Bank account added successfully.";
        } catch (Exception $ex) {
            die('Database Error: ' . $ex->getMessage());
        }
    } else {
        $msg = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bank Account</title>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>
        <h2>Add Bank Account</h2>
        <?php echo $msg; ?>
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
            <div>
                <label for="accNumber">Account Number:</label>
                <input type="text" name="accNumber" id="accNumber">
                <?php echo isset($err['accNumber'])?$err['accNumber']:''; ?>
            </div>
            <div>
                <label for="bankName">Bank Name:</label>
                <input type="text" name="bankName" id="bankName">
                <?php echo isset($err['bankName'])?$err['bankName']:''; ?>
            </div>
            <input type="submit" value="Add Account">
        </form>
    </div>
    <script src="../script/toggle.js"></script>
</body>
</html>
