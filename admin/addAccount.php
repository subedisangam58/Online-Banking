<?php
session_start();
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountNumber = trim($_POST['accountNumber']);
    $bankName = trim($_POST['bankName']);
    
    if (!empty($accountNumber) && !empty($bankName)) {
        try {
            $sql = "INSERT INTO BankAccounts (Account_Number, Bank_Name) VALUES (?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ss", $accountNumber, $bankName);
            $stmt->execute();
            
            echo "Bank account added successfully.";
        } catch (Exception $ex) {
            die('Database Error: ' . $ex->getMessage());
        }
    } else {
        echo "Please fill in all fields.";
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
    <h2>Add Bank Account</h2>
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
        <div>
            <label for="accountNumber">Account Number:</label>
            <input type="text" name="accountNumber" id="accountNumber" required>
        </div>
        <div>
            <label for="bankName">Bank Name:</label>
            <input type="text" name="bankName" id="bankName" required>
        </div>
        <input type="submit" value="Add Account">
    </form>
</body>
</html>
