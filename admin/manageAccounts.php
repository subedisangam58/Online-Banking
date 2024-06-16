<?php
session_start();
/*if(!isset($_SESSION['admin_id'])){
    header('location:index.php?err=1');
    exit;
}*/
require_once '../connection.php';
//$user_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM Account";
$result = $connection->query($sql);

if (!$result) {
    trigger_error('Invalid query: ' . $connection->error);
}

if(isset($_POST['update'])) {
    // Update query
    $update_query = "UPDATE users SET 
                    Name = '$name',
                    Client_id = '$client_id',
                    National_id = '$national_id',
                    Phone = '$phone',
                    Email = '$email',
                    Address = '$address'
                    WHERE User_id = $userIdToUpdate";
    
    if ($connection->query($update_query) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $connection->error;
    }
}

if(isset($_POST['delete'])) {
    $delete_query = "DELETE FROM users WHERE User_id = $userIdToDelete";
    
    if ($connection->query($delete_query) === TRUE) {
        echo "Record deleted successfully";
        // Optionally, you can redirect the user or refresh the page after deletion
        // header("Location: clients.php");
        // exit();
    } else {
        echo "Error deleting record: " . $connection->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="client.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <h1>Manage Accounts</h1>
            <table cellspacing=0>
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Account Type</th>
                        <th>Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['Account_id'] . "</td>";
                            echo "<td>" . $row['Account_type'] . "</td>";
                            echo "<td>" . $row['Rate'] . "</td>";
                            echo "<td>";
                            echo "<a href='update1.php?id=" . $row['Account_id'] . "'>Update</a>".'&nbsp;&nbsp;';
                            echo "<a href='delete1.php?id=" . $row['Account_id'] . "'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10'>No Accounts found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>    
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="../script.js"></script>
    <script>
        let toggle = document.querySelector('.toggle');
        let navigation = document.querySelector('.navigation');
        let main = document.querySelector('.main');
        toggle.onclick = function(){
            navigation.classList.toggle('active');
            main.classList.toggle('active');
        }

        let list = document.querySelectorAll('.navigation li');
        function activeLink(){
            list.forEach((item) =>
            item.classList.remove('hovered'));
            this.classList.add('hovered');
        }
        list.forEach((item) =>
        item.addEventListener('mouseover',activeLink));
    </script>
</body>
</html>
