<?php
session_start();
require_once 'connection.php';
$user_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM transactions WHERE Tuser_id = $user_id";
$result = $connection->query($sql);

if (!$result) {
    trigger_error('Invalid query: ' . $connection->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link rel="stylesheet" href="transaction.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="account.css">
    <link rel="stylesheet" href="admin/client.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>
        <div class="history">
            <h1>Transaction History</h1>
            <h4>Select on any action to manage the transaction.</h4>
            
            <div class="transaction">
                <table>
                    <thead>
                        <tr>
                            <th>S.N</th>
                            <th>Transaction ID</th>
                            <th>Receiver Bank Name</th>
                            <th>Receiver Bank Number</th>
                            <th>Receiver Account Name</th>
                            <th>Receiver Mobile Number</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $count . "</td>";
                                echo "<td>" . $row['Transaction_id'] . "</td>";
                                echo "<td>" . $row['Receiver_Bank_Name'] . "</td>";
                                echo "<td>" . $row['Receiver_Bank_Number'] . "</td>";
                                echo "<td>" . $row['Receiver_Account_Name'] . "</td>";
                                echo "<td>" . $row['Phone'] . "</td>";
                                echo "<td>" . $row['Amount'] . "</td>";
                                echo "<td>" . $row['Date'] . "</td>";
                                echo "<td>" . $row['Remarks'] . "</td>";
                                echo "</tr>";
                                $count++;
                            }
                        } else {
                            echo "<tr><td colspan='10'>No transactions found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>    
    </div>
    <script src="admin/toggle.js"></script>
</body>
</html>
